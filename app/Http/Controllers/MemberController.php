<?php

namespace App\Http\Controllers;

use App\Exports\MembersExport;
use App\Http\Requests\Members\StoreMemberRequest;
use App\Http\Requests\Members\UpdateMemberRequest;
use App\Models\Member;
use App\Models\UnionGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $members = $this->filteredQuery($request)->paginate(15)->withQueryString();

        $unionGroups = $request->user()->isOfficer()
            ? $request->user()->managedUnionGroups()->orderBy('name')->get()
            : UnionGroup::orderBy('name')->get();

        return view('members.index', compact('members', 'unionGroups'));
    }

    public function export(Request $request)
    {
        $this->authorize('viewAny', Member::class);

        $members = $this->filteredQuery($request)->get();

        return Excel::download(new MembersExport($members), 'danh-sach-doan-vien.xlsx');
    }

    protected function filteredQuery(Request $request)
    {
        $user = $request->user();

        return Member::query()
            ->with('unionGroup')
            ->when($user->isOfficer(), fn ($q) => $q->whereIn('union_group_id', $user->managedUnionGroups()->pluck('union_groups.id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($qq) => $qq->where('full_name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($request->filled('union_group_id'), fn ($q) => $q->where('union_group_id', $request->integer('union_group_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('full_name');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Member::class);

        $unionGroups = $request->user()->isOfficer()
            ? $request->user()->managedUnionGroups()->orderBy('name')->get()
            : UnionGroup::orderBy('name')->get();

        return view('members.create', compact('unionGroups'));
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            $data['avatar_path'] = $request->file('avatar')->store('members', 'public');
        }

        Member::create($data);

        return redirect()->route('members.index')->with('success', 'Đã thêm đoàn viên thành công.');
    }

    public function show(Member $member): View
    {
        $this->authorize('view', $member);

        $member->load('unionGroup');
        $activities = $member->activities()->with('activityType')->latest('start_time')->paginate(10);

        return view('members.show', compact('member', 'activities'));
    }

    public function edit(Member $member, Request $request): View
    {
        $this->authorize('update', $member);

        $unionGroups = $request->user()->isOfficer()
            ? $request->user()->managedUnionGroups()->orderBy('name')->get()
            : UnionGroup::orderBy('name')->get();

        return view('members.edit', compact('member', 'unionGroups'));
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($member->avatar_path) {
                Storage::disk('public')->delete($member->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('members', 'public');
        }

        $member->update($data);

        return redirect()->route('members.index')->with('success', 'Đã cập nhật đoàn viên thành công.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        if ($member->activityParticipants()->exists()) {
            return redirect()->route('members.index')
                ->with('error', 'Không thể xóa đoàn viên vì đã tham gia hoạt động. Vui lòng gỡ khỏi các hoạt động trước.');
        }

        if ($member->avatar_path) {
            Storage::disk('public')->delete($member->avatar_path);
        }

        $member->delete();

        return redirect()->route('members.index')->with('success', 'Đã xóa đoàn viên thành công.');
    }
}
