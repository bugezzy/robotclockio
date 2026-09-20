<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrganizationManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_owner_can_link_a_discord_guild_id_to_their_organization(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->put(route('admin.organizations.update', $organization), [
                'name' => $organization->name,
                'discord_guild_id' => '222222222222222222',
            ])
            ->assertSessionDoesntHaveErrors();

        $this->assertSame('222222222222222222', $organization->refresh()->discord_guild_id);
    }

    public function test_a_discord_guild_id_cannot_be_linked_to_two_organizations(): void
    {
        Organization::factory()->create(['discord_guild_id' => '222222222222222222']);
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->put(route('admin.organizations.update', $organization), [
                'name' => $organization->name,
                'discord_guild_id' => '222222222222222222',
            ])
            ->assertSessionHasErrors('discord_guild_id');
    }
}
