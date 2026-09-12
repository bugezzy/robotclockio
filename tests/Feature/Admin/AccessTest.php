<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.organizations.index'))->assertRedirect(route('login'));
    }

    public function test_non_admin_users_cannot_access_the_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_non_admin_users_cannot_access_organizations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.organizations.index'))
            ->assertForbidden();
    }
}
