<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_user_management(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/users')->assertOk();
    }

    public function test_officer_cannot_access_user_management(): void
    {
        $officer = User::factory()->officer()->create();

        $this->actingAs($officer)->get('/users')->assertForbidden();
    }

    public function test_admin_can_create_officer_account_and_assign_group(): void
    {
        $admin = User::factory()->admin()->create();
        $group = \App\Models\UnionGroup::factory()->create();

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Cán bộ mới',
            'email' => 'new.officer@tdmu.edu.vn',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'role' => 'officer',
            'union_group_ids' => [$group->id],
        ]);

        $response->assertRedirect('/users');
        $newUser = User::where('email', 'new.officer@tdmu.edu.vn')->first();
        $this->assertNotNull($newUser);
        $this->assertTrue($newUser->managedUnionGroups->contains($group->id));
    }

    public function test_each_role_sees_appropriate_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $officer = User::factory()->officer()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Tổ công đoàn');
        $this->actingAs($officer)->get('/dashboard')->assertOk();
    }

    public function test_inactive_user_cannot_access_admin_only_routes(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => false]);

        $this->actingAs($admin)->get('/users')->assertForbidden();
    }
}
