<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Member;
use App\Models\UnionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Áp bộ lọc dùng chung (tổ công đoàn, loại hoạt động, năm, tháng) lên query hoạt động.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['union_group_id'] ?? null, fn (Builder $q, $v) => $q->where('union_group_id', $v))
            ->when($filters['activity_type_id'] ?? null, fn (Builder $q, $v) => $q->where('activity_type_id', $v))
            ->when($filters['year'] ?? null, fn (Builder $q, $v) => $q->whereYear('start_time', $v))
            ->when($filters['month'] ?? null, fn (Builder $q, $v) => $q->whereMonth('start_time', $v))
            ->when($filters['semester'] ?? null, function (Builder $q, $v) {
                $months = $v == 1 ? [1, 2, 3, 4, 5, 6] : [7, 8, 9, 10, 11, 12];
                $q->whereIn(DB::raw('MONTH(start_time)'), $months);
            });
    }

    public function kpis(array $filters = []): array
    {
        $activities = $this->applyFilters(Activity::query(), $filters);

        $statusCounts = (clone $activities)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalParticipants = ActivityParticipant::whereIn('activity_id', (clone $activities)->pluck('id'))->count();

        return [
            'total_union_groups' => UnionGroup::count(),
            'total_members' => Member::where('status', 'active')->count(),
            'total_activities' => (clone $activities)->count(),
            'completed_activities' => $statusCounts->get(Activity::STATUS_COMPLETED, 0),
            'in_progress_activities' => $statusCounts->get(Activity::STATUS_IN_PROGRESS, 0)
                + $statusCounts->get(Activity::STATUS_PREPARING, 0),
            'not_started_activities' => $statusCounts->get(Activity::STATUS_NOT_STARTED, 0),
            'cancelled_activities' => $statusCounts->get(Activity::STATUS_CANCELLED, 0),
            'total_participants' => $totalParticipants,
        ];
    }

    public function activitiesByMonth(array $filters = []): array
    {
        $year = $filters['year'] ?? now()->year;
        $rows = $this->applyFilters(Activity::query(), array_merge($filters, ['year' => $year]))
            ->select(DB::raw('MONTH(start_time) as month'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('MONTH(start_time)'))
            ->pluck('total', 'month');

        $labels = [];
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = 'Tháng '.$m;
            $data[] = (int) ($rows[$m] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function activitiesByGroup(array $filters = []): array
    {
        $rows = $this->applyFilters(Activity::query(), $filters)
            ->join('union_groups', 'union_groups.id', '=', 'activities.union_group_id')
            ->select('union_groups.name as name', DB::raw('COUNT(*) as total'))
            ->groupBy('union_groups.id', 'union_groups.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    public function activitiesByType(array $filters = []): array
    {
        $rows = $this->applyFilters(Activity::query(), $filters)
            ->join('activity_types', 'activity_types.id', '=', 'activities.activity_type_id')
            ->select('activity_types.name as name', DB::raw('COUNT(*) as total'))
            ->groupBy('activity_types.id', 'activity_types.name')
            ->orderByDesc('total')
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'data' => $rows->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    public function statusDistribution(array $filters = []): array
    {
        $rows = $this->applyFilters(Activity::query(), $filters)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = [];
        $data = [];
        foreach (Activity::STATUSES as $key => $label) {
            $labels[] = $label;
            $data[] = (int) ($rows[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function participantsByMonth(array $filters = []): array
    {
        $year = $filters['year'] ?? now()->year;

        $rows = ActivityParticipant::query()
            ->join('activities', 'activities.id', '=', 'activity_participants.activity_id')
            ->whereYear('activities.start_time', $year)
            ->when($filters['union_group_id'] ?? null, fn (Builder $q, $v) => $q->where('activities.union_group_id', $v))
            ->select(DB::raw('MONTH(activities.start_time) as month'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('MONTH(activities.start_time)'))
            ->pluck('total', 'month');

        $labels = [];
        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = 'Tháng '.$m;
            $data[] = (int) ($rows[$m] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
