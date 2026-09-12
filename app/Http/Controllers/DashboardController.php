<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Services\StatisticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected StatisticsService $statistics)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $unionGroupOptions = $user->isOfficer()
            ? $user->managedUnionGroups()->orderBy('name')->get()
            : UnionGroup::orderBy('name')->get();

        $unionGroupId = $request->integer('union_group_id') ?: null;
        if ($user->isOfficer() && ! $unionGroupId) {
            $unionGroupId = $unionGroupOptions->first()?->id;
        }

        $filters = array_filter([
            'union_group_id' => $unionGroupId,
            'activity_type_id' => $request->integer('activity_type_id') ?: null,
            'year' => $request->integer('year') ?: null,
            'month' => $request->integer('month') ?: null,
            'semester' => $request->integer('semester') ?: null,
        ]);

        $kpis = $this->statistics->kpis($filters);
        $activitiesByMonth = $this->statistics->activitiesByMonth($filters);
        $activitiesByGroup = $this->statistics->activitiesByGroup($filters);
        $activitiesByType = $this->statistics->activitiesByType($filters);
        $statusDistribution = $this->statistics->statusDistribution($filters);
        $participantsByMonth = $this->statistics->participantsByMonth($filters);

        $recentActivities = Activity::with(['unionGroup', 'activityType'])
            ->when($user->isOfficer(), fn ($q) => $q->whereIn('union_group_id', $unionGroupOptions->pluck('id')))
            ->latest('start_time')
            ->limit(6)
            ->get();

        $activityTypes = ActivityType::where('is_active', true)->orderBy('name')->get();

        return view('dashboard.index', compact(
            'kpis', 'activitiesByMonth', 'activitiesByGroup', 'activitiesByType',
            'statusDistribution', 'participantsByMonth', 'recentActivities',
            'unionGroupOptions', 'activityTypes', 'unionGroupId'
        ));
    }
}
