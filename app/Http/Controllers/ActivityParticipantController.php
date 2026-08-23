<?php

namespace App\Http\Controllers;

use App\Exports\ParticipantsExport;
use App\Http\Requests\Activities\StoreParticipantRequest;
use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ActivityParticipantController extends Controller
{
    /**
     * Trang chọn đoàn viên để thêm vào hoạt động (hỗ trợ tìm kiếm/lọc theo tổ).
     */
    public function create(Activity $activity, Request $request): View
    {
        $this->authorize('manageParticipants', $activity);

        $existingIds = $activity->participants()->pluck('member_id');

        $members = Member::query()
            ->with('unionGroup')
            ->where('status', 'active')
            ->whereNotIn('id', $existingIds)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(fn ($qq) => $qq->where('full_name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($request->filled('union_group_id'), fn ($q) => $q->where('union_group_id', $request->integer('union_group_id')))
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        return view('activities.participants.create', compact('activity', 'members'));
    }

    public function store(StoreParticipantRequest $request, Activity $activity): RedirectResponse
    {
        DB::transaction(function () use ($request, $activity) {
            foreach ($request->validated('member_ids') as $memberId) {
                $activity->participants()->create([
                    'member_id' => $memberId,
                    'role_note' => $request->input('role_note'),
                    'status' => $request->input('status'),
                    'registered_at' => now(),
                    'note' => $request->input('note'),
                ]);
            }
        });

        return redirect()->route('activities.show', $activity)->with('success', 'Đã thêm người tham gia thành công.');
    }

    public function export(Activity $activity)
    {
        $this->authorize('view', $activity);

        $participants = $activity->participants()->with(['member.unionGroup'])->get();

        return Excel::download(new ParticipantsExport($participants), "nguoi-tham-gia-{$activity->code}.xlsx");
    }

    public function destroy(Activity $activity, ActivityParticipant $participant): RedirectResponse
    {
        $this->authorize('manageParticipants', $activity);

        abort_unless($participant->activity_id === $activity->id, 404);

        $participant->delete();

        return redirect()->route('activities.show', $activity)->with('success', 'Đã xóa người tham gia khỏi hoạt động.');
    }
}
