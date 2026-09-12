<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Member;
use App\Models\MemberEvaluation;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberEvaluationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_shows_direct_shortcut_for_single_group_officer(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);

        $response = $this->actingAs($officer)->get('/dashboard');

        $response->assertOk();
        $response->assertSee(route('evaluation.members.edit', $group), false);
        $response->assertSee('Chấm điểm đoàn viên');
    }

    public function test_officer_can_classify_members_of_owned_group(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);
        $member = Member::factory()->create(['union_group_id' => $group->id, 'status' => 'active']);

        $response = $this->actingAs($officer)->put("/evaluation/{$group->id}/members", [
            'academic_year' => '2026-2027',
            'evaluations' => [
                $member->id => ['classification' => 'xuat_sac', 'score' => 95, 'note' => 'Rất tích cực'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('member_evaluations', [
            'member_id' => $member->id,
            'academic_year' => '2026-2027',
            'classification' => 'xuat_sac',
            'score' => 95,
        ]);
    }

    public function test_officer_cannot_classify_members_of_another_group(): void
    {
        $officer = User::factory()->officer()->create();
        $ownGroup = UnionGroup::factory()->create();
        $otherGroup = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($ownGroup->id);
        $member = Member::factory()->create(['union_group_id' => $otherGroup->id, 'status' => 'active']);

        $response = $this->actingAs($officer)->put("/evaluation/{$otherGroup->id}/members", [
            'academic_year' => '2026-2027',
            'evaluations' => [
                $member->id => ['classification' => 'xuat_sac'],
            ],
        ]);

        $response->assertForbidden();
    }

    public function test_cannot_smuggle_evaluation_for_member_outside_the_group(): void
    {
        $officer = User::factory()->officer()->create();
        $ownGroup = UnionGroup::factory()->create();
        $otherGroup = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($ownGroup->id);
        $foreignMember = Member::factory()->create(['union_group_id' => $otherGroup->id, 'status' => 'active']);

        $response = $this->actingAs($officer)->put("/evaluation/{$ownGroup->id}/members", [
            'academic_year' => '2026-2027',
            'evaluations' => [
                $foreignMember->id => ['classification' => 'xuat_sac'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('member_evaluations', ['member_id' => $foreignMember->id]);
    }

    public function test_edit_page_shows_participation_rate_from_attended_activities(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);
        $member = Member::factory()->create(['union_group_id' => $group->id, 'status' => 'active']);
        $type = ActivityType::factory()->create();

        $attended = Activity::factory()->create([
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-09-01 08:00',
        ]);
        $missed = Activity::factory()->create([
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-10-01 08:00',
        ]);
        $attended->participants()->create(['member_id' => $member->id, 'status' => 'attended']);
        $missed->participants()->create(['member_id' => $member->id, 'status' => 'absent']);

        $response = $this->actingAs($officer)->get("/evaluation/{$group->id}/members?academic_year=2026-2027");

        $response->assertOk();
        $response->assertSee('1/2 (50%)');
    }

    public function test_existing_classification_is_prefilled_on_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $member = Member::factory()->create(['union_group_id' => $group->id, 'status' => 'active']);
        MemberEvaluation::create([
            'member_id' => $member->id,
            'academic_year' => '2026-2027',
            'classification' => 'tich_cuc',
            'score' => 80,
        ]);

        $response = $this->actingAs($admin)->get("/evaluation/{$group->id}/members?academic_year=2026-2027");

        $response->assertOk();
        $response->assertSee('Đoàn viên tích cực');
    }
}
