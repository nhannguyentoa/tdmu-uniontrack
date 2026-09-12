<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityPlans\StoreActivityPlanRequest;
use App\Http\Requests\ActivityPlans\UpdateActivityPlanRequest;
use App\Models\ActivityPlan;
use App\Models\Department;
use App\Models\UnionGroup;
use App\Support\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityPlanController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityPlan::class);

        $academicYear = $request->string('academic_year')->toString()
            ?: ActivityPlan::query()->orderByDesc('academic_year')->value('academic_year')
            ?: '2026-2027';

        $plans = ActivityPlan::with(['hostUnionGroup', 'department', 'activity'])
            ->where('academic_year', $academicYear)
            ->get()
            ->sortBy(fn (ActivityPlan $plan) => AcademicYear::monthOrder($plan->month))
            ->groupBy('month');

        $academicYears = ActivityPlan::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year');

        return view('activity-plans.index', compact('plans', 'academicYear', 'academicYears'));
    }

    public function create(): View
    {
        $this->authorize('create', ActivityPlan::class);

        $unionGroups = UnionGroup::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('activity-plans.create', compact('unionGroups', 'departments'));
    }

    public function store(StoreActivityPlanRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        ActivityPlan::create($data);

        return redirect()->route('activity-plans.index', ['academic_year' => $data['academic_year']])
            ->with('success', 'Đã thêm kế hoạch hoạt động thành công.');
    }

    public function edit(ActivityPlan $activityPlan): View
    {
        $this->authorize('update', $activityPlan);

        $unionGroups = UnionGroup::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();

        return view('activity-plans.edit', compact('activityPlan', 'unionGroups', 'departments'));
    }

    public function update(UpdateActivityPlanRequest $request, ActivityPlan $activityPlan): RedirectResponse
    {
        $activityPlan->update($request->validated());

        return redirect()->route('activity-plans.index', ['academic_year' => $activityPlan->academic_year])
            ->with('success', 'Đã cập nhật kế hoạch hoạt động thành công.');
    }

    public function destroy(ActivityPlan $activityPlan): RedirectResponse
    {
        $this->authorize('delete', $activityPlan);

        $academicYear = $activityPlan->academic_year;
        $activityPlan->delete();

        return redirect()->route('activity-plans.index', ['academic_year' => $academicYear])
            ->with('success', 'Đã xóa kế hoạch hoạt động thành công.');
    }

}
