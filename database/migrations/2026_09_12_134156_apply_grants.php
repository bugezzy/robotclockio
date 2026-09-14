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
     * Re-applies the RobotClock backend's own intended access control:
     * revoke everything from anon/authenticated, then hand back exactly the
     * functions the kiosk and admin dashboard need.
     *
     * Supabase's default privileges grant ALL on every new table/view/
     * function in `public` to anon and authenticated, so these revokes are
     * not belt-and-braces — without them anon holds the place. That default
     * lands on every table Laravel creates on this connection, including
     * its own framework tables (cache, jobs, sessions, passkeys, etc.), so
     * those are revoked here too rather than left exposed.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        DB::statement(<<<'SQL'
            revoke all on public.organizations, public.teams, public.users, public.cards, public.punches,
                public.roles
                from anon, authenticated
            SQL);

        DB::statement(<<<'SQL'
            do $$
            begin
                if to_regclass('public.shift_sessions') is not null then
                    execute 'revoke all on public.shift_sessions from anon, authenticated';
                end if;

                if to_regclass('public.daily_hours') is not null then
                    execute 'revoke all on public.daily_hours from anon, authenticated';
                end if;
            end $$
            SQL);

        DB::statement(<<<'SQL'
            do $$
            declare
                t text;
            begin
                foreach t in array array[
                    'sessions', 'password_reset_tokens',
                    'cache', 'cache_locks',
                    'jobs', 'job_batches', 'failed_jobs',
                    'passkeys', 'migrations'
                ]
                loop
                    if to_regclass('public.' || t) is not null then
                        execute format('revoke all on public.%I from anon, authenticated', t);
                    end if;
                end loop;
            end $$
            SQL);

        DB::statement('revoke all on all functions in schema public from public, anon, authenticated');

        // Unauthenticated by design, but scoped: both demand a kiosk key.
        DB::statement('grant execute on function public.kiosk_roster(uuid) to anon');
        DB::statement('grant execute on function public.record_punches(uuid, jsonb) to anon');

        // Reachable with the app key; useless without a password.
        DB::statement('grant execute on function public.login(text, text) to anon');
        DB::statement('grant execute on function public.logout(uuid) to anon');
        DB::statement('grant execute on function public.admin_users(uuid) to anon');
        DB::statement(<<<'SQL'
            grant execute on function
                public.admin_save_user(uuid, uuid, text, text, text, uuid, boolean, uuid)
                to anon
            SQL);
        DB::statement('grant execute on function public.admin_set_password(uuid, uuid, text) to anon');
        DB::statement('grant execute on function public.admin_delete_user(uuid, uuid) to anon');
        DB::statement('grant execute on function public.admin_teams(uuid) to anon');
        DB::statement(<<<'SQL'
            grant execute on function
                public.admin_save_team(uuid, uuid, text, text, boolean, uuid)
                to anon
            SQL);
        DB::statement('grant execute on function public.admin_delete_team(uuid, uuid) to anon');
        DB::statement('grant execute on function public.admin_cards(uuid) to anon');
        DB::statement('grant execute on function public.admin_issue_card(uuid, text, uuid, text) to anon');
        DB::statement('grant execute on function public.admin_revoke_card(uuid, text) to anon');
        DB::statement('grant execute on function public.admin_daily_hours(uuid, date, date) to anon');
        DB::statement('grant execute on function public.admin_team_hours(uuid, date, date) to anon');
        DB::statement('grant execute on function public.admin_punches(uuid, date, date, int) to anon');
        DB::statement('grant execute on function public.admin_organization(uuid) to anon');
        DB::statement('grant execute on function public.admin_save_organization(uuid, text, text) to anon');
        DB::statement('grant execute on function public.admin_rotate_kiosk_key(uuid, uuid) to anon');
        DB::statement('grant execute on function public.owner_organizations(uuid) to anon');
        DB::statement(<<<'SQL'
            grant execute on function public.owner_save_organization(uuid, uuid, text, text, boolean)
                to anon
            SQL);
        DB::statement('grant execute on function public.owner_delete_organization(uuid, uuid) to anon');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Never auto-reopen the backend's access control on a rollback
        // outside testing.
    }
};
