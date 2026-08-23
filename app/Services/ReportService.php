<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    protected function baseQuery(?int $unionGroupId = null): Builder
    {
        return Activity::query()
            ->with(['unionGroup', 'activityType'])
            ->when($unionGroupId, fn (Builder $q) => $q->where('union_group_id', $unionGroupId));
    }

    public function monthlyReport(int $year, int $month, ?int $unionGroupId = null): array
    {
        $query = $this->baseQuery($unionGroupId)
            ->whereYear('start_time', $year)
            ->whereMonth('start_time', $month);

        return $this->buildReport($query, [
            'type' => 'month',
            'year' => $year,
            'month' => $month,
            'label' => "Tháng {$month}/{$year}",
        ]);
    }

    public function semesterReport(int $year, int $semester, ?int $unionGroupId = null): array
    {
        $months = $semester == 1 ? [1, 2, 3, 4, 5, 6] : [7, 8, 9, 10, 11, 12];

        $query = $this->baseQuery($unionGroupId)
            ->whereYear('start_time', $year)
            ->whereIn(DB::raw('MONTH(start_time)'), $months);

        return $this->buildReport($query, [
            'type' => 'semester',
            'year' => $year,
            'semester' => $semester,
            'label' => "Học kỳ {$semester} năm học {$year}",
        ]);
    }

    public function yearlyReport(int $year, ?int $unionGroupId = null): array
    {
        $query = $this->baseQuery($unionGroupId)->whereYear('start_time', $year);

        $report = $this->buildReport($query, [
            'type' => 'year',
            'year' => $year,
            'label' => "Năm {$year}",
        ]);

        $report['total_members'] = Member::when($unionGroupId, fn (Builder $q) => $q->where('union_group_id', $unionGroupId))
            ->where('status', 'active')
            ->count();

        $report['group_comparison'] = $this->baseQuery(null)
            ->whereYear('start_time', $year)
            ->join('union_groups', 'union_groups.id', '=', 'activities.union_group_id')
            ->select(
                'union_groups.id',
                'union_groups.name',
                DB::raw('COUNT(*) as total_activities'),
                DB::raw("SUM(CASE WHEN activities.status = 'completed' THEN 1 ELSE 0 END) as completed_activities")
            )
            ->groupBy('union_groups.id', 'union_groups.name')
            ->orderByDesc('total_activities')
            ->get();

        return $report;
    }

    protected function buildReport(Builder $query, array $meta): array
    {
        $activities = $query->orderBy('start_time')->get();
        $total = $activities->count();
        $completed = $activities->where('status', Activity::STATUS_COMPLETED)->count();

        $byGroup = $activities->groupBy(fn (Activity $a) => $a->unionGroup?->name ?? 'Không xác định')
            ->map->count();

        $byType = $activities->groupBy(fn (Activity $a) => $a->activityType?->name ?? 'Không xác định')
            ->map->count();

        $totalParticipants = ActivityParticipant::whereIn('activity_id', $activities->pluck('id'))->count();

        return array_merge($meta, [
            'total_activities' => $total,
            'completed_activities' => $completed,
            'completion_rate' => $total > 0 ? round($completed / $total * 100, 1) : 0,
            'total_participants' => $totalParticipants,
            'by_group' => $byGroup,
            'by_type' => $byType,
            'activities' => $activities,
        ]);
    }
}
