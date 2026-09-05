<?php

namespace App\Services;

use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\UnionGroup;
use Illuminate\Support\Collection;

class EvaluationService
{
    public const CLASSIFICATION_EXCELLENT = 'Hoàn thành xuất sắc nhiệm vụ';
    public const CLASSIFICATION_GOOD = 'Hoàn thành tốt nhiệm vụ';
    public const CLASSIFICATION_NOT_MET = 'Chưa hoàn thành nhiệm vụ';
    public const CLASSIFICATION_PENDING = 'Chưa thẩm định xong';

    public function academicYears(): Collection
    {
        return EvaluationCriterion::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year');
    }

    public function classify(?float $score): string
    {
        if ($score === null) {
            return self::CLASSIFICATION_PENDING;
        }

        return match (true) {
            $score >= 80 => self::CLASSIFICATION_EXCELLENT,
            $score >= 50 => self::CLASSIFICATION_GOOD,
            default => self::CLASSIFICATION_NOT_MET,
        };
    }

    /**
     * Tổng hợp điểm tự chấm / thẩm định / xếp loại của tất cả tổ công đoàn cho một năm học,
     * theo đúng cách tính của "Bảng thẩm định Hội đồng" thực tế.
     */
    public function summary(string $academicYear): Collection
    {
        $criteria = EvaluationCriterion::where('academic_year', $academicYear)->get();
        $standardTotal = (float) $criteria->where('group_label', '<>', EvaluationCriterion::GROUP_BONUS)->sum('max_score');
        $criteriaIds = $criteria->pluck('id');

        $scoresByGroup = EvaluationScore::whereIn('evaluation_criterion_id', $criteriaIds)->get()->groupBy('union_group_id');

        return UnionGroup::where('status', 'active')->orderBy('name')->get()->map(function (UnionGroup $group) use ($criteria, $standardTotal, $scoresByGroup) {
            $scores = ($scoresByGroup->get($group->id) ?? collect())->keyBy('evaluation_criterion_id');

            $selfTotal = 0.0;
            $verifiedTotal = 0.0;
            $hasAnyScore = false;
            $verifiedCount = 0;

            foreach ($criteria as $criterion) {
                $score = $scores->get($criterion->id);
                if ($score && $score->self_score !== null) {
                    $selfTotal += (float) $score->self_score;
                    $hasAnyScore = true;
                }
                if ($score && $score->verified_score !== null) {
                    $verifiedTotal += (float) $score->verified_score;
                    $verifiedCount++;
                }
            }

            $finalScore = $verifiedCount > 0 ? round($verifiedTotal, 2) : null;

            return [
                'union_group' => $group,
                'standard_total' => $standardTotal,
                'self_total' => $hasAnyScore ? round($selfTotal, 2) : null,
                'verified_total' => $finalScore,
                'verified_count' => $verifiedCount,
                'criteria_count' => $criteria->count(),
                'diff' => ($hasAnyScore && $finalScore !== null) ? round($verifiedTotal - $selfTotal, 2) : null,
                'classification' => $this->classify($finalScore),
            ];
        });
    }
}
