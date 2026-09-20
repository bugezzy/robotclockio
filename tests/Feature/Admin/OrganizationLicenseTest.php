<?php

namespace Tests\Feature\Admin;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class OrganizationLicenseTest extends TestCase
{
    use DatabaseTransactions;

    public function test_owner_can_revoke_their_organizations_license(): void
    {
        $organization = Organization::factory()->create(['kiosk_key' => '11111111-1111-1111-1111-111111111111']);
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->delete(route('admin.organizations.license.revoke', $organization))
            ->assertSuccessful();

        $this->assertNotSame('11111111-1111-1111-1111-111111111111', $organization->fresh()->kiosk_key);
    }

    public function test_system_can_revoke_any_organizations_license(): void
    {
        $organization = Organization::factory()->create(['kiosk_key' => '11111111-1111-1111-1111-111111111111']);
        $system = User::factory()->system()->create();

        $this->actingAs($system)
            ->delete(route('admin.organizations.license.revoke', $organization))
            ->assertSuccessful();

        $this->assertNotSame('11111111-1111-1111-1111-111111111111', $organization->fresh()->kiosk_key);
    }

    public function test_owner_cannot_revoke_another_organizations_license(): void
    {
        $organization = Organization::factory()->create(['kiosk_key' => '11111111-1111-1111-1111-111111111111']);
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->delete(route('admin.organizations.license.revoke', $organization))
            ->assertForbidden();

        $this->assertSame('11111111-1111-1111-1111-111111111111', $organization->fresh()->kiosk_key);
    }

    public function test_admin_cannot_revoke_a_license(): void
    {
        $organization = Organization::factory()->create(['kiosk_key' => '11111111-1111-1111-1111-111111111111']);
        $admin = User::factory()->admin()->create(['organization_id' => $organization->id]);

        $this->actingAs($admin)
            ->delete(route('admin.organizations.license.revoke', $organization))
            ->assertForbidden();

        $this->assertSame('11111111-1111-1111-1111-111111111111', $organization->fresh()->kiosk_key);
    }

    public function test_revoking_a_license_does_not_delete_its_issuance_history(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)->post(route('admin.organizations.license.issue', $organization));

        $response = $this->actingAs($owner)
            ->delete(route('admin.organizations.license.revoke', $organization));

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'history');
    }
}
