<?php

namespace Database\Seeders;

use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Database\Seeder;

class EvaluationScoreSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/evaluation_seed_data.php');
        $academicYear = $data['academic_year'];

        $criteriaByOrderNo = EvaluationCriterion::where('academic_year', $academicYear)->get()->keyBy('order_no');
        $groupsByCode = UnionGroup::whereIn('code', array_column(UnionGroupSeeder::realGroups(), 'code'))->get()->keyBy('code');
        $admin = User::where('role', User::ROLE_ADMIN)->first();

        foreach ($data['scores'] as $orderNo => $groupScores) {
            $criterion = $criteriaByOrderNo->get($orderNo);
            if (! $criterion) {
                continue;
            }

            foreach ($groupScores as $groupCode => [$self, $verified]) {
                $unionGroup = $groupsByCode->get($groupCode);
                if (! $unionGroup) {
                    continue;
                }

                EvaluationScore::updateOrCreate(
                    ['evaluation_criterion_id' => $criterion->id, 'union_group_id' => $unionGroup->id],
                    [
                        'self_score' => $self,
                        'self_scored_by' => $self !== null ? $admin?->id : null,
                        'self_scored_at' => $self !== null ? now() : null,
                        'verified_score' => $verified,
                        'verified_by' => $verified !== null ? $admin?->id : null,
                        'verified_at' => $verified !== null ? now() : null,
                    ]
                );
            }
        }
    }
}
