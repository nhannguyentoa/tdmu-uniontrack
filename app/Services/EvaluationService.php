<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\UnionGroup;
use App\Support\AcademicYear;
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
     * Tiêu chí "Điểm thưởng" của một năm học — tự tạo nếu chưa có, vì điểm của tiêu chí này
     * từ nay được tính hoàn toàn tự động từ các hoạt động đánh dấu "tính điểm thi đua".
     */
    public function bonusCriterion(string $academicYear): EvaluationCriterion
    {
        return EvaluationCriterion::firstOrCreate(
            ['academic_year' => $academicYear, 'group_label' => EvaluationCriterion::GROUP_BONUS],
            [
                'order_no' => (EvaluationCriterion::where('academic_year', $academicYear)->max('order_no') ?? 0) + 1,
                'content' => 'Điểm thưởng — tự động tính từ các hoạt động đánh dấu "tính điểm thi đua" đã hoàn thành.',
                'max_score' => 10,
                'department_id' => null,
            ]
        );
    }

    /**
     * Các hoạt động "tính điểm thi đua" của một tổ trong một năm học (dựa theo thời gian bắt đầu).
     */
    public function activitiesForBonus(int $unionGroupId, string $academicYear): Collection
    {
        [$start, $end] = AcademicYear::dateRange($academicYear);

        return Activity::countsForEvaluation()
            ->where('union_group_id', $unionGroupId)
            ->whereBetween('start_time', [$start, $end])
            ->orderByDesc('start_time')
            ->get();
    }

    /**
     * Tổng điểm thưởng đã đạt được từ hoạt động (chưa giới hạn theo điểm chuẩn tiêu chí).
     */
    public function activityEarnedScore(int $unionGroupId, string $academicYear): float
    {
        return round(
            $this->activitiesForBonus($unionGroupId, $academicYear)
                ->sum(fn (Activity $activity) => $activity->earnedEvaluationScore()),
            2
        );
    }

    /**
     * Tổng hợp điểm tự chấm / thẩm định / xếp loại của tất cả tổ công đoàn cho một năm học,
     * theo đúng cách tính của "Bảng thẩm định Hội đồng" thực tế. Điểm thưởng luôn được tính
     * tự động từ hoạt động — không lấy giá trị tự chấm tay đã lưu cho tiêu chí này.
     */
    public function summary(string $academicYear): Collection
    {
        $bonusCriterion = $this->bonusCriterion($academicYear);
        $criteria = EvaluationCriterion::where('academic_year', $academicYear)->get();
        $standardTotal = (float) $criteria->where('group_label', '<>', EvaluationCriterion::GROUP_BONUS)->sum('max_score');
        $criteriaIds = $criteria->pluck('id');

        $scoresByGroup = EvaluationScore::whereIn('evaluation_criterion_id', $criteriaIds)->get()->groupBy('union_group_id');

        return UnionGroup::where('status', 'active')->orderBy('name')->get()->map(function (UnionGroup $group) use ($criteria, $standardTotal, $scoresByGroup, $bonusCriterion, $academicYear) {
            $scores = ($scoresByGroup->get($group->id) ?? collect())->keyBy('evaluation_criterion_id');
            $bonusEarned = min($this->activityEarnedScore($group->id, $academicYear), (float) $bonusCriterion->max_score);

            $selfTotal = 0.0;
            $verifiedTotal = 0.0;
            $hasAnyScore = false;
            $verifiedCount = 0;

            foreach ($criteria as $criterion) {
                $isBonus = $criterion->id === $bonusCriterion->id;
                $score = $scores->get($criterion->id);

                $selfValue = $isBonus ? $bonusEarned : ($score?->self_score !== null ? (float) $score->self_score : null);
                if ($selfValue !== null) {
                    $selfTotal += $selfValue;
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
                'activity_bonus' => $bonusEarned,
                'diff' => ($hasAnyScore && $finalScore !== null) ? round($verifiedTotal - $selfTotal, 2) : null,
                'classification' => $this->classify($finalScore),
            ];
        });
    }
}
