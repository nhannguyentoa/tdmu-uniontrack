<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnionGroups\StoreUnionGroupRequest;
use App\Http\Requests\UnionGroups\UpdateUnionGroupRequest;
use App\Models\Activity;
use App\Models\UnionGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnionGroupController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', UnionGroup::class);

        $user = $request->user();

        $query = UnionGroup::query()
            ->withCount(['members', 'activities'])
            ->when($user->isOfficer(), fn ($q) => $q->whereIn('id', $user->managedUnionGroups()->pluck('union_groups.id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($qq) => $qq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name');

        $unionGroups = $query->paginate(10)->withQueryString();

        return view('union-groups.index', compact('unionGroups'));
    }

    public function create(): View
    {
        $this->authorize('create', UnionGroup::class);

        return view('union-groups.create');
    }

    public function store(StoreUnionGroupRequest $request): RedirectResponse
    {
        UnionGroup::create($request->validated());

        return redirect()->route('union-groups.index')->with('success', 'Đã thêm tổ công đoàn thành công.');
    }

    public function show(UnionGroup $unionGroup): View
    {
        $this->authorize('view', $unionGroup);

        $stats = [
            'total_members' => $unionGroup->members()->count(),
            'total_activities' => $unionGroup->activities()->count(),
            'completed' => $unionGroup->activities()->where('status', Activity::STATUS_COMPLETED)->count(),
            'in_progress' => $unionGroup->activities()->whereIn('status', [Activity::STATUS_IN_PROGRESS, Activity::STATUS_PREPARING])->count(),
            'not_started' => $unionGroup->activities()->where('status', Activity::STATUS_NOT_STARTED)->count(),
            'total_participants' => \App\Models\ActivityParticipant::whereIn('activity_id', $unionGroup->activities()->pluck('id'))->count(),
        ];

        $recentActivities = $unionGroup->activities()->with('activityType')->latest('start_time')->limit(5)->get();
        $members = $unionGroup->members()->latest()->limit(10)->get();

        return view('union-groups.show', compact('unionGroup', 'stats', 'recentActivities', 'members'));
    }

    public function edit(UnionGroup $unionGroup): View
    {
        $this->authorize('update', $unionGroup);

        return view('union-groups.edit', compact('unionGroup'));
    }

    public function update(UpdateUnionGroupRequest $request, UnionGroup $unionGroup): RedirectResponse
    {
        $unionGroup->update($request->validated());

        return redirect()->route('union-groups.index')->with('success', 'Đã cập nhật tổ công đoàn thành công.');
    }

    public function destroy(UnionGroup $unionGroup): RedirectResponse
    {
        $this->authorize('delete', $unionGroup);

        if ($unionGroup->members()->exists() || $unionGroup->activities()->exists()) {
            return redirect()->route('union-groups.index')
                ->with('error', 'Không thể xóa tổ công đoàn vì vẫn còn đoàn viên hoặc hoạt động liên quan. Vui lòng chuyển/xóa dữ liệu liên quan trước.');
        }

        $unionGroup->delete();

        return redirect()->route('union-groups.index')->with('success', 'Đã xóa tổ công đoàn thành công.');
    }
}
