<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_activity_type(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/activity-types', [
            'name' => 'Hoạt động thể thao',
            'is_active' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activity_types', ['name' => 'Hoạt động thể thao']);
    }

    public function test_activity_type_name_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        ActivityType::factory()->create(['name' => 'Hoạt động xã hội']);

        $response = $this->actingAs($admin)->post('/activity-types', [
            'name' => 'Hoạt động xã hội',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_officer_cannot_manage_activity_types(): void
    {
        $officer = User::factory()->officer()->create();

        $response = $this->actingAs($officer)->get('/activity-types');

        $response->assertForbidden();
    }

    public function test_cannot_delete_activity_type_in_use(): void
    {
        $admin = User::factory()->admin()->create();
        $type = ActivityType::factory()->create();
        Activity::factory()->create([
            'activity_type_id' => $type->id,
            'union_group_id' => UnionGroup::factory(),
        ]);

        $response = $this->actingAs($admin)->delete("/activity-types/{$type->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('activity_types', ['id' => $type->id]);
    }
}
