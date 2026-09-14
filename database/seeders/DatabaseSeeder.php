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

        // System, not owner: system is the only role that doesn't require
        // an organization_id (see the users_org_unless_system check on the
        // real users table), so this stays a simple, dependency-free login
        // with full admin panel access.
        User::factory()->system()->create([
            'name' => 'James Moyers',
            'email' => 'bugezzy@gmail.com',
        ]);

        $this->call(DemoDataSeeder::class);
    }
}
