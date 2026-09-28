<?php

namespace App\Http\Controllers;

use App\Http\Requests\ActivityPlans\StoreActivityPlanRequest;
use App\Http\Requests\ActivityPlans\UpdateActivityPlanRequest;
use App\Models\Activity;
use App\Models\ActivityPlan;
use App\Models\ActivityType;
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

        $plans = ActivityPlan::with(['hostUnionGroup', 'department', 'activity', 'activityType'])
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

        return view('activity-plans.create', $this->formData());
    }

    public function store(StoreActivityPlanRequest $request): RedirectResponse
    {
        $data = $this->planData($request->validated(), $request);
        $collaboratingGroups = $data['collaborating_groups'] ?? [];
        unset($data['collaborating_groups'], $data['is_cancelled']);
        $data['created_by'] = $request->user()->id;

        $plan = ActivityPlan::create($data);
        $this->syncCollaboratingGroups($plan, $collaboratingGroups);

        return redirect()->route('activity-plans.index', ['academic_year' => $data['academic_year']])
            ->with('success', 'Đã thêm kế hoạch hoạt động thành công.');
    }

    public function edit(ActivityPlan $activityPlan): View
    {
        $this->authorize('update', $activityPlan);

        $activityPlan->load(['collaboratingGroups', 'activity']);

        return view('activity-plans.edit', $this->formData() + ['activityPlan' => $activityPlan]);
    }

    public function update(UpdateActivityPlanRequest $request, ActivityPlan $activityPlan): RedirectResponse
    {
        $data = $this->planData($request->validated(), $request);
        $collaboratingGroups = $data['collaborating_groups'] ?? [];
        unset($data['collaborating_groups'], $data['is_cancelled']);

        $activityPlan->update($data);
        $this->syncCollaboratingGroups($activityPlan, $collaboratingGroups);

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

    protected function formData(): array
    {
        return [
            'unionGroups' => UnionGroup::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
            'activityTypes' => ActivityType::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    /**
     * Trạng thái lưu trong CSDL chỉ là 'planned' hoặc 'cancelled'; điểm tối đa bị bỏ nếu không tính điểm thi đua.
     */
    protected function planData(array $data, Request $request): array
    {
        $data['status'] = $request->boolean('is_cancelled') ? ActivityPlan::STATUS_CANCELLED : ActivityPlan::STATUS_PLANNED;
        $data['counts_for_evaluation'] = $request->boolean('counts_for_evaluation');

        if (! $data['counts_for_evaluation']) {
            $data['evaluation_max_score'] = null;
        }

        return $data;
    }

    protected function syncCollaboratingGroups(ActivityPlan $plan, array $collaboratingGroups): void
    {
        $sync = [];

        foreach ($collaboratingGroups['phoi_hop'] ?? [] as $unionGroupId) {
            $sync[$unionGroupId] = ['role' => Activity::ROLE_PHOI_HOP];
        }

        foreach ($collaboratingGroups['tham_gia'] ?? [] as $unionGroupId) {
            $sync[$unionGroupId] = ['role' => Activity::ROLE_THAM_GIA];
        }

        $plan->collaboratingGroups()->sync($sync);
    }
}
