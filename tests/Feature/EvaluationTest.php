<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Department;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\UnionGroup;
use App\Models\User;
use App\Services\EvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationTest extends TestCase
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

    public function test_report_page_renders(): void
    {
        $admin = User::factory()->admin()->create();
        UnionGroup::factory()->create(['status' => 'active']);
        $this->makeCriterion();

        $this->actingAs($admin)->get('/evaluation/report?academic_year=2025-2026')->assertOk();
    }

    public function test_criteria_index_page_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeCriterion();

        $this->actingAs($admin)->get('/evaluation/criteria?academic_year=2025-2026')->assertOk();
    }

    public function test_verify_edit_page_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::create(['code' => 'TEST2', 'name' => 'Ban kiểm thử 2']);
        $this->makeCriterion(['department_id' => $department->id]);
        UnionGroup::factory()->create(['status' => 'active']);

        $this->actingAs($admin)->get("/evaluation/verify?academic_year=2025-2026&department_id={$department->id}")->assertOk();
    }

    public function test_self_edit_page_renders(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);
        $this->makeCriterion();

        $this->actingAs($officer)->get("/evaluation/{$group->id}/self?academic_year=2025-2026")->assertOk();
    }

    public function test_admin_can_create_evaluation_criterion(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/evaluation/criteria', [
            'academic_year' => '2025-2026',
            'group_label' => 'I',
            'order_no' => 1,
            'content' => 'Tiêu chí kiểm thử',
            'max_score' => 5,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('evaluation_criteria', ['content' => 'Tiêu chí kiểm thử']);
    }

    public function test_officer_can_self_score_own_group(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);
        $criterion = $this->makeCriterion();

        $response = $this->actingAs($officer)->put("/evaluation/{$group->id}/self", [
            'academic_year' => '2025-2026',
            'scores' => [
                $criterion->id => ['self_score' => 4.5, 'self_note' => 'Đạt gần tối đa'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('evaluation_scores', [
            'evaluation_criterion_id' => $criterion->id,
            'union_group_id' => $group->id,
            'self_score' => 4.5,
        ]);
    }

    public function test_officer_cannot_self_score_another_groups_evaluation(): void
    {
        $officer = User::factory()->officer()->create();
        $ownGroup = UnionGroup::factory()->create();
        $otherGroup = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($ownGroup->id);

        $response = $this->actingAs($officer)->get("/evaluation/{$otherGroup->id}/self");

        $response->assertForbidden();
    }

    public function test_officer_cannot_verify_scores(): void
    {
        $officer = User::factory()->officer()->create();

        $response = $this->actingAs($officer)->get('/evaluation/verify');

        $response->assertForbidden();
    }

    public function test_admin_can_verify_scores_for_a_department(): void
    {
        $admin = User::factory()->admin()->create();
        $department = Department::create(['code' => 'TEST', 'name' => 'Ban kiểm thử']);
        $group = UnionGroup::factory()->create();
        $criterion = $this->makeCriterion(['department_id' => $department->id]);

        $response = $this->actingAs($admin)->put('/evaluation/verify', [
            'academic_year' => '2025-2026',
            'department_id' => $department->id,
            'scores' => [
                $criterion->id => [
                    $group->id => ['verified_score' => 4, 'verified_note' => 'Trừ 1 điểm do nộp trễ'],
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('evaluation_scores', [
            'evaluation_criterion_id' => $criterion->id,
            'union_group_id' => $group->id,
            'verified_score' => 4,
        ]);
    }

    public function test_report_classifies_group_based_on_verified_total(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create(['status' => 'active']);
        $criterionA = $this->makeCriterion(['order_no' => 1, 'max_score' => 50]);
        $criterionB = $this->makeCriterion(['order_no' => 2, 'max_score' => 50]);

        EvaluationScore::create([
            'evaluation_criterion_id' => $criterionA->id,
            'union_group_id' => $group->id,
            'self_score' => 45,
            'verified_score' => 45,
        ]);
        EvaluationScore::create([
            'evaluation_criterion_id' => $criterionB->id,
            'union_group_id' => $group->id,
            'self_score' => 40,
            'verified_score' => 40,
        ]);

        $summary = app(\App\Services\EvaluationService::class)->summary('2025-2026');
        $row = $summary->firstWhere('union_group.id', $group->id);

        $this->assertSame(85.0, $row['verified_total']);
        $this->assertSame('Hoàn thành xuất sắc nhiệm vụ', $row['classification']);
    }

    protected function makeScoredActivity(UnionGroup $group, array $overrides = []): Activity
    {
        return Activity::factory()->create(array_merge([
            'union_group_id' => $group->id,
            'activity_type_id' => ActivityType::factory(),
            'start_time' => '2025-09-01 08:00',
            'end_time' => '2025-09-01 10:00',
            'counts_for_evaluation' => true,
            'evaluation_max_score' => 10,
            'status' => Activity::STATUS_COMPLETED,
            'progress' => 100,
        ], $overrides));
    }

    public function test_completed_activity_at_full_progress_earns_full_score(): void
    {
        $group = UnionGroup::factory()->create(['status' => 'active']);
        $activity = $this->makeScoredActivity($group);

        $this->assertSame(10.0, $activity->earnedEvaluationScore());
    }

    public function test_partial_progress_scales_earned_score(): void
    {
        $group = UnionGroup::factory()->create(['status' => 'active']);
        $activity = $this->makeScoredActivity($group, ['progress' => 50]);

        $this->assertSame(5.0, $activity->earnedEvaluationScore());
    }

    public function test_incomplete_activity_earns_no_score_regardless_of_progress(): void
    {
        $group = UnionGroup::factory()->create(['status' => 'active']);
        $activity = $this->makeScoredActivity($group, ['status' => Activity::STATUS_IN_PROGRESS, 'progress' => 80]);

        $this->assertSame(0.0, $activity->earnedEvaluationScore());
    }

    public function test_activity_bonus_feeds_into_evaluation_report_self_total(): void
    {
        $group = UnionGroup::factory()->create(['status' => 'active']);
        $this->makeScoredActivity($group, ['evaluation_max_score' => 6, 'progress' => 100]);

        $summary = app(EvaluationService::class)->summary('2025-2026');
        $row = $summary->firstWhere('union_group.id', $group->id);

        $this->assertSame(6.0, $row['activity_bonus']);
        $this->assertSame(6.0, $row['self_total']);
    }

    public function test_activity_bonus_is_capped_at_bonus_criterion_max_score(): void
    {
        $group = UnionGroup::factory()->create(['status' => 'active']);
        $this->makeScoredActivity($group, ['evaluation_max_score' => 8, 'progress' => 100]);
        $this->makeScoredActivity($group, ['evaluation_max_score' => 8, 'progress' => 100]);

        $bonusCriterion = app(EvaluationService::class)->bonusCriterion('2025-2026');
        $earned = app(EvaluationService::class)->activityEarnedScore($group->id, '2025-2026');

        $this->assertSame(16.0, $earned);
        $summary = app(EvaluationService::class)->summary('2025-2026');
        $row = $summary->firstWhere('union_group.id', $group->id);
        $this->assertSame((float) $bonusCriterion->max_score, $row['activity_bonus']);
    }

    public function test_manual_self_score_for_bonus_criterion_is_ignored(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create(['status' => 'active']);
        $officer->managedUnionGroups()->attach($group->id);
        $this->makeScoredActivity($group, ['evaluation_max_score' => 4, 'progress' => 100]);

        $bonusCriterion = app(EvaluationService::class)->bonusCriterion('2025-2026');

        $response = $this->actingAs($officer)->put("/evaluation/{$group->id}/self", [
            'academic_year' => '2025-2026',
            'scores' => [
                $bonusCriterion->id => ['self_score' => 999, 'self_note' => 'cố tình sửa tay'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('evaluation_scores', [
            'evaluation_criterion_id' => $bonusCriterion->id,
            'union_group_id' => $group->id,
        ]);

        $summary = app(EvaluationService::class)->summary('2025-2026');
        $row = $summary->firstWhere('union_group.id', $group->id);
        $this->assertSame(4.0, $row['activity_bonus']);
    }
}
