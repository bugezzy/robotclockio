<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The RobotClock database this app owns, not this application's own
     * default connection.
     */
    protected $connection = 'supabase';

    /**
     * Run the migrations.
     *
     * Roles, in ascending order of privilege. `rank` is for display/ordering
     * only — the PL/pgSQL functions in this schema check explicit role
     * names (via the `name` column, still unique though no longer the
     * primary key), not rank or id, to match the rest of the codebase's
     * style.
     */
    public function up(): void
    {
        if (app()->environment('testing') || Schema::hasTable('roles')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->text('description');
            $table->unsignedSmallInteger('rank')->unique();
        });

        $uuid = fn () => DB::raw('gen_random_uuid()');

        DB::table('roles')->insert([
            ['id' => $uuid(), 'name' => 'member', 'description' => 'Plain employee. No admin dashboard access.', 'rank' => 0],
            ['id' => $uuid(), 'name' => 'admin', 'description' => 'Team admin. Adds and removes cards.', 'rank' => 1],
            ['id' => $uuid(), 'name' => 'owner', 'description' => 'Creates teams and edits the organization.', 'rank' => 2],
            ['id' => $uuid(), 'name' => 'system', 'description' => 'Full access to every aspect. Creates and licenses organizations.', 'rank' => 3],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Never auto-drop shared reference data on a rollback outside testing.
    }
};
