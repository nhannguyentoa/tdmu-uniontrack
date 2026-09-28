<?php

namespace App\Services\Ai;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\ActivityPlan;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use App\Services\EvaluationService;
use App\Support\AcademicYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Các hàm tra cứu chỉ-đọc mà trợ lý hỏi đáp được phép gọi. Phạm vi dữ liệu luôn do máy chủ quyết định theo
 * người đang đăng nhập (Quản trị viên: mọi tổ; Cán bộ công đoàn: chỉ tổ mình phụ trách), mô hình AI không thể
 * mở rộng phạm vi. Kết quả chỉ là số liệu tổng hợp, không có họ tên đoàn viên.
 */
class DataTools
{
    public const MAX_ITEMS = 30;

    /** @var Collection<int, UnionGroup>|null */
    private ?Collection $allowed = null;

    public function __construct(private readonly User $user, private readonly EvaluationService $evaluation)
    {
    }

    /**
     * Khai báo hàm theo định dạng function calling của Gemini.
     *
     * @return list<array<string, mixed>>
     */
    public static function declarations(): array
    {
        $period = [
            'month' => ['type' => 'integer', 'description' => 'Tháng (1-12). Kèm year nếu không phải năm hiện tại.'],
            'year' => ['type' => 'integer', 'description' => 'Năm dương lịch của tháng, mặc định năm hiện tại.'],
            'academic_year' => ['type' => 'string', 'description' => 'Năm học dạng "2026-2027" (tháng 8 đến tháng 7 năm sau). Nếu không nêu tháng/năm học thì mặc định là năm học hiện tại.'],
        ];
        $group = ['union_group' => ['type' => 'string', 'description' => 'Tên (hoặc một phần tên) tổ công đoàn cần lọc. Bỏ trống để lấy tất cả các tổ được phép xem.']];

        return [
            [
                'name' => 'list_activities',
                'description' => 'Liệt kê hoạt động theo tổ, thời gian, trạng thái. Trường qua_han=true nghĩa là hoạt động chưa hoàn thành/chưa hủy nhưng đã quá ngày kết thúc (chậm tiến độ).',
                'parameters' => ['type' => 'object', 'properties' => $period + $group + [
                    'status' => ['type' => 'string', 'enum' => array_keys(Activity::STATUSES), 'description' => 'Lọc theo trạng thái hoạt động.'],
                    'overdue_only' => ['type' => 'boolean', 'description' => 'true để chỉ lấy hoạt động quá hạn/chậm tiến độ.'],
                    'limit' => ['type' => 'integer', 'description' => 'Số dòng tối đa (mặc định 30).'],
                ]],
            ],
            [
                'name' => 'group_overview',
                'description' => 'Tổng quan theo từng tổ trong kỳ: số đoàn viên, số hoạt động, số hoàn thành, số quá hạn, tỷ lệ hoàn thành. Dùng để so sánh các tổ.',
                'parameters' => ['type' => 'object', 'properties' => $period + $group],
            ],
            [
                'name' => 'evaluation_summary',
                'description' => 'Điểm thi đua theo năm học của từng tổ: điểm tự chấm, điểm thẩm định, điểm thưởng từ hoạt động, xếp loại. Dùng để so sánh điểm thưởng hoặc xếp loại các tổ.',
                'parameters' => ['type' => 'object', 'properties' => [
                    'academic_year' => $period['academic_year'],
                ] + $group],
            ],
            [
                'name' => 'participation_stats',
                'description' => 'Thống kê lượt người tham gia hoạt động của từng tổ (đã đăng ký, đã tham gia, vắng mặt, đã hủy) trong kỳ.',
                'parameters' => ['type' => 'object', 'properties' => $period + $group],
            ],
            [
                'name' => 'plan_status',
                'description' => 'Tình hình kế hoạch hoạt động theo năm học: số kế hoạch dự kiến, quá hạn, đã chuyển thành hoạt động, đã hủy, kèm tên các kế hoạch quá hạn.',
                'parameters' => ['type' => 'object', 'properties' => $period + $group],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    public function run(string $name, array $args): array
    {
        try {
            return match ($name) {
                'list_activities' => $this->listActivities($args),
                'group_overview' => $this->groupOverview($args),
                'evaluation_summary' => $this->evaluationSummary($args),
                'participation_stats' => $this->participationStats($args),
                'plan_status' => $this->planStatus($args),
                default => ['loi' => "Không có công cụ tên {$name}."],
            };
        } catch (ToolException $e) {
            return ['loi' => $e->getMessage()];
        }
    }

    /**
     * Các tổ mà người dùng hiện tại được phép xem.
     *
     * @return Collection<int, UnionGroup>
     */
    public function allowedGroups(): Collection
    {
        return $this->allowed ??= ($this->user->isAdmin()
            ? UnionGroup::query()->where('status', 'active')->orderBy('name')->get()
            : $this->user->managedUnionGroups()->orderBy('name')->get())->keyBy('id');
    }

    public function scopeDescription(): string
    {
        return $this->user->isAdmin()
            ? 'Toàn trường (mọi tổ công đoàn)'
            : 'Chỉ các tổ được phân công: '.($this->allowedGroups()->pluck('name')->implode(', ') ?: '(chưa được phân công tổ nào)');
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function listActivities(array $args): array
    {
        [$from, $to, $label] = $this->period($args);
        $groups = $this->groupsFor($args);

        $items = Activity::query()
            ->with(['unionGroup', 'activityType'])
            ->whereIn('union_group_id', $groups->pluck('id'))
            ->whereBetween('start_time', [$from, $to])
            ->when(! empty($args['status']) && isset(Activity::STATUSES[$args['status']]), fn ($q) => $q->where('status', $args['status']))
            ->orderBy('start_time')
            ->get();

        if (! empty($args['overdue_only'])) {
            $items = $items->filter(fn (Activity $a) => $a->isOverdue())->values();
        }

        $limit = max(1, min(self::MAX_ITEMS, (int) ($args['limit'] ?? self::MAX_ITEMS)));

        return [
            'ky' => $label,
            'pham_vi' => $this->scopeNames($groups, $args),
            'tong_so_khop' => $items->count(),
            'hoat_dong' => $items->take($limit)->map(fn (Activity $a) => [
                'ma' => $a->code,
                'ten' => $a->name,
                'to' => $a->unionGroup?->name,
                'loai' => $a->activityType?->name,
                'bat_dau' => $a->start_time?->format('d/m/Y'),
                'ket_thuc' => $a->end_time?->format('d/m/Y'),
                'trang_thai' => $a->statusLabel(),
                'tien_do_phan_tram' => (int) $a->progress,
                'qua_han' => $a->isOverdue(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function groupOverview(array $args): array
    {
        [$from, $to, $label] = $this->period($args);
        $groups = $this->groupsFor($args);

        $activities = Activity::query()
            ->whereIn('union_group_id', $groups->pluck('id'))
            ->whereBetween('start_time', [$from, $to])
            ->get()
            ->groupBy('union_group_id');
        $members = Member::query()->where('status', 'active')->whereIn('union_group_id', $groups->pluck('id'))
            ->selectRaw('union_group_id, COUNT(*) as total')->groupBy('union_group_id')->pluck('total', 'union_group_id');

        return [
            'ky' => $label,
            'pham_vi' => $this->scopeNames($groups, $args),
            'cac_to' => $groups->map(function (UnionGroup $g) use ($activities, $members) {
                $items = $activities->get($g->id) ?? collect();
                $done = $items->where('status', Activity::STATUS_COMPLETED)->count();

                return [
                    'to' => $g->name,
                    'so_doan_vien' => (int) ($members[$g->id] ?? 0),
                    'so_hoat_dong' => $items->count(),
                    'da_hoan_thanh' => $done,
                    'qua_han' => $items->filter(fn (Activity $a) => $a->isOverdue())->count(),
                    'ty_le_hoan_thanh_phan_tram' => $items->count() > 0 ? round($done / $items->count() * 100, 1) : null,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function evaluationSummary(array $args): array
    {
        $year = $this->academicYear($args);
        $groups = $this->groupsFor($args);
        $summary = $this->evaluation->summary($year)->filter(fn (array $row) => $groups->has($row['union_group']->id));

        return [
            'nam_hoc' => $year,
            'pham_vi' => $this->scopeNames($groups, $args),
            'cac_to' => $summary->map(fn (array $row) => [
                'to' => $row['union_group']->name,
                'diem_tu_cham' => $row['self_total'],
                'diem_tham_dinh' => $row['verified_total'],
                'diem_thuong_tu_hoat_dong' => $row['activity_bonus'],
                'xep_loai' => $row['classification'],
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function participationStats(array $args): array
    {
        [$from, $to, $label] = $this->period($args);
        $groups = $this->groupsFor($args);

        $rows = ActivityParticipant::query()
            ->join('activities', 'activities.id', '=', 'activity_participants.activity_id')
            ->whereNull('activities.deleted_at')
            ->whereIn('activities.union_group_id', $groups->pluck('id'))
            ->whereBetween('activities.start_time', [$from, $to])
            ->selectRaw('activities.union_group_id as gid, activity_participants.status as st, COUNT(*) as total')
            ->groupBy('activities.union_group_id', 'activity_participants.status')
            ->get()
            ->groupBy('gid');

        return [
            'ky' => $label,
            'pham_vi' => $this->scopeNames($groups, $args),
            'cac_to' => $groups->map(function (UnionGroup $g) use ($rows) {
                $by = ($rows->get($g->id) ?? collect())->pluck('total', 'st');

                return [
                    'to' => $g->name,
                    'da_dang_ky' => (int) ($by['registered'] ?? 0),
                    'da_tham_gia' => (int) ($by['attended'] ?? 0),
                    'vang_mat' => (int) ($by['absent'] ?? 0),
                    'da_huy' => (int) ($by['cancelled'] ?? 0),
                    'tong_luot' => (int) $by->sum(),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array<string, mixed>
     */
    private function planStatus(array $args): array
    {
        $year = $this->academicYear($args);
        $groups = $this->groupsFor($args);

        $plans = ActivityPlan::query()
            ->with(['activity', 'hostUnionGroup'])
            ->where('academic_year', $year)
            ->whereIn('host_union_group_id', $groups->pluck('id'))
            ->when(! empty($args['month']), fn ($q) => $q->where('month', (int) $args['month']))
            ->get();

        $keys = $plans->groupBy(fn (ActivityPlan $p) => $p->displayStatusKey());
        $overdue = $keys->get(ActivityPlan::DISPLAY_OVERDUE) ?? collect();

        return [
            'nam_hoc' => $year,
            'pham_vi' => $this->scopeNames($groups, $args),
            'tong_ke_hoach' => $plans->count(),
            'theo_trang_thai' => $keys->map->count()->all(),
            'ke_hoach_qua_han' => $overdue->take(self::MAX_ITEMS)->map(fn (ActivityPlan $p) => [
                'ten' => $p->title,
                'to' => $p->hostUnionGroup?->name,
                'thang' => $p->month,
            ])->values()->all(),
        ];
    }

    /**
     * Xác định các tổ cần truy vấn, luôn nằm trong phạm vi được phép.
     *
     * @param  array<string, mixed>  $args
     * @return Collection<int, UnionGroup>
     *
     * @throws ToolException
     */
    private function groupsFor(array $args): Collection
    {
        $allowed = $this->allowedGroups();
        $query = trim((string) ($args['union_group'] ?? ''));

        if ($query === '') {
            if ($allowed->isEmpty()) {
                throw new ToolException('Người dùng chưa được phân công tổ công đoàn nào nên không có dữ liệu để tra cứu.');
            }

            return $allowed;
        }

        $needle = MemberMatcher::normalize($query);
        $matches = $allowed->filter(fn (UnionGroup $g) => str_contains(MemberMatcher::normalize($g->name), $needle));

        if ($matches->isNotEmpty()) {
            return $matches;
        }

        $exists = UnionGroup::query()->get()->contains(fn (UnionGroup $g) => str_contains(MemberMatcher::normalize($g->name), $needle));

        throw new ToolException($exists
            ? 'Bạn không có quyền xem dữ liệu của tổ này. Chỉ được xem: '.($allowed->pluck('name')->implode(', ') ?: '(không có tổ nào)').'.'
            : "Không tìm thấy tổ công đoàn nào có tên gần giống \"{$query}\".");
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    private function period(array $args): array
    {
        if (! empty($args['month'])) {
            $month = max(1, min(12, (int) $args['month']));
            $year = (int) ($args['year'] ?? now()->year);
            $from = Carbon::create($year, $month, 1)->startOfMonth();

            return [$from, $from->copy()->endOfMonth(), "Tháng {$month}/{$year}"];
        }

        $academic = $this->academicYear($args);
        [$from, $to] = AcademicYear::dateRange($academic);

        return [$from, $to, "Năm học {$academic}"];
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function academicYear(array $args): string
    {
        $year = (string) ($args['academic_year'] ?? '');

        return preg_match('/^\d{4}-\d{4}$/', $year) ? $year : AcademicYear::forDate(now());
    }

    /**
     * @param  Collection<int, UnionGroup>  $groups
     * @param  array<string, mixed>  $args
     */
    private function scopeNames(Collection $groups, array $args): string
    {
        return empty($args['union_group']) && $this->user->isAdmin() ? 'Tất cả các tổ' : $groups->pluck('name')->implode(', ');
    }
}
