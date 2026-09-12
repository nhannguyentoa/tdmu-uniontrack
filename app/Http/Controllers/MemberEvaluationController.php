<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\MemberEvaluation;
use App\Models\UnionGroup;
use App\Support\AcademicYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MemberEvaluationController extends Controller
{
    public function edit(Request $request, UnionGroup $unionGroup): View
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->managesUnionGroup($unionGroup->id), 403);

        $academicYear = $request->string('academic_year')->toString() ?: AcademicYear::forDate(now());
        $academicYears = $this->academicYearOptions($academicYear);

        $members = $unionGroup->members()->where('status', 'active')->orderBy('full_name')->get();

        [$start, $end] = AcademicYear::dateRange($academicYear);
        $groupActivityIds = Activity::where('union_group_id', $unionGroup->id)
            ->whereBetween('start_time', [$start, $end])
            ->pluck('id');

        $attendedCounts = ActivityParticipant::whereIn('activity_id', $groupActivityIds)
            ->where('status', 'attended')
            ->whereIn('member_id', $members->pluck('id'))
            ->selectRaw('member_id, COUNT(*) as cnt')
            ->groupBy('member_id')
            ->pluck('cnt', 'member_id');

        $evaluations = MemberEvaluation::where('academic_year', $academicYear)
            ->whereIn('member_id', $members->pluck('id'))
            ->get()
            ->keyBy('member_id');

        $totalGroupActivities = $groupActivityIds->count();

        return view('evaluation.members-edit', compact(
            'unionGroup', 'members', 'evaluations', 'academicYear', 'academicYears',
            'attendedCounts', 'totalGroupActivities'
        ));
    }

    public function update(Request $request, UnionGroup $unionGroup): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->managesUnionGroup($unionGroup->id), 403);

        $data = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'evaluations' => ['array'],
            'evaluations.*.classification' => ['nullable', 'in:'.implode(',', array_keys(MemberEvaluation::CLASSIFICATIONS))],
            'evaluations.*.score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'evaluations.*.note' => ['nullable', 'string'],
        ]);

        $memberIds = $unionGroup->members()->pluck('id')->all();

        DB::transaction(function () use ($data, $memberIds, $request) {
            foreach ($data['evaluations'] ?? [] as $memberId => $row) {
                if (! in_array((int) $memberId, $memberIds, true)) {
                    continue;
                }

                MemberEvaluation::updateOrCreate(
                    ['member_id' => $memberId, 'academic_year' => $data['academic_year']],
                    [
                        'classification' => $row['classification'] ?? null,
                        'score' => $row['score'] ?? null,
                        'note' => $row['note'] ?? null,
                        'scored_by' => $request->user()->id,
                        'scored_at' => now(),
                    ]
                );
            }
        });

        return back()->with('success', 'Đã lưu xếp loại thi đua đoàn viên thành công.');
    }

    protected function academicYearOptions(string $current): array
    {
        $startYear = (int) strtok($current, '-');

        return [
            ($startYear - 1).'-'.$startYear,
            $current,
            ($startYear + 1).'-'.($startYear + 2),
        ];
    }
}
