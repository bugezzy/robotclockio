<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Fortify\Features;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::registration());
    }

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'team_name' => 'Acme Inc',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_registration_creates_an_organization_and_team_owned_by_the_new_user(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Test User',
            'team_name' => 'Acme Inc',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $organization = Organization::where('name', 'Acme Inc')->first();
        $team = Team::where('name', 'Acme Inc')->first();
        $user = User::where('email', 'test@example.com')->first();

        $this->assertNotNull($organization);
        $this->assertNotNull($team);
        $this->assertSame($organization->id, $team->organization_id);
        $this->assertSame('owner', $user->role);
        $this->assertSame($organization->id, $user->organization_id);
        $this->assertSame($team->id, $user->team_id);
    }

    public function test_registration_fails_when_team_name_is_taken(): void
    {
        Organization::factory()->create(['name' => 'Acme Inc']);

        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'team_name' => 'Acme Inc',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('team_name');
        $this->assertGuest();
        $this->assertSame(1, Organization::count());
        $this->assertNull(User::where('email', 'test@example.com')->first());
    }

    public function test_registration_rejects_a_blank_team_name(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'team_name' => '   ',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('team_name');
        $this->assertGuest();
        $this->assertSame(0, Organization::count());
    }
}
