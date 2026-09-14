<?php

namespace Tests\Feature\Admin;

use App\Models\Card;
use App\Models\Organization;
use App\Models\Punch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_sees_platform_wide_stats(): void
    {
        $system = User::factory()->system()->create();

        $ownOrganization = Organization::factory()->create();
        $otherOrganization = Organization::factory()->create();
        Team::factory()->create(['organization_id' => $ownOrganization->id]);

        $employee = User::factory()->create(['organization_id' => $otherOrganization->id]);
        Card::factory()->create(['user_id' => $employee->id]);
        Punch::factory()->create([
            'organization_id' => $otherOrganization->id,
            'user_id' => $employee->id,
            'punched_at' => Carbon::today(),
        ]);

        $this->actingAs($system)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->where('stats.primary.label', 'Organizations')
                ->where('stats.primary.count', Organization::count())
                ->where('stats.users', User::count())
                ->where('stats.punchesToday', 1),
            );
    }

    public function test_owner_only_sees_their_own_organizations_data(): void
    {
        $ownOrganization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $ownOrganization->id]);
        Team::factory()->count(2)->create(['organization_id' => $ownOrganization->id]);

        $ownEmployee = User::factory()->create(['organization_id' => $ownOrganization->id]);
        Card::factory()->create(['user_id' => $ownEmployee->id]);
        Punch::factory()->create([
            'organization_id' => $ownOrganization->id,
            'user_id' => $ownEmployee->id,
            'punched_at' => Carbon::today(),
        ]);

        $otherOrganization = Organization::factory()->create();
        $otherEmployee = User::factory()->create(['organization_id' => $otherOrganization->id]);
        Card::factory()->create(['user_id' => $otherEmployee->id]);
        Punch::factory()->create([
            'organization_id' => $otherOrganization->id,
            'user_id' => $otherEmployee->id,
            'punched_at' => Carbon::today(),
        ]);

        $this->actingAs($owner)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->where('stats.primary.label', 'Teams')
                ->where('stats.primary.count', 2)
                // owner (self) + the one employee in their own organization.
                ->where('stats.users', 2)
                ->where('stats.activeCards', 1)
                ->where('stats.punchesToday', 1)
                ->has('recentPunches', 1)
                ->where('recentPunches.0.organization_name', $ownOrganization->name),
            );
    }

    public function test_admin_only_sees_their_own_teams_data(): void
    {
        $organization = Organization::factory()->create();
        $ownTeam = Team::factory()->create(['organization_id' => $organization->id]);
        $otherTeam = Team::factory()->create(['organization_id' => $organization->id]);

        $admin = User::factory()->admin()->create([
            'organization_id' => $organization->id,
            'team_id' => $ownTeam->id,
        ]);

        $ownTeammate = User::factory()->create([
            'organization_id' => $organization->id,
            'team_id' => $ownTeam->id,
        ]);
        Card::factory()->create(['user_id' => $ownTeammate->id]);
        Punch::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $ownTeammate->id,
            'punched_at' => Carbon::today(),
        ]);

        $otherTeamEmployee = User::factory()->create([
            'organization_id' => $organization->id,
            'team_id' => $otherTeam->id,
        ]);
        Card::factory()->create(['user_id' => $otherTeamEmployee->id]);
        Punch::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => $otherTeamEmployee->id,
            'punched_at' => Carbon::today(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Dashboard')
                ->where('stats.primary', null)
                // admin (self) + their one teammate.
                ->where('stats.users', 2)
                ->where('stats.activeCards', 1)
                ->where('stats.punchesToday', 1)
                ->has('recentPunches', 1),
            );
    }
}
