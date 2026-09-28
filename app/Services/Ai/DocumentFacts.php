<?php

namespace App\Services\Ai;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\ActivityPlan;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Services\EvaluationService;
use App\Services\ReportService;
use App\Support\AcademicYear;
use Illuminate\Support\Carbon;

/**
 * Tổng hợp số liệu từ CSDL thành mảng gọn để đưa cho AI (hoặc mẫu giả lập).
 * Quy ước quyền riêng tư: chỉ có số liệu tổng hợp, KHÔNG có họ tên đoàn viên hay người phụ trách.
 */
class DocumentFacts
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly EvaluationService $evaluation,
    ) {
    }

    /**
     * Số liệu một hoạt động (dùng cho báo cáo tổng kết và thông báo mời).
     *
     * @return array<string, mixed>
     */
    public function forActivity(Activity $activity): array
    {
        $activity->loadMissing(['unionGroup', 'activityType', 'collaboratingGroups']);

        $byStatus = $activity->participants()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return [
            'truong' => 'Trường Đại học Thủ Dầu Một',
            'ma_hoat_dong' => $activity->code,
            'ten_hoat_dong' => $activity->name,
            'loai_hoat_dong' => $activity->activityType?->name,
            'to_chu_tri' => $activity->unionGroup?->name,
            'to_phoi_hop' => $activity->collaboratingGroups->map(fn (UnionGroup $g) => [
                'ten' => $g->name,
                'vai_tro' => Activity::COLLABORATION_ROLES[$g->pivot->role] ?? $g->pivot->role,
            ])->values()->all(),
            'bat_dau' => $activity->start_time?->format('H:i d/m/Y'),
            'ket_thuc' => $activity->end_time?->format('H:i d/m/Y'),
            'dia_diem' => $activity->location,
            'muc_tieu' => $activity->goal,
            'noi_dung' => $activity->content,
            'trang_thai' => $activity->statusLabel(),
            'tien_do_phan_tram' => (int) $activity->progress,
            'so_luong_du_kien' => $activity->expected_quantity,
            'so_luong_thuc_te' => $activity->actual_quantity,
            'kinh_phi_vnd' => $activity->budget !== null ? (float) $activity->budget : null,
            'nguoi_tham_gia' => [
                'da_dang_ky' => (int) ($byStatus['registered'] ?? 0),
                'da_tham_gia' => (int) ($byStatus['attended'] ?? 0),
                'vang_mat' => (int) ($byStatus['absent'] ?? 0),
                'da_huy' => (int) ($byStatus['cancelled'] ?? 0),
                'tong' => (int) $byStatus->sum(),
            ],
            'so_minh_chung' => $activity->evidences()->count(),
            'tinh_diem_thi_dua' => (bool) $activity->counts_for_evaluation,
            'diem_toi_da' => $activity->evaluation_max_score !== null ? (float) $activity->evaluation_max_score : null,
            'da_duoc_duyet_diem' => $activity->isEvaluationApproved(),
            'ghi_chu' => $activity->note,
        ];
    }

    /**
     * Số liệu tháng của một tổ.
     *
     * @return array<string, mixed>
     */
    public function forMonth(UnionGroup $group, int $year, int $month): array
    {
        $report = $this->reports->monthlyReport($year, $month, $group->id);
        $participants = ActivityParticipant::query()
            ->whereIn('activity_id', $report['activities']->pluck('id'))
            ->selectRaw('activity_id, status, COUNT(*) as total')
            ->groupBy('activity_id', 'status')
            ->get()
            ->groupBy('activity_id');

        $monthStart = Carbon::create($year, $month, 1);
        $nextStart = $monthStart->copy()->addMonth();

        return [
            'truong' => 'Trường Đại học Thủ Dầu Một',
            'to_cong_doan' => $group->name,
            'ky_bao_cao' => "Tháng {$month}/{$year}",
            'so_doan_vien_dang_hoat_dong' => Member::where('union_group_id', $group->id)->where('status', 'active')->count(),
            'tong_hop' => [
                'tong_so_hoat_dong' => $report['total_activities'],
                'da_hoan_thanh' => $report['completed_activities'],
                'ty_le_hoan_thanh_phan_tram' => $report['completion_rate'],
                'tong_luot_nguoi_tham_gia' => $report['total_participants'],
                'so_hoat_dong_qua_han' => $report['activities']->filter(fn (Activity $a) => $a->isOverdue())->count(),
            ],
            'hoat_dong' => $report['activities']->map(function (Activity $a) use ($participants) {
                $byStatus = ($participants->get($a->id) ?? collect())->pluck('total', 'status');

                return [
                    'ten' => $a->name,
                    'loai' => $a->activityType?->name,
                    'thoi_gian' => $a->start_time?->format('d/m/Y'),
                    'trang_thai' => $a->statusLabel().($a->isOverdue() ? ' (quá hạn)' : ''),
                    'tien_do_phan_tram' => (int) $a->progress,
                    'so_luong_du_kien' => $a->expected_quantity,
                    'so_luong_thuc_te' => $a->actual_quantity,
                    'so_nguoi_tham_gia' => (int) $byStatus->sum(),
                ];
            })->values()->all(),
            'ke_hoach_trong_thang_chua_thuc_hien' => $this->pendingPlans($group->id, $monthStart),
            'ke_hoach_thang_sau' => $this->pendingPlans($group->id, $nextStart),
        ];
    }

    /**
     * Số liệu cả năm học toàn trường (chỉ Quản trị viên).
     *
     * @return array<string, mixed>
     */
    public function forSchoolYear(string $academicYear): array
    {
        [$from, $to] = AcademicYear::dateRange($academicYear);

        $activities = Activity::query()->with('unionGroup')->whereBetween('start_time', [$from, $to])->get();
        $summary = $this->evaluation->summary($academicYear);
        $members = Member::query()->where('status', 'active')->selectRaw('union_group_id, COUNT(*) as total')->groupBy('union_group_id')->pluck('total', 'union_group_id');
        $byGroup = $activities->groupBy('union_group_id');

        $groups = $summary->map(function (array $row) use ($byGroup, $members) {
            /** @var UnionGroup $group */
            $group = $row['union_group'];
            $items = $byGroup->get($group->id) ?? collect();

            return [
                'to' => $group->name,
                'so_doan_vien' => (int) ($members[$group->id] ?? 0),
                'so_hoat_dong' => $items->count(),
                'da_hoan_thanh' => $items->where('status', Activity::STATUS_COMPLETED)->count(),
                'diem_tu_cham' => $row['self_total'],
                'diem_tham_dinh' => $row['verified_total'],
                'diem_thuong_tu_hoat_dong' => $row['activity_bonus'],
                'xep_loai' => $row['classification'],
            ];
        })->values();

        $total = $activities->count();
        $completed = $activities->where('status', Activity::STATUS_COMPLETED)->count();

        return [
            'truong' => 'Trường Đại học Thủ Dầu Một',
            'nam_hoc' => $academicYear,
            'tong_hop' => [
                'so_to_cong_doan' => $summary->count(),
                'so_doan_vien' => (int) $members->sum(),
                'tong_so_hoat_dong' => $total,
                'da_hoan_thanh' => $completed,
                'ty_le_hoan_thanh_phan_tram' => $total > 0 ? round($completed / $total * 100, 1) : 0,
                'hoat_dong_theo_loai' => $activities->groupBy(fn (Activity $a) => $a->activityType?->name ?? 'Khác')->map->count()->all(),
            ],
            'cac_to' => $groups->all(),
        ];
    }

    /**
     * Kế hoạch của tổ trong tháng chưa chuyển thành hoạt động và chưa hủy (tên gọn).
     *
     * @return list<string>
     */
    private function pendingPlans(int $groupId, Carbon $monthStart): array
    {
        return ActivityPlan::query()
            ->with('activity')
            ->where('host_union_group_id', $groupId)
            ->where('academic_year', AcademicYear::forDate($monthStart))
            ->where('month', $monthStart->month)
            ->get()
            ->filter(fn (ActivityPlan $plan) => in_array($plan->displayStatusKey(), [ActivityPlan::DISPLAY_PLANNED, ActivityPlan::DISPLAY_OVERDUE], true))
            ->map(fn (ActivityPlan $plan) => $plan->title)
            ->values()
            ->all();
    }
}
