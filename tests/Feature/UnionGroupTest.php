<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityType;
use App\Models\Member;
use App\Models\UnionGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnionGroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_union_group(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/union-groups', [
            'code' => 'TCD-001',
            'name' => 'Tổ Công đoàn Khoa CNTT',
            'status' => 'active',
        ]);

        $response->assertRedirect('/union-groups');
        $this->assertDatabaseHas('union_groups', ['code' => 'TCD-001']);
    }

    public function test_union_group_code_must_be_unique(): void
    {
        $admin = User::factory()->admin()->create();
        UnionGroup::factory()->create(['code' => 'TCD-001']);

        $response = $this->actingAs($admin)->post('/union-groups', [
            'code' => 'TCD-001',
            'name' => 'Tổ khác',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame(1, UnionGroup::where('code', 'TCD-001')->count());
    }

    public function test_officer_cannot_create_union_group(): void
    {
        $officer = User::factory()->officer()->create();

        $response = $this->actingAs($officer)->post('/union-groups', [
            'code' => 'TCD-002',
            'name' => 'Tổ mới',
            'status' => 'active',
        ]);

        $response->assertForbidden();
    }

    public function test_officer_only_sees_managed_union_groups(): void
    {
        $officer = User::factory()->officer()->create();
        $managed = UnionGroup::factory()->create(['name' => 'Tổ được quản lý']);
        $other = UnionGroup::factory()->create(['name' => 'Tổ khác']);
        $officer->managedUnionGroups()->attach($managed->id);

        $response = $this->actingAs($officer)->get('/union-groups');

        $response->assertOk();
        $response->assertSee($managed->name);
        $response->assertDontSee($other->name);
    }

    public function test_cannot_delete_union_group_with_members(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        Member::factory()->create(['union_group_id' => $group->id]);

        $response = $this->actingAs($admin)->delete("/union-groups/{$group->id}");

        $response->assertRedirect('/union-groups');
        $this->assertDatabaseHas('union_groups', ['id' => $group->id]);
    }

    public function test_admin_can_delete_empty_union_group(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();

        $response = $this->actingAs($admin)->delete("/union-groups/{$group->id}");

        $response->assertRedirect('/union-groups');
        $this->assertSoftDeleted('union_groups', ['id' => $group->id]);
    }

    public function test_union_group_show_page_reports_correct_stats(): void
    {
        $admin = User::factory()->admin()->create();
        $group = UnionGroup::factory()->create();
        $type = ActivityType::factory()->create();
        Member::factory()->count(3)->create(['union_group_id' => $group->id]);
        Activity::factory()->completed()->create(['union_group_id' => $group->id, 'activity_type_id' => $type->id]);
        Activity::factory()->notStarted()->create(['union_group_id' => $group->id, 'activity_type_id' => $type->id]);

        $response = $this->actingAs($admin)->get("/union-groups/{$group->id}");

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['total_members'] === 3
                && $stats['total_activities'] === 2
                && $stats['completed'] === 1
                && $stats['not_started'] === 1;
        });
    }
}
