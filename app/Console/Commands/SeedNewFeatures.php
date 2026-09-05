<?php

namespace App\Console\Commands;

use App\Models\Department;
use Database\Seeders\ActivityPlanSeeder;
use Database\Seeders\DepartmentSeeder;
use Database\Seeders\EvaluationCriterionSeeder;
use Database\Seeders\EvaluationScoreSeeder;
use Illuminate\Console\Command;

class SeedNewFeatures extends Command
{
    protected $signature = 'app:seed-new-features';

    protected $description = 'Seed du lieu cho cac tinh nang moi (Ban chuyen mon, tieu chi thi dua, ke hoach hoat dong). Chi chay 1 lan duy nhat.';

    public function handle(): int
    {
        if (Department::count() > 0) {
            $this->info('Da seed du lieu tinh nang moi truoc do, bo qua.');

            return self::SUCCESS;
        }

        $this->call(DepartmentSeeder::class);
        $this->call(EvaluationCriterionSeeder::class);
        $this->call(EvaluationScoreSeeder::class);
        $this->call(ActivityPlanSeeder::class);

        $this->info('Da dong bo du lieu cho cac tinh nang moi.');

        return self::SUCCESS;
    }
}
