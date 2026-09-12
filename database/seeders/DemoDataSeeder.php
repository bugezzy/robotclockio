<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     *
     * One system account, one organization with an owner, 2 teams split
     * 2/1 admins and 150/150 members. Members are card-only staff (no
     * password_hash — see the column comment in create_supabase_users_table)
     * since only admin/owner/system sign in to the dashboard.
     */
    public function run(): void
    {
        User::factory()->system()->create([
            'name' => 'James Moyers',
            'email' => 'james.moyers@crash850.com',
        ]);

        $organization = Organization::factory()->create([
            'name' => 'Acme Corp',
        ]);

        User::factory()->owner()->create([
            'organization_id' => $organization->id,
        ]);

        $teams = Team::factory()
            ->count(2)
            ->create(['organization_id' => $organization->id]);

        // 3 admins split 2/1 across the two teams, 300 members split evenly.
        $adminsPerTeam = [2, 1];

        foreach ($teams as $index => $team) {
            User::factory()
                ->admin()
                ->count($adminsPerTeam[$index])
                ->create([
                    'organization_id' => $organization->id,
                    'team_id' => $team->id,
                ]);

            User::factory()
                ->count(150)
                ->create([
                    'organization_id' => $organization->id,
                    'team_id' => $team->id,
                    'password_hash' => null,
                ]);
        }
    }
}
