<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function validPayload(array $overrides = []): array
    {
        $group = $overrides['union_group_id'] ?? UnionGroup::factory()->create()->id;
        $type = $overrides['activity_type_id'] ?? ActivityType::factory()->create()->id;

        return array_merge([
            'code' => 'HD-001',
            'name' => 'Hội thao truyền thống',
            'union_group_id' => $group,
            'activity_type_id' => $type,
            'start_time' => '2026-09-01 08:00',
            'end_time' => '2026-09-01 10:00',
            'status' => 'not_started',
            'progress' => 0,
        ], $overrides);
    }

    public function test_admin_can_create_activity(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/activities', $this->validPayload());

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', ['code' => 'HD-001']);
    }

    public function test_end_time_cannot_be_before_start_time(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/activities', $this->validPayload([
            'start_time' => '2026-09-01 10:00',
            'end_time' => '2026-09-01 08:00',
        ]));

        $response->assertSessionHasErrors('end_time');
        $this->assertDatabaseMissing('activities', ['code' => 'HD-001']);
    }

    public function test_activity_code_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create();
        Activity::factory()->create(['code' => 'HD-001', 'union_group_id' => $group->id, 'activity_type_id' => $type->id]);

        $response = $this->actingAs($admin)->post('/activities', $this->validPayload([
            'code' => 'HD-001',
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
        ]));

        $response->assertSessionHasErrors('code');
    }

    public function test_progress_must_be_between_0_and_100(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/activities', $this->validPayload(['progress' => 150]));

        $response->assertSessionHasErrors('progress');
    }

    public function test_officer_cannot_create_activity_for_unmanaged_group(): void
    {
        $officer = User::factory()->officer()->create();
        $ownedGroup = UnionGroup::factory()->create();
        $otherGroup = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($ownedGroup->id);

        $response = $this->actingAs($officer)->post('/activities', $this->validPayload([
            'union_group_id' => $otherGroup->id,
        ]));

        $response->assertSessionHasErrors('union_group_id');
    }

    public function test_officer_can_create_activity_for_managed_group(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);

        $response = $this->actingAs($officer)->post('/activities', $this->validPayload([
            'union_group_id' => $group->id,
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', ['code' => 'HD-001', 'union_group_id' => $group->id]);
    }

    public function test_only_admin_can_delete_activity(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);
        $activity = Activity::factory()->create([
            'union_group_id' => $group->id,
            'activity_type_id' => ActivityType::factory(),
        ]);

        $response = $this->actingAs($officer)->delete("/activities/{$activity->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'deleted_at' => null]);
    }

    public function test_status_change_records_history(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create();
        $activity = Activity::factory()->notStarted()->create([
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
        ]);

        $response = $this->actingAs($admin)->put("/activities/{$activity->id}", $this->validPayload([
            'code' => $activity->code,
            'name' => $activity->name,
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
            'status' => 'in_progress',
            'progress' => 40,
        ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('activity_status_histories', [
            'activity_id' => $activity->id,
            'status' => 'in_progress',
            'progress' => 40,
        ]);
    }

    public function test_officer_can_quick_update_status_and_progress_without_full_edit(): void
    {
        $officer = User::factory()->officer()->create();
        $group = UnionGroup::factory()->create();
        $officer->managedUnionGroups()->attach($group->id);
        $activity = Activity::factory()->create(['union_group_id' => $group->id, 'status' => 'not_started', 'progress' => 0]);

        $response = $this->actingAs($officer)->patch("/activities/{$activity->id}/quick-update", [
            'status' => 'in_progress',
            'progress' => 50,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'status' => 'in_progress', 'progress' => 50]);
        $this->assertDatabaseHas('activity_status_histories', ['activity_id' => $activity->id, 'status' => 'in_progress', 'progress' => 50]);
    }

    public function test_quick_update_rejects_non_milestone_progress(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        $response = $this->actingAs($admin)->patch("/activities/{$activity->id}/quick-update", [
            'progress' => 42,
        ]);

        $response->assertSessionHasErrors('progress');
    }

    public function test_officer_cannot_quick_update_activity_of_another_group(): void
    {
        $officer = User::factory()->officer()->create();
        $otherGroup = UnionGroup::factory()->create();
        $activity = Activity::factory()->create(['union_group_id' => $otherGroup->id]);

        $response = $this->actingAs($officer)->patch("/activities/{$activity->id}/quick-update", [
            'status' => 'completed',
        ]);

        $response->assertForbidden();
    }
}
