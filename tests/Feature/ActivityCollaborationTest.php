<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCollaborationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_edit_pages_render_with_collaboration_picker(): void
    {
        $admin = User::factory()->admin()->create();
        $activity = Activity::factory()->create();

        $this->actingAs($admin)->get('/activities/create')->assertOk();
        $this->actingAs($admin)->get("/activities/{$activity->id}/edit")->assertOk();
    }

    public function test_activity_can_have_collaborating_groups_with_roles(): void
    {
        $admin = User::factory()->admin()->create();
        $host = UnionGroup::factory()->create();
        $collaborator = UnionGroup::factory()->create();
        $participant = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create();

        $response = $this->actingAs($admin)->post('/activities', [
            'code' => 'HD-200',
            'name' => 'Hội thao liên tổ',
            'union_group_id' => $host->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-09-01 08:00',
            'end_time' => '2026-09-01 10:00',
            'status' => 'not_started',
            'progress' => 0,
            'collaborating_groups' => [
                'phoi_hop' => [$collaborator->id],
                'tham_gia' => [$participant->id],
            ],
        ]);

        $response->assertRedirect();
        $activity = Activity::where('code', 'HD-200')->firstOrFail();

        $this->assertDatabaseHas('activity_union_groups', [
            'activity_id' => $activity->id,
            'union_group_id' => $collaborator->id,
            'role' => 'phoi_hop',
        ]);
        $this->assertDatabaseHas('activity_union_groups', [
            'activity_id' => $activity->id,
            'union_group_id' => $participant->id,
            'role' => 'tham_gia',
        ]);

        $this->actingAs($admin)->get("/activities/{$activity->id}")->assertOk()->assertSee($collaborator->name);
    }

    public function test_primary_group_cannot_also_be_a_collaborating_group(): void
    {
        $admin = User::factory()->admin()->create();
        $host = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create();

        $response = $this->actingAs($admin)->post('/activities', [
            'code' => 'HD-201',
            'name' => 'Hoạt động lỗi',
            'union_group_id' => $host->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-09-01 08:00',
            'end_time' => '2026-09-01 10:00',
            'status' => 'not_started',
            'progress' => 0,
            'collaborating_groups' => [
                'phoi_hop' => [$host->id],
            ],
        ]);

        $response->assertSessionHasErrors('collaborating_groups');
        $this->assertDatabaseMissing('activities', ['code' => 'HD-201']);
    }
}
