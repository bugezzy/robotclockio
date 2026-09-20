<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
     * CREATE TRIGGER isn't idempotent the way CREATE OR REPLACE FUNCTION is,
     * so each is DROP TRIGGER IF EXISTS followed by CREATE TRIGGER.
     */
    public function up(): void
    {
        DB::statement('drop trigger if exists teams_touch_updated_at on teams');
        DB::statement(<<<'SQL'
            create trigger teams_touch_updated_at
                before update on teams
                for each row execute function private.touch_updated_at()
            SQL);

        DB::statement('drop trigger if exists users_touch_updated_at on users');
        DB::statement(<<<'SQL'
            create trigger users_touch_updated_at
                before update on users
                for each row execute function private.touch_updated_at()
            SQL);

        DB::statement('drop trigger if exists organizations_touch_updated_at on organizations');
        DB::statement(<<<'SQL'
            create trigger organizations_touch_updated_at
                before update on organizations
                for each row execute function private.touch_updated_at()
            SQL);

        DB::statement('drop trigger if exists users_team_in_same_org on users');
        DB::statement(<<<'SQL'
            create trigger users_team_in_same_org
                before insert or update of team_id, organization_id on users
                for each row execute function private.user_team_in_same_org()
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Never auto-drop shared triggers on a rollback outside testing.
    }
};
