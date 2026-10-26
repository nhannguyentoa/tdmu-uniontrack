<?php

namespace App\Http\Controllers;

use App\Exports\ActivitiesExport;
use App\Exports\UnionActivityReportExport;
use App\Http\Requests\Activities\QuickUpdateActivityRequest;
use App\Http\Requests\Activities\StoreActivityRequest;
use App\Http\Requests\Activities\UpdateActivityRequest;
use App\Models\Activity;
use App\Models\ActivityPlan;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use App\Services\EvaluationService;
use App\Support\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ActivityController extends Controller
{
    public function index(Request $request, EvaluationService $evaluation): View
    {
        $this->authorize('viewAny', Activity::class);

        $activities = $this->filteredQuery($request)->paginate(12)->withQueryString();

        $unionGroups = UnionGroup::orderBy('name')->get();
        $activityTypes = ActivityType::where('is_active', true)->orderBy('name')->get();
        $academicYears = $request->user()->isAdmin() ? $evaluation->academicYears() : collect();

        return view('activities.index', compact('activities', 'unionGroups', 'activityTypes', 'academicYears'));
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Activity::class);

        $activities = $this->filteredQuery($request)->get();

        // Cột/dòng tổ: chỉ tổ đang lọc; nếu không lọc thì mọi tổ đang hoạt động và các tổ có hoạt động trong danh sách.
        $groups = $request->filled('union_group_id')
            ? UnionGroup::whereKey($request->integer('union_group_id'))->get()
            : UnionGroup::where('status', 'active')
                ->orWhereIn('id', $activities->pluck('union_group_id')->unique())
                ->orderBy('name')->get();

        // Giờ xuất hiển thị theo giờ Việt Nam (config app.timezone hiện đang là UTC).
        $exportedAt = now('Asia/Ho_Chi_Minh');
        $meta = [
            'exported_at' => $exportedAt,
            'exported_by' => $request->user()->name,
            'filters' => $this->describeFilters($request),
        ];

        return Excel::download(new ActivitiesExport($activities, $groups, $meta), 'danh-sach-hoat-dong_'.$exportedAt->format('Y-m-d_Hi').'.xlsx');
    }

    /** Mô tả bộ lọc đang áp dụng để ghi vào file xuất, vd "Tổ: Công đoàn 1 · Trạng thái: Đã hủy". */
    protected function describeFilters(Request $request): string
    {
        $parts = array_filter([
            $request->filled('search') ? 'Từ khóa: "'.$request->string('search').'"' : null,
            $request->filled('union_group_id') ? 'Tổ: '.(UnionGroup::find($request->integer('union_group_id'))?->name ?? '?') : null,
            $request->filled('activity_type_id') ? 'Loại: '.(ActivityType::find($request->integer('activity_type_id'))?->name ?? '?') : null,
            $request->filled('status') ? 'Trạng thái: '.(Activity::STATUSES[(string) $request->string('status')] ?? $request->string('status')) : null,
            $request->filled('year') ? 'Năm: '.$request->integer('year') : null,
            $request->filled('month') ? 'Tháng: '.$request->integer('month') : null,
            $request->filled('date_from') ? 'Từ ngày: '.$request->date('date_from')?->format('d/m/Y') : null,
            $request->filled('date_to') ? 'Đến ngày: '.$request->date('date_to')?->format('d/m/Y') : null,
        ]);

        return $parts === [] ? 'Tất cả' : implode(' · ', $parts);
    }

    /**
     * Xuất toàn bộ hoạt động của một năm học theo đúng mẫu báo cáo nộp lên Liên đoàn Lao động
     * (gom nhóm theo Loại hoạt động). Chỉ Quản trị viên dùng được, vì phạm vi là toàn trường.
     */
    public function exportUnionReport(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $request->validate(['academic_year' => ['required', 'regex:/^\d{4}-\d{4}$/']]);
        $academicYear = $request->string('academic_year')->toString();
        [$from, $to] = AcademicYear::dateRange($academicYear);

        $activityTypes = ActivityType::orderBy('name')->get();

        $activities = Activity::query()
            ->with('evidences')
            ->whereBetween('start_time', [$from, $to])
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (Activity $a) => $a->activity_type_id ?? 'none');

        $groups = $activityTypes
            ->mapWithKeys(fn (ActivityType $type) => [$type->name => $activities->get($type->id, collect())])
            ->filter(fn ($items) => $items->isNotEmpty());

        if ($unclassified = $activities->get('none')) {
            $groups['Chưa phân loại'] = $unclassified;
        }

        $fileName = "hoat-dong-cong-doan-{$academicYear}.xlsx";

        return Excel::download(new UnionActivityReportExport($academicYear, $groups), $fileName);
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

    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create', Activity::class);

        $plan = null;
        if ($request->filled('from_activity_plan_id')) {
            $plan = ActivityPlan::with('collaboratingGroups')->find($request->integer('from_activity_plan_id'));

            if (! $plan || $plan->activity_id !== null || $plan->isCancelled()) {
                return redirect()->route('activity-plans.index')
                    ->with('error', 'Kế hoạch này không thể chuyển thành hoạt động (không tồn tại, đã được chuyển hoặc đã hủy).');
            }
        }

        $suggestedCode = Activity::suggestNextCode($plan?->monthStart()->year);

        $unionGroups = $request->user()->isOfficer()
            ? $request->user()->managedUnionGroups()->orderBy('name')->get()
            : UnionGroup::orderBy('name')->get();
        $allUnionGroups = UnionGroup::orderBy('name')->get();
        $activityTypes = ActivityType::where('is_active', true)->orderBy('name')->get();
        $users = User::whereIn('role', ['admin', 'officer'])->orderBy('name')->get();

        return view('activities.create', compact('unionGroups', 'allUnionGroups', 'activityTypes', 'users', 'plan', 'suggestedCode'));
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
            // Chỉ gắn kế hoạch vào hoạt động; trạng thái kế hoạch tự đồng bộ theo hoạt động (ActivityPlan::displayStatusKey).
            ActivityPlan::whereKey($fromActivityPlanId)
                ->whereNull('activity_id')
                ->update(['activity_id' => $activity->id]);
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

    public function toggleEvaluationApproval(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        if (! $activity->canToggleEvaluationApproval()) {
            return back()->with('error', 'Chỉ có thể duyệt điểm thi đua cho hoạt động có tính điểm và đã hoàn thành.');
        }

        if ($activity->isEvaluationApproved()) {
            $activity->update(['evaluation_approved_at' => null, 'evaluation_approved_by' => null]);

            return back()->with('success', 'Đã hủy duyệt điểm thi đua của hoạt động '.$activity->code.'.');
        }

        $activity->update(['evaluation_approved_at' => now(), 'evaluation_approved_by' => $request->user()->id]);

        return back()->with('success', 'Đã duyệt điểm thi đua của hoạt động '.$activity->code.'.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        foreach ($activity->evidences as $evidence) {
            Storage::disk('public')->delete($evidence->file_path);
        }

        // Kế hoạch đã chuyển thành hoạt động này quay lại trạng thái chưa chuyển (Dự kiến / Quá hạn).
        ActivityPlan::where('activity_id', $activity->id)->update(['activity_id' => null]);

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
