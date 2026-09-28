<?php

namespace App\Http\Controllers;

use App\Models\EvaluationCriterion;
use App\Models\UnionGroup;
use App\Services\Ai\AiException;
use App\Services\Ai\EvidenceEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiEvaluationController extends Controller
{
    /**
     * Quản trị viên: AI gợi ý điểm thẩm định cho một tiêu chí của một tổ.
     */
    public function suggest(Request $request, EvidenceEvaluator $evaluator, EvaluationCriterion $evaluationCriterion, UnionGroup $unionGroup): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        return $this->run($request, $evaluator, $evaluationCriterion, $unionGroup, includeScore: true);
    }

    /**
     * Cán bộ (hoặc Quản trị viên): AI kiểm tra trước mức đủ minh chứng của tổ mình phụ trách, không trả điểm gợi ý.
     */
    public function precheck(Request $request, EvidenceEvaluator $evaluator, EvaluationCriterion $evaluationCriterion, UnionGroup $unionGroup): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->managesUnionGroup($unionGroup->id), 403);

        return $this->run($request, $evaluator, $evaluationCriterion, $unionGroup, includeScore: $user->isAdmin());
    }

    protected function run(Request $request, EvidenceEvaluator $evaluator, EvaluationCriterion $criterion, UnionGroup $group, bool $includeScore): JsonResponse
    {
        try {
            $suggestion = $evaluator->evaluate($criterion, $group, $request->user());
        } catch (AiException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['suggestion' => $suggestion->toPayload($includeScore)]);
    }
}
