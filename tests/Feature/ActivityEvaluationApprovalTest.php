<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use App\Services\EvaluationService;
use App\Support\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityEvaluationApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompletedScoredActivity(array $overrides = []): Activity
    {
        $group = $overrides['union_group_id'] ?? UnionGroup::factory()->create()->id;

        return Activity::factory()->create(array_merge([
            'union_group_id' => $group,
            'activity_type_id' => ActivityType::factory(),
            'counts_for_evaluation' => true,
            'evaluation_max_score' => 10,
            'status' => Activity::STATUS_COMPLETED,
            'progress' => 100,
        ], $overrides));
    }

    public function test_unapproved_completed_activity_earns_no_score(): void
    {
        $activity = $this->makeCompletedScoredActivity();

        $this->assertFalse($activity->isEvaluationApproved());
        $this->assertSame(0.0, $activity->earnedEvaluationScore());
    }

    public function test_admin_can_approve_and_score_becomes_active(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = $this->makeCompletedScoredActivity();

        $response = $this->actingAs($admin)->patch("/activities/{$activity->id}/toggle-evaluation-approval");

        $response->assertRedirect();
        $activity->refresh();
        $this->assertTrue($activity->isEvaluationApproved());
        $this->assertSame($admin->id, $activity->evaluation_approved_by);
        $this->assertSame(10.0, $activity->earnedEvaluationScore());
    }

    public function test_admin_can_unapprove_again(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = $this->makeCompletedScoredActivity(['evaluation_approved_at' => now(), 'evaluation_approved_by' => $admin->id]);

        $response = $this->actingAs($admin)->patch("/activities/{$activity->id}/toggle-evaluation-approval");

        $response->assertRedirect();
        $activity->refresh();
        $this->assertFalse($activity->isEvaluationApproved());
        $this->assertSame(0.0, $activity->earnedEvaluationScore());
    }

    public function test_officer_cannot_approve(): void
    {
        $officer = User::factory()->officer()->create();
        $activity = $this->makeCompletedScoredActivity();

        $response = $this->actingAs($officer)->patch("/activities/{$activity->id}/toggle-evaluation-approval");

        $response->assertForbidden();
        $this->assertFalse($activity->fresh()->isEvaluationApproved());
    }

    public function test_cannot_approve_activity_that_is_not_completed(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = $this->makeCompletedScoredActivity(['status' => Activity::STATUS_IN_PROGRESS]);

        $response = $this->actingAs($admin)->patch("/activities/{$activity->id}/toggle-evaluation-approval");

        $response->assertRedirect();
        $this->assertFalse($activity->fresh()->isEvaluationApproved());
    }

    public function test_cannot_approve_activity_that_does_not_count_for_evaluation(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = $this->makeCompletedScoredActivity(['counts_for_evaluation' => false]);

        $response = $this->actingAs($admin)->patch("/activities/{$activity->id}/toggle-evaluation-approval");

        $response->assertRedirect();
        $this->assertFalse($activity->fresh()->isEvaluationApproved());
    }

    public function test_approval_button_only_visible_to_admin_on_completed_scored_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);
        $activity = $this->makeCompletedScoredActivity(['union_group_id' => $group->id]);

        $this->actingAs($admin)->get("/activities/{$activity->id}")->assertOk()->assertSee('Duyệt điểm thi đua');
        $this->actingAs($officer)->get("/activities/{$activity->id}")->assertOk()->assertDontSee('Duyệt điểm thi đua');
    }

    public function test_current_academic_year_is_always_selectable_even_without_criteria(): void
    {
        $currentYear = AcademicYear::forDate(now());

        $years = app(EvaluationService::class)->academicYears();

        $this->assertTrue($years->contains($currentYear));
    }

    public function test_default_academic_year_prefers_the_year_with_existing_criteria(): void
    {
        \App\Models\EvaluationCriterion::create([
            'academic_year' => '2025-2026',
            'group_label' => 'I',
            'order_no' => 1,
            'content' => 'Tiêu chí kiểm thử',
            'max_score' => 5,
            'department_id' => null,
        ]);

        $currentYear = AcademicYear::forDate(now());
        $default = app(EvaluationService::class)->defaultAcademicYear();

        $this->assertSame('2025-2026', $default);
        $this->assertNotSame($currentYear, $default);
    }

    public function test_default_academic_year_falls_back_to_current_year_when_no_criteria_exist(): void
    {
        $currentYear = AcademicYear::forDate(now());

        $default = app(EvaluationService::class)->defaultAcademicYear();

        $this->assertSame($currentYear, $default);
    }
}
