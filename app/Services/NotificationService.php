<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityPlan;
use App\Models\ActivityStatusHistory;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\UnionGroup;
use App\Models\User;
use App\Support\AcademicYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Thông báo trên thanh trên: tính trực tiếp từ dữ liệu hiện tại (không lưu), nên việc nào xử lý xong sẽ tự biến mất.
 * Phạm vi theo vai trò: Quản trị viên xem toàn trường; Cán bộ công đoàn chỉ xem các tổ mình phụ trách.
 */
class NotificationService
{
    public const DANGER = 'danger';
    public const WARNING = 'warning';
    public const INFO = 'info';

    /** Số ngày báo trước cho "sắp trễ hạn", "sắp diễn ra" và thời gian giữ tin "mới cập nhật". */
    public const DAYS_AHEAD = 3;

    /** Số thông báo tối đa mỗi loại (để trang không quá nặng). */
    public const MAX_PER_TYPE = 100;

    /** Khóa loại => [nhãn, mức độ], theo thứ tự hiển thị (khẩn trước). */
    public const TYPES = [
        'overdue' => ['Hoạt động quá hạn', self::DANGER],
        'plan_overdue' => ['Kế hoạch quá hạn', self::DANGER],
        'due_soon' => ['Hoạt động sắp trễ hạn', self::WARNING],
        'needs_approval' => ['Hoạt động cần duyệt điểm', self::WARNING],
        'missing_evidence' => ['Hoạt động thiếu minh chứng', self::WARNING],
        'plan_due' => ['Kế hoạch tháng này chưa thực hiện', self::WARNING],
        'upcoming' => ['Hoạt động sắp diễn ra, chưa có người tham gia', self::INFO],
        'evaluation' => ['Chấm điểm thi đua', self::INFO],
        'recent' => ['Hoạt động mới tạo/cập nhật', self::INFO],
    ];

    /**
     * @return list<array{type: string, label: string, level: string, count: int, items: list<array<string, mixed>>}>
     */
    public function groups(User $user): array
    {
        $groupIds = $this->scope($user);
        if ($groupIds === []) {
            return [];
        }

        $items = collect()
            ->merge($this->overdue($groupIds))
            ->merge($this->dueSoon($groupIds))
            ->merge($this->upcoming($groupIds))
            ->merge($this->missingEvidence($groupIds))
            ->merge($user->isAdmin() ? $this->needsApproval() : [])
            ->merge($this->plans($groupIds))
            ->merge($this->evaluation($user, $groupIds))
            ->merge($user->isAdmin() ? $this->recent($user) : [])
            ->groupBy('type');

        $groups = [];
        foreach (self::TYPES as $type => [$label, $level]) {
            $list = $items->get($type);
            if ($list === null || $list->isEmpty()) {
                continue;
            }
            $groups[] = ['type' => $type, 'label' => $label, 'level' => $level, 'count' => $list->count(), 'items' => $list->values()->all()];
        }

        return $groups;
    }

    /**
     * @param  list<array{count: int}>  $groups
     */
    public static function total(array $groups): int
    {
        return (int) array_sum(array_column($groups, 'count'));
    }

