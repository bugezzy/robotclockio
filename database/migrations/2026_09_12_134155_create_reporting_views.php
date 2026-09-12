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
     * `kiosk_roster` existed as a view before organizations were added, but
     * was dropped in favor of a kiosk-key-scoped function of the same name
     * (see create_auth_and_kiosk_functions) — do not recreate it as a view.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        // Pairs each `in` punch with its next `out` via a window function.
        // An `in` with no following `out` (or two `in`s in a row) surfaces
        // as an open session (`ended_at` null) rather than being dropped.
        DB::statement(<<<'SQL'
            create or replace view public.shift_sessions
            with (security_invoker = true) as
            with ordered as (
                select p.user_id,
                       p.punched_at,
                       p.direction,
                       lead(p.direction)  over w as next_direction,
                       lead(p.punched_at) over w as next_punched_at
                from public.punches p
                where p.user_id is not null
                window w as (partition by p.user_id order by p.punched_at)
            )
            select o.user_id,
                   o.punched_at as started_at,
                   case when o.next_direction = 'out' then o.next_punched_at end as ended_at,
                   case when o.next_direction = 'out' then o.next_punched_at - o.punched_at end as duration
            from ordered o
            where o.direction = 'in'
            SQL);

        // Rolls shift_sessions up per user per work-date. A shift crossing
        // midnight is attributed to the day it started (America/Chicago).
        DB::statement(<<<'SQL'
            create or replace view public.daily_hours
            with (security_invoker = true) as
            select s.user_id,
                   u.full_name,
                   t.name as team_name,
                   (s.started_at at time zone 'America/Chicago')::date as work_date,
                   count(*) as session_count,
                   count(*) filter (where s.ended_at is null) as open_sessions,
                   sum(s.duration) as worked,
                   round(extract(epoch from sum(s.duration)) / 3600.0, 2) as hours,
                   u.organization_id,
                   o.name as organization_name
            from public.shift_sessions s
            join public.users u on u.id = s.user_id
            left join public.organizations o on o.id = u.organization_id
            left join public.teams t on t.id = u.team_id
            group by s.user_id, u.full_name, t.name, u.organization_id, o.name,
                     (s.started_at at time zone 'America/Chicago')::date
            SQL);

        DB::statement('revoke all on public.daily_hours from anon, authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Never auto-drop shared views on a rollback outside testing.
    }
};
