<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\EvaluationCriterion;
use App\Models\EvaluationScore;
use App\Models\UnionGroup;
use App\Models\User;
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
}
