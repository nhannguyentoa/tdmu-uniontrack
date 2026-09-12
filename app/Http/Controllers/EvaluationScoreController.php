<?php

namespace App\Http\Controllers;

use App\Exports\EvaluationReportExport;
use App\Models\Department;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\UnionGroup;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class EvaluationScoreController extends Controller
{
    public function editSelf(Request $request, UnionGroup $unionGroup, EvaluationService $evaluationService): View
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->managesUnionGroup($unionGroup->id), 403);

        $academicYear = $request->string('academic_year')->toString()
            ?: $evaluationService->academicYears()->first()
            ?: '2025-2026';

        $bonusCriterion = $evaluationService->bonusCriterion($academicYear);

        $criteria = EvaluationCriterion::with('department')
            ->where('academic_year', $academicYear)
            ->orderByRaw("FIELD(group_label, 'I', 'II', 'III', 'thuong')")
            ->orderBy('order_no')
            ->get();

        $scores = EvaluationScore::where('union_group_id', $unionGroup->id)
            ->whereIn('evaluation_criterion_id', $criteria->pluck('id'))
            ->get()
            ->keyBy('evaluation_criterion_id');

        $academicYears = $evaluationService->academicYears();
        $bonusActivities = $evaluationService->activitiesForBonus($unionGroup->id, $academicYear);
        $bonusEarned = min($evaluationService->activityEarnedScore($unionGroup->id, $academicYear), (float) $bonusCriterion->max_score);

        return view('evaluation.self-edit', compact(
            'unionGroup', 'criteria', 'scores', 'academicYear', 'academicYears',
            'bonusCriterion', 'bonusActivities', 'bonusEarned'
        ));
    }

    public function updateSelf(Request $request, UnionGroup $unionGroup, EvaluationService $evaluationService): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->managesUnionGroup($unionGroup->id), 403);

        $data = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'scores' => ['array'],
            'scores.*.self_score' => ['nullable', 'numeric', 'min:0'],
            'scores.*.self_note' => ['nullable', 'string'],
        ]);

        // Điểm thưởng luôn được tính tự động từ hoạt động — bỏ qua giá trị người dùng gửi lên (nếu có).
        $bonusCriterionId = $evaluationService->bonusCriterion($data['academic_year'])->id;
        unset($data['scores'][$bonusCriterionId]);

        DB::transaction(function () use ($data, $unionGroup, $request) {
            foreach ($data['scores'] ?? [] as $criterionId => $row) {
                EvaluationScore::updateOrCreate(
                    ['evaluation_criterion_id' => $criterionId, 'union_group_id' => $unionGroup->id],
                    [
                        'self_score' => $row['self_score'] ?? null,
                        'self_note' => $row['self_note'] ?? null,
                        'self_scored_by' => $request->user()->id,
                        'self_scored_at' => now(),
                    ]
                );
            }
        });

        return back()->with('success', 'Đã lưu điểm tự chấm thành công.');
    }

    public function editVerify(Request $request, EvaluationService $evaluationService): View
    {
        abort_unless($request->user()->isAdmin(), 403);

        $academicYear = $request->string('academic_year')->toString()
            ?: $evaluationService->academicYears()->first()
            ?: '2025-2026';

        $departments = Department::orderBy('name')->get();
        $departmentId = $request->integer('department_id') ?: $departments->first()?->id;

        $criteria = EvaluationCriterion::where('academic_year', $academicYear)
            ->where('department_id', $departmentId)
            ->orderBy('order_no')
            ->get();

        $unionGroups = UnionGroup::where('status', 'active')->orderBy('name')->get();

        $scores = EvaluationScore::whereIn('evaluation_criterion_id', $criteria->pluck('id'))
            ->get()
            ->groupBy('evaluation_criterion_id')
            ->map(fn ($rows) => $rows->keyBy('union_group_id'));

        $academicYears = $evaluationService->academicYears();

        return view('evaluation.verify-edit', compact(
            'criteria', 'unionGroups', 'scores', 'departments', 'departmentId', 'academicYear', 'academicYears'
        ));
    }

    public function updateVerify(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $data = $request->validate([
            'academic_year' => ['required', 'string', 'max:20'],
            'department_id' => ['required', 'exists:departments,id'],
            'scores' => ['array'],
            'scores.*.*.verified_score' => ['nullable', 'numeric', 'min:0'],
            'scores.*.*.verified_note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $request) {
            foreach ($data['scores'] ?? [] as $criterionId => $groupRows) {
                foreach ($groupRows as $unionGroupId => $row) {
                    EvaluationScore::updateOrCreate(
                        ['evaluation_criterion_id' => $criterionId, 'union_group_id' => $unionGroupId],
                        [
                            'verified_score' => $row['verified_score'] ?? null,
                            'verified_note' => $row['verified_note'] ?? null,
                            'verified_by' => $request->user()->id,
                            'verified_at' => now(),
                        ]
                    );
                }
            }
        });

        return back()->with('success', 'Đã lưu điểm thẩm định thành công.');
    }

    public function report(Request $request, EvaluationService $evaluationService): View
    {
        $academicYear = $request->string('academic_year')->toString()
            ?: $evaluationService->academicYears()->first()
            ?: '2025-2026';

        $summary = $evaluationService->summary($academicYear);
        $academicYears = $evaluationService->academicYears();

        return view('evaluation.report', compact('summary', 'academicYear', 'academicYears'));
    }

    public function exportExcel(Request $request, EvaluationService $evaluationService)
    {
        $academicYear = $request->string('academic_year')->toString()
            ?: $evaluationService->academicYears()->first()
            ?: '2025-2026';

        $summary = $evaluationService->summary($academicYear);

        return Excel::download(new EvaluationReportExport($summary, $academicYear), "bao-cao-hoi-dong-thi-dua-{$academicYear}.xlsx");
    }
}
