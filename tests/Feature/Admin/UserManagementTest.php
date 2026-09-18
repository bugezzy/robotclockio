<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_does_not_see_the_system_role_option(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->get(route('admin.users.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Users')
                ->where('roles', fn ($roles) => collect($roles)->doesntContain(fn ($role) => $role['name'] === 'system'),
                ),
            );
    }

    public function test_system_sees_the_system_role_option(): void
    {
        $system = User::factory()->system()->create();

        $this->actingAs($system)
            ->get(route('admin.users.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Users')
                ->where('roles', fn ($roles) => collect($roles)->contains(fn ($role) => $role['name'] === 'system'),
                ),
            );
    }

    public function test_system_must_pick_an_organization_when_creating_a_non_system_user(): void
    {
        $system = User::factory()->system()->create();

        $this->actingAs($system)
            ->post(route('admin.users.store'), [
                'name' => 'New Owner',
                'role' => 'owner',
                'active' => true,
            ])
            ->assertSessionHasErrors('organization_id');
    }

    public function test_system_can_create_an_owner_with_no_team(): void
    {
        $system = User::factory()->system()->create();
        $organization = Organization::factory()->create();

        $this->actingAs($system)
            ->post(route('admin.users.store'), [
                'name' => 'New Owner',
                'role' => 'owner',
                'organization_id' => $organization->id,
                'active' => true,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('users', [
            'full_name' => 'New Owner',
            'role' => 'owner',
            'organization_id' => $organization->id,
            'team_id' => null,
        ], 'sqlite');
    }

    public function test_owner_can_link_a_discord_user_id_to_a_member(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);
        $employee = User::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->put(route('admin.users.update', $employee), [
                'name' => $employee->name,
                'role' => 'member',
                'discord_user_id' => '111111111111111111',
                'active' => true,
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('111111111111111111', $employee->refresh()->discord_user_id);
    }

    public function test_a_discord_user_id_cannot_be_linked_to_two_members(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);
        User::factory()->create(['organization_id' => $organization->id, 'discord_user_id' => '111111111111111111']);
        $employee = User::factory()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->put(route('admin.users.update', $employee), [
                'name' => $employee->name,
                'role' => 'member',
                'discord_user_id' => '111111111111111111',
                'active' => true,
            ])
            ->assertSessionHasErrors('discord_user_id');
    }
}
