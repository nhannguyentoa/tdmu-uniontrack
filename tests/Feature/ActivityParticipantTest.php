<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityParticipantTest extends TestCase
{
    use RefreshDatabase;

    protected function makeActivity(): Activity
    {
        return Activity::factory()->create([
            'union_group_id' => UnionGroup::factory(),
            'activity_type_id' => ActivityType::factory(),
        ]);
    }

    public function test_admin_can_add_participants_to_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = $this->makeActivity();
        $member = Member::factory()->create(['union_group_id' => $activity->union_group_id]);

        $response = $this->actingAs($admin)->post("/activities/{$activity->id}/participants", [
            'member_ids' => [$member->id],
            'status' => 'registered',
        ]);

        $response->assertRedirect("/activities/{$activity->id}");
        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $activity->id,
            'member_id' => $member->id,
        ]);
    }

    public function test_cannot_add_same_member_twice_to_same_activity(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = $this->makeActivity();
        $member = Member::factory()->create(['union_group_id' => $activity->union_group_id]);
        $activity->participants()->create(['member_id' => $member->id, 'status' => 'registered']);

        $response = $this->actingAs($admin)->post("/activities/{$activity->id}/participants", [
            'member_ids' => [$member->id],
            'status' => 'registered',
        ]);

        $response->assertSessionHasErrors('member_ids');
        $this->assertSame(1, $activity->participants()->where('member_id', $member->id)->count());
    }

    public function test_officer_cannot_manage_participants_of_unmanaged_activity(): void
    {
        $officer = User::factory()->officer()->create();
        $activity = $this->makeActivity();
        $member = Member::factory()->create(['union_group_id' => $activity->union_group_id]);

        $response = $this->actingAs($officer)->post("/activities/{$activity->id}/participants", [
            'member_ids' => [$member->id],
            'status' => 'registered',
        ]);

        $response->assertForbidden();
    }

    public function test_participant_can_be_removed(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = $this->makeActivity();
        $member = Member::factory()->create(['union_group_id' => $activity->union_group_id]);
        $participant = $activity->participants()->create(['member_id' => $member->id, 'status' => 'registered']);

        $response = $this->actingAs($admin)->delete("/activities/{$activity->id}/participants/{$participant->id}");

        $response->assertRedirect("/activities/{$activity->id}");
        $this->assertDatabaseMissing('activity_participants', ['id' => $participant->id]);
    }
}
