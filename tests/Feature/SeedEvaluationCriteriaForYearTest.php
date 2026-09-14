<?php

namespace Tests\Feature;

use App\Models\EvaluationCriterion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedEvaluationCriteriaForYearTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCriterion(array $overrides = []): EvaluationCriterion
    {
        return EvaluationCriterion::create(array_merge([
            'academic_year' => '2025-2026',
            'group_label' => 'I',
            'order_no' => 1,
            'content' => 'Tiêu chí kiểm thử',
            'max_score' => 5,
            'department_id' => null,
        ], $overrides));
    }

    public function test_copies_criteria_from_source_year_to_target_year(): void
    {
        $this->makeCriterion(['order_no' => 1, 'group_label' => 'I', 'content' => 'Tiêu chí A', 'max_score' => 5]);
        $this->makeCriterion(['order_no' => 2, 'group_label' => 'thuong', 'content' => 'Điểm thưởng', 'max_score' => 10]);

        $this->artisan('app:seed-evaluation-criteria', ['academic_year' => '2026-2027'])
            ->assertExitCode(0);

        $this->assertSame(2, EvaluationCriterion::where('academic_year', '2026-2027')->count());
        $this->assertDatabaseHas('evaluation_criteria', [
            'academic_year' => '2026-2027',
            'order_no' => 1,
            'content' => 'Tiêu chí A',
            'max_score' => 5,
        ]);
    }

    public function test_is_idempotent_and_skips_when_target_year_already_has_criteria(): void
    {
        $this->makeCriterion(['order_no' => 1]);
        $this->makeCriterion(['academic_year' => '2026-2027', 'order_no' => 1, 'content' => 'Đã có sẵn']);

        $this->artisan('app:seed-evaluation-criteria', ['academic_year' => '2026-2027'])
            ->assertExitCode(0);

        $this->assertSame(1, EvaluationCriterion::where('academic_year', '2026-2027')->count());
        $this->assertDatabaseHas('evaluation_criteria', [
            'academic_year' => '2026-2027',
            'content' => 'Đã có sẵn',
        ]);
    }

    public function test_still_copies_criteria_when_target_year_only_has_an_auto_created_bonus_criterion(): void
    {
        $this->makeCriterion(['order_no' => 1, 'group_label' => 'I', 'content' => 'Tiêu chí A', 'max_score' => 5]);
        $this->makeCriterion(['order_no' => 2, 'group_label' => 'thuong', 'content' => 'Điểm thưởng', 'max_score' => 10]);

        // Mô phỏng EvaluationService::bonusCriterion() tự tạo trước một dòng "Điểm thưởng" riêng lẻ
        // cho năm học đích, trước khi lệnh sao chép tiêu chí từng chạy.
        $this->makeCriterion(['academic_year' => '2026-2027', 'order_no' => 1, 'group_label' => 'thuong', 'content' => 'Điểm thưởng', 'max_score' => 10]);

        $this->artisan('app:seed-evaluation-criteria', ['academic_year' => '2026-2027'])
            ->assertExitCode(0);

        $this->assertSame(2, EvaluationCriterion::where('academic_year', '2026-2027')->count());
        $this->assertDatabaseHas('evaluation_criteria', [
            'academic_year' => '2026-2027',
            'order_no' => 1,
            'content' => 'Tiêu chí A',
            'group_label' => 'I',
        ]);
        $this->assertSame(
            1,
            EvaluationCriterion::where('academic_year', '2026-2027')->where('group_label', 'thuong')->count()
        );
    }

    public function test_fails_gracefully_when_no_source_year_has_criteria(): void
    {
        $this->artisan('app:seed-evaluation-criteria', ['academic_year' => '2026-2027'])
            ->assertExitCode(1);

        $this->assertSame(0, EvaluationCriterion::count());
    }
}
