<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            UnionGroupSeeder::class,
            DepartmentSeeder::class,
            ActivityTypeSeeder::class,
            UserSeeder::class,
            MemberSeeder::class,
            ActivitySeeder::class,
            EvaluationCriterionSeeder::class,
            EvaluationScoreSeeder::class,
            ActivityPlanSeeder::class,
        ]);
    }
}
