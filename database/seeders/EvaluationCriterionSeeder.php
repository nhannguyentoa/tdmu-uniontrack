<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\EvaluationCriterion;
use Illuminate\Database\Seeder;

class EvaluationCriterionSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('seeders/data/evaluation_seed_data.php');
        $academicYear = $data['academic_year'];

        $departmentIdByName = Department::pluck('id', 'name');

        foreach ($data['criteria'] as $criterion) {
            EvaluationCriterion::updateOrCreate(
                ['academic_year' => $academicYear, 'order_no' => $criterion['order_no']],
                [
                    'group_label' => $criterion['group_label'],
                    'content' => $criterion['content'],
                    'max_score' => $criterion['max_score'],
                    'department_id' => $departmentIdByName[$criterion['department']] ?? null,
                ]
            );
        }

        // Tiêu chí điểm thưởng, không thuộc nhóm I/II/III và chưa gắn Ban thẩm định cụ thể.
        $bonus = $data['bonus'];
        $maxOrderNo = collect($data['criteria'])->max('order_no');

        EvaluationCriterion::updateOrCreate(
            ['academic_year' => $academicYear, 'order_no' => $maxOrderNo + 1],
            [
                'group_label' => $bonus['group_label'],
                'content' => $bonus['content'],
                'max_score' => $bonus['max_score'],
                'department_id' => null,
            ]
        );
    }
}
