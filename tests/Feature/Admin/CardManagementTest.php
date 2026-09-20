<?php

namespace Tests\Feature\Admin;

use App\Models\Card;
use App\Models\Organization;
use App\Models\Punch;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CardManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unmatched_scans_include_punches_with_no_user(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);

        Punch::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
            'card_uid' => 'DEADBEEF',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.cards.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Cards')
                ->where('unmatchedScans', fn ($scans) => collect($scans)->contains(fn ($scan) => $scan['card_uid'] === 'DEADBEEF'),
                ),
            );
    }

    public function test_unmatched_scans_exclude_uids_that_already_have_an_active_card(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);
        $employee = User::factory()->create(['organization_id' => $organization->id]);

        Card::factory()->create(['card_uid' => 'ABCDEF01', 'user_id' => $employee->id]);

        Punch::factory()->create([
            'organization_id' => $organization->id,
            'user_id' => null,
            'card_uid' => 'ABCDEF01',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.cards.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Cards')
                ->where('unmatchedScans', fn ($scans) => collect($scans)->doesntContain(fn ($scan) => $scan['card_uid'] === 'ABCDEF01'),
                ),
            );
    }

    public function test_unmatched_scans_are_scoped_to_the_owners_organization(): void
    {
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);

        $otherOrganization = Organization::factory()->create();
        Punch::factory()->create([
            'organization_id' => $otherOrganization->id,
            'user_id' => null,
            'card_uid' => 'FEEDFACE',
        ]);

        $this->actingAs($owner)
            ->get(route('admin.cards.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Cards')
                ->where('unmatchedScans', fn ($scans) => collect($scans)->doesntContain(fn ($scan) => $scan['card_uid'] === 'FEEDFACE'),
                ),
            );
    }
}
