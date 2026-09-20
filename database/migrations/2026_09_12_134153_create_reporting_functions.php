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
     * Read-only, org-scoped reporting over the daily_hours view.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function public.admin_daily_hours(p_token uuid, p_from date, p_to date)
            returns table (user_id uuid, full_name text, team_name text, work_date date,
                           session_count bigint, open_sessions bigint, hours numeric,
                           organization_name text)
            language plpgsql
            stable
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_org  uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                return query
                select d.user_id, d.full_name::text, d.team_name::text, d.work_date,
                       d.session_count, d.open_sessions, d.hours, d.organization_name::text
                from public.daily_hours d
                where d.work_date >= coalesce(p_from, current_date - 30)
                  and d.work_date <= coalesce(p_to, current_date)
                  and (v_role = 'system' or d.organization_id = v_org)
                order by d.work_date desc, d.organization_name, d.full_name;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_team_hours(p_token uuid, p_from date, p_to date)
            returns table (team_name text, people bigint, days bigint,
                           session_count bigint, open_sessions bigint, hours numeric,
                           organization_name text)
            language plpgsql
            stable
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_org  uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                return query
                select coalesce(d.team_name, '(no team)')::text,
                       count(distinct d.user_id),
                       count(distinct d.work_date),
                       sum(d.session_count)::bigint,
                       sum(d.open_sessions)::bigint,
                       coalesce(sum(d.hours), 0),
                       d.organization_name::text
                from public.daily_hours d
                where d.work_date >= coalesce(p_from, current_date - 30)
                  and d.work_date <= coalesce(p_to, current_date)
                  and (v_role = 'system' or d.organization_id = v_org)
                group by coalesce(d.team_name, '(no team)'), d.organization_name
                order by 7, 1;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_punches(p_token uuid, p_from date, p_to date, p_limit int)
            returns table (id uuid, card_uid text, full_name text, direction text,
                           punched_at timestamptz, recorded_at timestamptz, organization_name text)
            language plpgsql
            stable
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_org  uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                return query
                select p.id, p.card_uid, u.full_name::text, p.direction, p.punched_at, p.recorded_at,
                       o.name::text
                from public.punches p
                left join public.users u on u.id = p.user_id
                join public.organizations o on o.id = p.organization_id
                where (p.punched_at at time zone 'America/Chicago')::date
                          >= coalesce(p_from, current_date - 30)
                  and (p.punched_at at time zone 'America/Chicago')::date
                          <= coalesce(p_to, current_date)
                  and (v_role = 'system' or p.organization_id = v_org)
                order by p.punched_at desc
                limit least(coalesce(p_limit, 500), 2000);
            end;
            $fn$;
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Never auto-drop shared business logic on a rollback outside testing.
    }
};
