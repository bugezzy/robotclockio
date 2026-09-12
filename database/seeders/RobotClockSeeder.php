<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Punch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Simulates a realistic RobotClock dataset: a customer with a couple of
 * teams, employees with cards, and two weeks of clock in/out history.
 *
 * This writes through the 'supabase' connection (see App\Models\User and
 * its siblings Organization/Team/Card/Punch), so it targets whatever
 * database that connection currently points at.
 */
class RobotClockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::factory()->create();

        $teams = Team::factory(2)->create([
            'organization_id' => $organization->id,
        ]);

        $teams->each(function (Team $team) use ($organization) {
            $employees = User::factory(random_int(2, 3))->create([
                'team_id' => $team->id,
                'organization_id' => $organization->id,
            ]);

            $employees->each(function (User $employee) use ($organization) {
                $card = $employee->cards()->create([
                    'card_uid' => strtoupper(bin2hex(random_bytes(4))),
                ]);

                $this->punchHistoryFor($employee, $card->card_uid, $organization->id);
            });
        });
    }

    /**
     * Create two weeks of alternating clock in/out punches for an employee.
     */
    private function punchHistoryFor(User $employee, string $cardUid, string $organizationId): void
    {
        $direction = 'in';

        for ($daysAgo = 13; $daysAgo >= 0; $daysAgo--) {
            $punchedAt = now()->subDays($daysAgo)->setTime(9, 0)->addMinutes(random_int(-15, 15));

            Punch::factory()->create([
                'card_uid' => $cardUid,
                'user_id' => $employee->id,
                'direction' => $direction,
                'punched_at' => $punchedAt,
                'organization_id' => $organizationId,
            ]);

            $direction = $direction === 'in' ? 'out' : 'in';
        }
    }
}