    /**
     * Các tổ được phép xem: null = tất cả (Quản trị viên); mảng id cho cán bộ (rỗng nếu chưa được phân công).
     *
     * @return list<int>|null
     */
    private function scope(User $user): ?array
    {
        if ($user->isAdmin()) {
            return null;
        }

        return $user->managedUnionGroups()->pluck('union_groups.id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  list<int>|null  $groupIds
     */
    private function activities(?array $groupIds): Builder
    {
        return Activity::query()
            ->with('unionGroup')
            ->when($groupIds !== null, fn (Builder $q) => $q->whereIn('union_group_id', $groupIds));
    }

    private function open(Builder $query): Builder
    {
        return $query->whereNotIn('status', [Activity::STATUS_COMPLETED, Activity::STATUS_CANCELLED]);
    }

    /**
     * @param  list<int>|null  $groupIds
     * @return Collection<int, array<string, mixed>>
     */
    private function overdue(?array $groupIds): Collection
    {
        return $this->open($this->activities($groupIds))
            ->where('end_time', '<', now())
            ->where('end_time', '>=', now()->subYear())
            ->orderBy('end_time')
            ->limit(self::MAX_PER_TYPE)
            ->get()
            ->map(fn (Activity $a) => $this->activityItem('overdue', $a,
                'Quá hạn '.max(1, (int) ceil($a->end_time->diffInDays(now(), true))).' ngày (hạn '.$a->end_time->format('d/m/Y').'), tiến độ '.(int) $a->progress.'%', $a->end_time));
    }

    /**
     * @param  list<int>|null  $groupIds
     * @return Collection<int, array<string, mixed>>
     */
    private function dueSoon(?array $groupIds): Collection
    {
        return $this->open($this->activities($groupIds))
            ->whereBetween('end_time', [now(), now()->addDays(self::DAYS_AHEAD)])
            ->orderBy('end_time')
            ->limit(self::MAX_PER_TYPE)
            ->get()
            ->map(fn (Activity $a) => $this->activityItem('due_soon', $a,
                'Hạn '.$a->end_time->format('H:i d/m/Y').', tiến độ mới '.(int) $a->progress.'%', $a->end_time));
    }

    /**
     * @param  list<int>|null  $groupIds
     * @return Collection<int, array<string, mixed>>
     */
    private function upcoming(?array $groupIds): Collection
    {
        return $this->open($this->activities($groupIds))
            ->whereBetween('start_time', [now(), now()->addDays(self::DAYS_AHEAD)])
            ->doesntHave('participants')
            ->orderBy('start_time')
            ->limit(self::MAX_PER_TYPE)
            ->get()
            ->map(fn (Activity $a) => $this->activityItem('upcoming', $a,
                'Bắt đầu '.$a->start_time->format('H:i d/m/Y').', chưa ghi nhận người tham gia', $a->start_time));
    }

    /**
     * @param  list<int>|null  $groupIds
     * @return Collection<int, array<string, mixed>>
     */
    private function missingEvidence(?array $groupIds): Collection
    {
        return $this->activities($groupIds)
            ->where('status', Activity::STATUS_COMPLETED)
            ->where('counts_for_evaluation', true)
            ->doesntHave('evidences')
            ->orderByDesc('end_time')
            ->limit(self::MAX_PER_TYPE)
            ->get()
            ->map(fn (Activity $a) => $this->activityItem('missing_evidence', $a, 'Đã hoàn thành, tính điểm thi đua nhưng chưa có minh chứng đính kèm', $a->end_time));
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function needsApproval(): Collection
    {
        return $this->activities(null)
            ->where('status', Activity::STATUS_COMPLETED)
            ->where('counts_for_evaluation', true)
            ->whereNull('evaluation_approved_at')
            ->orderByDesc('end_time')
            ->limit(self::MAX_PER_TYPE)
            ->get()
            ->map(fn (Activity $a) => $this->activityItem('needs_approval', $a,
                'Chờ duyệt tối đa '.rtrim(rtrim((string) $a->evaluation_max_score, '0'), '.').'đ, tiến độ '.(int) $a->progress.'%', $a->end_time));
    }

    /**
     * @param  list<int>|null  $groupIds
     * @return Collection<int, array<string, mixed>>
     */
    private function plans(?array $groupIds): Collection
    {
        $year = AcademicYear::forDate(now());

        return ActivityPlan::query()
            ->with(['activity', 'hostUnionGroup'])
            ->where('academic_year', $year)
            ->when($groupIds !== null, fn (Builder $q) => $q->whereIn('host_union_group_id', $groupIds))
            ->get()
            ->map(function (ActivityPlan $plan) {
                $status = $plan->displayStatusKey();
                $isThisMonth = $plan->monthStart()->isSameMonth(now());

                if ($status === ActivityPlan::DISPLAY_OVERDUE) {
                    $type = 'plan_overdue';
                    $message = 'Kế hoạch tháng '.$plan->month.' chưa chuyển thành hoạt động';
                } elseif ($status === ActivityPlan::DISPLAY_PLANNED && $isThisMonth) {
                    $type = 'plan_due';
                    $message = 'Kế hoạch tháng này, hết tháng '.$plan->monthEnd()->format('d/m/Y').' mà chưa chuyển thành hoạt động';
                } else {
                    return null;
                }

                return [
                    'type' => $type,
                    'title' => $plan->title,
                    'message' => trim(($plan->hostUnionGroup?->name ? $plan->hostUnionGroup->name.' · ' : '').$message),
                    'url' => route('activity-plans.index', ['academic_year' => $plan->academic_year]),
                    'at' => $plan->monthEnd(),
                ];
            })
            ->filter()
            ->sortBy('at')
            ->take(self::MAX_PER_TYPE * 2)
            ->values();
    }

    /**
     * Tự chấm chưa xong (cán bộ) / thẩm định chưa xong (Quản trị viên) của năm học hiện tại.
     *
     * @param  list<int>|null  $groupIds
     * @return Collection<int, array<string, mixed>>
     */
    private function evaluation(User $user, ?array $groupIds): Collection
    {
        $year = AcademicYear::forDate(now());
        $criteriaIds = EvaluationCriterion::query()
            ->where('academic_year', $year)
            ->where('group_label', '<>', EvaluationCriterion::GROUP_BONUS)
            ->pluck('id');

        if ($criteriaIds->isEmpty()) {
            return collect();
        }

        $total = $criteriaIds->count();
        $scores = EvaluationScore::query()->whereIn('evaluation_criterion_id', $criteriaIds)->get()->groupBy('union_group_id');

        if ($user->isAdmin()) {
            $groups = UnionGroup::query()->where('status', 'active')->get();
            $pending = $groups->filter(fn (UnionGroup $g) => ($scores->get($g->id) ?? collect())->whereNotNull('verified_score')->count() < $total);

            return $pending->isEmpty() ? collect() : collect([[
                'type' => 'evaluation',
                'title' => "Thẩm định thi đua năm học {$year}",
                'message' => $pending->count().'/'.$groups->count().' tổ chưa được thẩm định đủ '.$total.' tiêu chí',
                'url' => route('evaluation.verify.edit'),
                'at' => null,
            ]]);
        }

        return UnionGroup::query()->whereIn('id', $groupIds ?? [])->get()
            ->map(function (UnionGroup $g) use ($scores, $total, $year) {
                $done = ($scores->get($g->id) ?? collect())->whereNotNull('self_score')->count();

                return $done >= $total ? null : [
                    'type' => 'evaluation',
                    'title' => "Tự chấm điểm năm học {$year}: {$g->name}",
                    'message' => 'Còn '.($total - $done).'/'.$total.' tiêu chí chưa tự chấm',
                    'url' => route('evaluation.self.edit', $g),
                    'at' => null,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Quản trị viên: hoạt động do người khác vừa tạo hoặc đổi trạng thái/tiến độ trong vài ngày gần đây.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function recent(User $admin): Collection
    {
        return ActivityStatusHistory::query()
            ->with(['activity.unionGroup', 'changer'])
            ->where('created_at', '>=', now()->subDays(self::DAYS_AHEAD))
            ->where(fn (Builder $q) => $q->whereNull('changed_by')->orWhere('changed_by', '<>', $admin->id))
            ->whereHas('activity')
            ->latest()
            ->limit(self::MAX_PER_TYPE)
            ->get()
            ->map(fn (ActivityStatusHistory $h) => $this->activityItem('recent', $h->activity,
                ($h->changer?->name ?: 'Cán bộ').' cập nhật: '.(Activity::STATUSES[$h->status] ?? $h->status).', tiến độ '.(int) $h->progress.'% · '.$h->created_at->diffForHumans(), $h->created_at));
    }

    /**
     * @return array<string, mixed>
     */
    private function activityItem(string $type, Activity $activity, string $message, ?Carbon $at): array
    {
        return [
            'type' => $type,
            'title' => $activity->name,
            'message' => trim(($activity->unionGroup?->name ? $activity->unionGroup->name.' · ' : '').$message),
            'url' => route('activities.show', $activity),
            'at' => $at,
        ];
    }
}
