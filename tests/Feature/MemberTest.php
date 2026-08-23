<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_member(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();

        $response = $this->actingAs($admin)->post('/members', [
            'code' => 'DV-001',
            'full_name' => 'Nguyen Van A',
            'union_group_id' => $group->id,
            'status' => 'active',
        ]);

        $response->assertRedirect('/members');
        $this->assertDatabaseHas('members', ['code' => 'DV-001', 'union_group_id' => $group->id]);
    }

    public function test_member_code_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        Member::factory()->create(['code' => 'DV-001', 'union_group_id' => $group->id]);

        $response = $this->actingAs($admin)->post('/members', [
            'code' => 'DV-001',
            'full_name' => 'Nguyen Van B',
            'union_group_id' => $group->id,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_validation_rejects_invalid_gender(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();

        $response = $this->actingAs($admin)->post('/members', [
            'code' => 'DV-002',
            'full_name' => 'Nguyen Van C',
            'union_group_id' => $group->id,
            'status' => 'active',
            'gender' => 'invalid-value',
        ]);

        $response->assertSessionHasErrors('gender');
    }

    public function test_officer_can_only_manage_members_of_owned_group(): void
    {
        $officer = User::factory()->officer()->create();
        $ownedGroup = UnionGroup::factory()->create();
        $otherGroup = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($ownedGroup->id);

        $memberInOtherGroup = Member::factory()->create(['union_group_id' => $otherGroup->id]);

        $response = $this->actingAs($officer)->get("/members/{$memberInOtherGroup->id}");

        $response->assertForbidden();
    }

    public function test_member_role_can_view_own_profile(): void
    {
        $user = User::factory()->member()->create();
        $group = UnionGroup::factory()->create();
        $member = Member::factory()->create(['union_group_id' => $group->id, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->get("/members/{$member->id}");

        $response->assertOk();
    }

    public function test_member_role_cannot_view_others_profile(): void
    {
        $user = User::factory()->member()->create();
        $group = UnionGroup::factory()->create();
        $otherMember = Member::factory()->create(['union_group_id' => $group->id]);

        $response = $this->actingAs($user)->get("/members/{$otherMember->id}");

        $response->assertForbidden();
    }

    public function test_cannot_delete_member_who_has_participated_in_activities(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $member = Member::factory()->create(['union_group_id' => $group->id]);
        $activity = \App\Models\Activity::factory()->create([
            'union_group_id' => $group->id,
            'activity_type_id' => \App\Models\ActivityType::factory(),
        ]);
        $activity->participants()->create(['member_id' => $member->id, 'status' => 'registered']);

        $response = $this->actingAs($admin)->delete("/members/{$member->id}");

        $response->assertRedirect('/members');
        $this->assertDatabaseHas('members', ['id' => $member->id]);
    }
}
