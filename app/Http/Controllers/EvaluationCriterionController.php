<?php

namespace App\Http\Controllers;

use App\Http\Requests\Evaluation\StoreEvaluationCriterionRequest;
use App\Http\Requests\Evaluation\UpdateEvaluationCriterionRequest;
use App\Models\Department;
use App\Models\EvaluationCriterion;
use App\Services\EvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationCriterionController extends Controller
{
    public function index(Request $request, EvaluationService $evaluationService): View
    {
        $this->authorize('viewAny', EvaluationCriterion::class);

        $academicYear = $request->string('academic_year')->toString()
            ?: $evaluationService->academicYears()->first()
            ?: '2025-2026';

        $criteria = EvaluationCriterion::with('department')
            ->where('academic_year', $academicYear)
            ->orderByRaw("FIELD(group_label, 'I', 'II', 'III', 'thuong')")
            ->orderBy('order_no')
            ->get();

        $academicYears = $evaluationService->academicYears();
        $departments = Department::orderBy('name')->get();

        return view('evaluation.criteria.index', compact('criteria', 'academicYear', 'academicYears', 'departments'));
    }

    public function store(StoreEvaluationCriterionRequest $request): RedirectResponse
    {
        EvaluationCriterion::create($request->validated());

        return back()->with('success', 'Đã thêm tiêu chí thi đua thành công.');
    }

    public function update(UpdateEvaluationCriterionRequest $request, EvaluationCriterion $evaluationCriterion): RedirectResponse
    {
        $evaluationCriterion->update($request->validated());

        return back()->with('success', 'Đã cập nhật tiêu chí thi đua thành công.');
    }

    public function destroy(EvaluationCriterion $evaluationCriterion): RedirectResponse
    {
        $this->authorize('delete', $evaluationCriterion);

        if ($evaluationCriterion->scores()->exists()) {
            return back()->with('error', 'Không thể xóa vì đã có điểm chấm cho tiêu chí này.');
        }

        $evaluationCriterion->delete();

        return back()->with('success', 'Đã xóa tiêu chí thi đua thành công.');
    }
}
