<?php

namespace App\Http\Controllers;

use App\Exports\ActivitiesExport;
use App\Http\Requests\Activities\QuickUpdateActivityRequest;
use App\Http\Requests\Activities\StoreActivityRequest;
use App\Http\Requests\Activities\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $activities = $this->filteredQuery($request)->paginate(12)->withQueryString();

        $unionGroups = UnionGroup::orderBy('name')->get();
        $activityTypes = ActivityType::where('is_active', true)->orderBy('name')->get();

        return view('activities.index', compact('activities', 'unionGroups', 'activityTypes'));
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Activity::class);

        $activities = $this->filteredQuery($request)->get();

        return Excel::download(new ActivitiesExport($activities), 'danh-sach-hoat-dong.xlsx');
    }

    protected function filteredQuery(Request $request)
    {
        $query = Activity::query()
            ->with(['unionGroup', 'activityType'])
            ->withCount('members')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($qq) => $qq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($request->filled('union_group_id'), fn ($q) => $q->where('union_group_id', $request->integer('union_group_id')))
            ->when($request->filled('activity_type_id'), fn ($q) => $q->where('activity_type_id', $request->integer('activity_type_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('year'), fn ($q) => $q->whereYear('start_time', $request->integer('year')))
            ->when($request->filled('month'), fn ($q) => $q->whereMonth('start_time', $request->integer('month')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('start_time', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('start_time', '<=', $request->date('date_to')));

        $sort = $request->string('sort', 'start_time_desc');
        match ((string) $sort) {
            'start_time_asc' => $query->orderBy('start_time'),
            'name_asc' => $query->orderBy('name'),
            default => $query->orderByDesc('start_time'),
        };

        return $query;
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Activity::class);

        $unionGroups = $request->user()->isOfficer()
            ? $request->user()->managedUnionGroups()->orderBy('name')->get()
            : UnionGroup::orderBy('name')->get();
        $allUnionGroups = UnionGroup::orderBy('name')->get();
        $activityTypes = ActivityType::where('is_active', true)->orderBy('name')->get();
        $users = User::whereIn('role', ['admin', 'officer'])->orderBy('name')->get();

        return view('activities.create', compact('unionGroups', 'allUnionGroups', 'activityTypes', 'users'));
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $collaboratingGroups = $data['collaborating_groups'] ?? [];
        $fromActivityPlanId = $data['from_activity_plan_id'] ?? null;
        unset($data['collaborating_groups'], $data['from_activity_plan_id']);
        $data['counts_for_evaluation'] = $request->boolean('counts_for_evaluation');
        $data['created_by'] = $request->user()->id;

        $activity = Activity::create($data);
        $this->syncCollaboratingGroups($activity, $collaboratingGroups);

        if ($fromActivityPlanId) {
            \App\Models\ActivityPlan::whereKey($fromActivityPlanId)
                ->whereNull('activity_id')
                ->update(['activity_id' => $activity->id, 'status' => \App\Models\ActivityPlan::STATUS_DONE]);
        }

        $activity->statusHistories()->create([
            'status' => $activity->status,
            'progress' => $activity->progress,
            'note' => 'Tạo mới hoạt động.',
            'changed_by' => $request->user()->id,
        ]);

        return redirect()->route('activities.show', $activity)->with('success', 'Đã tạo hoạt động thành công.');
    }

    public function show(Activity $activity): View
    {
        $this->authorize('view', $activity);

        $activity->load(['unionGroup', 'activityType', 'responsibleUser', 'creator', 'statusHistories.changer', 'collaboratingGroups']);

        $participants = $activity->participants()->with('member')->latest()->paginate(10, ['*'], 'participants_page');
        $evidences = $activity->evidences()->with('uploader')->latest()->get();

        return view('activities.show', compact('activity', 'participants', 'evidences'));
    }

    public function edit(Activity $activity, Request $request): View
    {
        $this->authorize('update', $activity);

        $unionGroups = $request->user()->isOfficer()
            ? $request->user()->managedUnionGroups()->orderBy('name')->get()
            : UnionGroup::orderBy('name')->get();
        $allUnionGroups = UnionGroup::orderBy('name')->get();
        $activityTypes = ActivityType::where('is_active', true)->orderBy('name')->get();
        $users = User::whereIn('role', ['admin', 'officer'])->orderBy('name')->get();
        $activity->load('collaboratingGroups');

        return view('activities.edit', compact('activity', 'unionGroups', 'allUnionGroups', 'activityTypes', 'users'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $data = $request->validated();
        $collaboratingGroups = $data['collaborating_groups'] ?? [];
        unset($data['collaborating_groups']);
        $data['counts_for_evaluation'] = $request->boolean('counts_for_evaluation');
        $data['updated_by'] = $request->user()->id;

        $statusChanged = $activity->status !== $data['status'] || (int) $activity->progress !== (int) $data['progress'];

        $activity->update($data);
        $this->syncCollaboratingGroups($activity, $collaboratingGroups);

        if ($statusChanged) {
            $activity->statusHistories()->create([
                'status' => $activity->status,
                'progress' => $activity->progress,
                'note' => 'Cập nhật trạng thái/tiến độ hoạt động.',
                'changed_by' => $request->user()->id,
            ]);
        }

        return redirect()->route('activities.show', $activity)->with('success', 'Đã cập nhật hoạt động thành công.');
    }

    public function quickUpdate(QuickUpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $data = $request->validated();

        if (empty($data)) {
            return back();
        }

        $statusChanged = (isset($data['status']) && $data['status'] !== $activity->status)
            || (isset($data['progress']) && (int) $data['progress'] !== (int) $activity->progress);

        $data['updated_by'] = $request->user()->id;
        $activity->update($data);

        if ($statusChanged) {
            $activity->statusHistories()->create([
                'status' => $activity->status,
                'progress' => $activity->progress,
                'note' => 'Cập nhật nhanh trạng thái/tiến độ từ danh sách hoạt động.',
                'changed_by' => $request->user()->id,
            ]);
        }

        return back()->with('success', 'Đã cập nhật hoạt động '.$activity->code.'.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        foreach ($activity->evidences as $evidence) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($evidence->file_path);
        }

        $activity->delete();

        return redirect()->route('activities.index')->with('success', 'Đã xóa hoạt động thành công.');
    }

    protected function syncCollaboratingGroups(Activity $activity, array $collaboratingGroups): void
    {
        $sync = [];

        foreach ($collaboratingGroups['phoi_hop'] ?? [] as $unionGroupId) {
            $sync[$unionGroupId] = ['role' => Activity::ROLE_PHOI_HOP];
        }

        foreach ($collaboratingGroups['tham_gia'] ?? [] as $unionGroupId) {
            $sync[$unionGroupId] = ['role' => Activity::ROLE_THAM_GIA];
        }

        $activity->collaboratingGroups()->sync($sync);
    }
}
