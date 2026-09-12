<?php

namespace Tests\Feature;

use App\Models\ActivityPlan;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_and_edit_pages_render(): void
    {
        $admin = User::factory()->admin()->create();
        $plan = ActivityPlan::create([
            'academic_year' => '2026-2027',
            'month' => 9,
            'title' => 'Kiểm thử render',
            'status' => 'planned',
        ]);

        $this->actingAs($admin)->get('/activity-plans/create')->assertOk();
        $this->actingAs($admin)->get("/activity-plans/{$plan->id}/edit")->assertOk();
    }

    public function test_admin_can_create_activity_plan(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();

        $response = $this->actingAs($admin)->post('/activity-plans', [
            'academic_year' => '2026-2027',
            'month' => 9,
            'title' => 'Tổ chức khám sức khỏe cho VC, NLĐ',
            'host_union_group_id' => $group->id,
            'status' => 'planned',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activity_plans', [
            'title' => 'Tổ chức khám sức khỏe cho VC, NLĐ',
            'host_union_group_id' => $group->id,
        ]);
    }

    public function test_index_groups_plans_by_month_for_selected_academic_year(): void
    {
        $user = User::factory()->officer()->create();
        ActivityPlan::create([
            'academic_year' => '2026-2027',
            'month' => 9,
            'title' => 'Tuyên truyền 2/9',
            'status' => 'planned',
        ]);

        $response = $this->actingAs($user)->get('/activity-plans?academic_year=2026-2027');

        $response->assertOk();
        $response->assertSee('Tuyên truyền 2/9');
    }

    public function test_converting_plan_to_activity_links_it_back(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $type = \App\Models\ActivityType::factory()->create();
        $plan = ActivityPlan::create([
            'academic_year' => '2026-2027',
            'month' => 9,
            'title' => 'Tổ chức Tết Trung thu',
            'host_union_group_id' => $group->id,
            'status' => 'planned',
        ]);

        $response = $this->actingAs($admin)->post('/activities', [
            'code' => 'HD-100',
            'name' => 'Tổ chức Tết Trung thu',
            'union_group_id' => $group->id,
            'activity_type_id' => $type->id,
            'start_time' => '2026-09-15 08:00',
            'end_time' => '2026-09-15 10:00',
            'status' => 'not_started',
            'progress' => 0,
            'from_activity_plan_id' => $plan->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('activity_plans', [
            'id' => $plan->id,
            'status' => 'done',
        ]);
        $this->assertNotNull($plan->fresh()->activity_id);
    }
}
