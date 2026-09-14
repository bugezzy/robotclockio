<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
        //
        // Built directly rather than via User::factory(): factories call
        // fake() to fill in unfaked attributes, and fakerphp/faker is a
        // require-dev package that isn't present after a production
        // (--no-dev) composer install, which is where this seeder runs.
        User::create([
            'name' => 'James Moyers',
            'email' => 'bugezzy@gmail.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'system',
            'active' => true,
        ]);

    }
}
