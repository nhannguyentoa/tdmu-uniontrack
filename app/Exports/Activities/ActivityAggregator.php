<?php

namespace App\Exports\Activities;

use App\Models\Activity;
use App\Models\UnionGroup;
use Illuminate\Support\Collection;

/**
 * Gom và tính số liệu cho file Excel "Danh sách hoạt động" bằng PHP (không dựa vào công thức so khớp tên của Excel).
 * Hai hoạt động được coi là cùng một hoạt động khi tên giống nhau sau khi bỏ khoảng trắng thừa, không phân biệt hoa/thường.
 */
class ActivityAggregator
{
    /** @var Collection<int, Activity> */
    protected Collection $activities;

    /**
     * @param  Collection<int, Activity>  $activities  các hoạt động đã lọc, đã nạp sẵn unionGroup và activityType
     * @param  Collection<int, UnionGroup>  $groups  các tổ hiển thị thành cột/dòng, đã sắp theo tên
     */
    public function __construct(Collection $activities, protected Collection $groups)
    {
        $order = $groups->pluck('id')->flip();

        $this->activities = $activities
            ->sortBy([
                fn (Activity $a, Activity $b) => ($order[$a->union_group_id] ?? PHP_INT_MAX) <=> ($order[$b->union_group_id] ?? PHP_INT_MAX),
                fn (Activity $a, Activity $b) => $a->start_time <=> $b->start_time,
                fn (Activity $a, Activity $b) => $a->id <=> $b->id,
            ])
            ->values();
    }

    public static function normalizeName(?string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim((string) $name)) ?? '');
    }

    /** @return Collection<int, UnionGroup> */
    public function groups(): Collection
    {
        return $this->groups;
    }

    /** Hoạt động đã sắp theo tổ (thứ tự cột tổ) rồi theo thời gian bắt đầu. @return Collection<int, Activity> */
    public function details(): Collection
    {
        return $this->activities;
    }

    /**
     * Danh sách theo tổ: trong mỗi tổ gom các hoạt động cùng tên, sắp theo ngày bắt đầu sớm nhất.
     *
     * @return list<array{group: UnionGroup, rows: list<array<string, mixed>>, total: array<string, mixed>}>
     */
    public function byGroup(): array
    {
        $result = [];

        foreach ($this->groups as $group) {
            $items = $this->activities->where('union_group_id', $group->id);

            if ($items->isEmpty()) {
                continue;
            }

            $rows = $items
                ->groupBy(fn (Activity $a) => self::normalizeName($a->name))
                ->map(fn (Collection $same) => $this->summarize($same))
                ->sortBy([['start', 'asc'], ['name', 'asc']])
                ->values()
                ->all();

            $result[] = ['group' => $group, 'rows' => $rows, 'total' => $this->summarize($items)];
        }

        return $result;
    }

    /**
     * Bảng 2 chiều: mỗi hàng là một hoạt động (gom theo tên trên toàn trường), mỗi ô là số lần và điểm đạt của một tổ.
     *
     * @return list<array{name: string, cells: array<int, array{count: int, earned: float}>, count: int, groups: int, earned: float}>
     */
    public function matrix(): array
    {
        return $this->activities
            ->groupBy(fn (Activity $a) => self::normalizeName($a->name))
            ->sortKeys(SORT_STRING)
            ->map(function (Collection $same) {
                $cells = $same->groupBy('union_group_id')->map(fn (Collection $g) => [
                    'count' => $g->count(),
                    'earned' => round($g->sum(fn (Activity $a) => $a->earnedEvaluationScore()), 2),
                ])->all();

                return [
                    'name' => $this->displayName($same),
                    'cells' => $cells,
                    'count' => $same->count(),
                    'groups' => count($cells),
                    'earned' => round($same->sum(fn (Activity $a) => $a->earnedEvaluationScore()), 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Mỗi tổ một dòng: số lượt, số hoạt động sau khi gom, đếm theo trạng thái, số lượng và điểm.
     *
     * @return list<array<string, mixed>>
     */
    public function summary(): array
    {
        return $this->groups->map(function (UnionGroup $group) {
            $items = $this->activities->where('union_group_id', $group->id);

            return [
                'group' => $group,
                'total' => $items->count(),
                'distinct' => $items->map(fn (Activity $a) => self::normalizeName($a->name))->unique()->count(),
                'statuses' => collect(Activity::STATUSES)->map(fn ($label, $status) => $items->where('status', $status)->count())->all(),
                'expected' => (int) $items->sum('expected_quantity'),
                'actual' => (int) $items->sum('actual_quantity'),
                'earned' => round($items->sum(fn (Activity $a) => $a->earnedEvaluationScore()), 2),
            ];
        })->values()->all();
    }

    /**
     * Các điểm bất thường của một hoạt động để người xem kiểm tra lại dữ liệu.
     *
     * @return list<string>
     */
    public static function warnings(Activity $activity): array
    {
        $warnings = [];
        $progress = $activity->progress;

        if ($progress === null) {
            $warnings[] = 'chưa nhập tiến độ';
        } elseif ($activity->status === Activity::STATUS_COMPLETED && $progress < 100) {
            $warnings[] = "'Đã hoàn thành' nhưng tiến độ {$progress}%";
        } elseif ($activity->status === Activity::STATUS_CANCELLED && $progress > 0) {
            $warnings[] = "'Đã hủy' nhưng tiến độ {$progress}%";
        }

        if ($activity->counts_for_evaluation && $activity->status === Activity::STATUS_COMPLETED
            && $activity->evaluation_max_score && ! $activity->isEvaluationApproved()) {
            $warnings[] = 'Chờ duyệt điểm';
        }

        return $warnings;
    }

    /**
     * @param  Collection<int, Activity>  $items
     * @return array<string, mixed>
     */
    protected function summarize(Collection $items): array
    {
        $progress = $items->pluck('progress')->filter(fn ($p) => $p !== null);
        $scored = $items->filter(fn (Activity $a) => $a->counts_for_evaluation);

        return [
            'name' => $this->displayName($items),
            'count' => $items->count(),
            'codes' => $items->pluck('code')->all(),
            'types' => $items->map(fn (Activity $a) => $a->activityType?->name)->filter()->unique()->values()->all(),
            'start' => $items->min('start_time'),
            'end' => $items->max('end_time') ?? $items->max('start_time'),
            'completed' => $items->where('status', Activity::STATUS_COMPLETED)->count(),
            'progress' => $progress->isEmpty() ? null : round($progress->avg() / 100, 4),
            'expected' => (int) $items->sum('expected_quantity'),
            'actual' => (int) $items->sum('actual_quantity'),
            'max_score' => round((float) $scored->sum('evaluation_max_score'), 2),
            'earned' => round($items->sum(fn (Activity $a) => $a->earnedEvaluationScore()), 2),
        ];
    }

    /** Tên hiển thị = tên của lần tổ chức sớm nhất. */
    protected function displayName(Collection $items): string
    {
        $first = $items->sortBy([fn ($a, $b) => $a->start_time <=> $b->start_time, fn ($a, $b) => $a->id <=> $b->id])->first();

        return preg_replace('/\s+/u', ' ', trim((string) $first->name));
    }
}
