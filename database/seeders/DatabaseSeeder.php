<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Owner, not member: an owner is the only role that doesn't require
        // an organization_id (see the users_org_unless_owner check on the
        // real users table), so this stays a simple, dependency-free login
        // for local admin panel access.
        User::factory()->owner()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
