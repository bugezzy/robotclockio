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
     * The `private` schema holds session storage and internal helper
     * functions that must never be reachable directly over the API —
     * PostgREST only exposes `public`. Functions use CREATE OR REPLACE,
     * which is idempotent by nature, so no existence guard is needed beyond
     * the testing check.
     */
    public function up(): void
    {
        DB::statement('create schema if not exists private');
        DB::statement('revoke all on schema private from public');

        if (! DB::table('information_schema.tables')
            ->where('table_schema', 'private')
            ->where('table_name', 'sessions')
            ->exists()) {
            DB::statement(<<<'SQL'
                create table private.sessions (
                    token      uuid primary key default gen_random_uuid(),
                    user_id    uuid not null references public.users(id) on delete cascade,
                    created_at timestamptz not null default now(),
                    expires_at timestamptz not null default now() + interval '12 hours'
                )
                SQL);

            DB::statement('create index sessions_user_id_idx on private.sessions (user_id)');
            DB::statement('create index sessions_expires_at_idx on private.sessions (expires_at)');
        }

        // Generic updated_at trigger function, used by teams/users/organizations.
        DB::unprepared(<<<'SQL'
            create or replace function private.touch_updated_at()
            returns trigger
            language plpgsql
            set search_path = ''
            as $fn$
            begin
                new.updated_at := now();
                return new;
            end;
            $fn$;
            SQL);

        // Older admin-only token check. Superseded in practice by session_actor
        // below, but still declared (and callable) in the live schema.
        DB::unprepared(<<<'SQL'
            create or replace function private.session_user(p_token uuid)
            returns uuid
            language plpgsql
            stable
            set search_path = ''
            as $fn$
            declare
                v_user uuid;
            begin
                if p_token is null then
                    raise exception 'not signed in' using errcode = '28000';
                end if;

                select s.user_id into v_user
                from private.sessions s
                join public.users u on u.id = s.user_id
                where s.token = p_token
                  and s.expires_at > now()
                  and u.active
                  and u.role in ('admin', 'owner', 'system');

                if v_user is null then
                    raise exception 'session is invalid or has expired' using errcode = '28000';
                end if;

                return v_user;
            end;
            $fn$;
            SQL);

        // Resolves the caller's identity, role, and organization from a
        // session token. What almost every admin/owner RPC uses.
        DB::unprepared(<<<'SQL'
            create or replace function private.session_actor(p_token uuid)
            returns table (user_id uuid, role text, organization_id uuid)
            language plpgsql
            stable
            set search_path = ''
            as $fn$
            begin
                if p_token is null then
                    raise exception 'not signed in' using errcode = '28000';
                end if;

                -- users.role is varchar; the declared return type above is
                -- text, and RETURN QUERY requires an exact match, not just a
                -- castable one — see 2026_09_12_210001 for how this class of
                -- bug surfaced in public.login().
                return query
                select u.id, u.role::text, u.organization_id
                from private.sessions s
                join public.users u on u.id = s.user_id
                where s.token = p_token
                  and s.expires_at > now()
                  and u.active
                  and u.role in ('admin', 'owner', 'system');

                if not found then
                    raise exception 'session is invalid or has expired' using errcode = '28000';
                end if;
            end;
            $fn$;
            SQL);

        // Which organization a write should land in: an owner's own, or the
        // one a system-tier caller names. The tenant-isolation enforcement
        // point.
        DB::unprepared(<<<'SQL'
            create or replace function private.target_org(p_role text, p_actor_org uuid, p_requested uuid)
            returns uuid
            language plpgsql
            immutable
            set search_path = ''
            as $fn$
            begin
                if p_role = 'system' then
                    if coalesce(p_requested, p_actor_org) is null then
                        raise exception 'which organization? a system user must name one';
                    end if;
                    return coalesce(p_requested, p_actor_org);
                end if;

                if p_requested is not null and p_requested <> p_actor_org then
                    raise exception 'that organization is not yours';
                end if;
                return p_actor_org;
            end;
            $fn$;
            SQL);

        // Resolves a kiosk's key to its organization — the kiosk's entire
        // identity.
        DB::unprepared(<<<'SQL'
            create or replace function private.kiosk_org(p_kiosk_key uuid)
            returns uuid
            language plpgsql
            stable
            set search_path = ''
            as $fn$
            declare
                v_org uuid;
            begin
                if p_kiosk_key is null then
                    raise exception 'this kiosk is not licensed to an organization' using errcode = '28000';
                end if;

                select o.id into v_org
                from public.organizations o
                where o.kiosk_key = p_kiosk_key and o.active;

                if v_org is null then
                    raise exception 'this kiosk key is not recognised' using errcode = '28000';
                end if;

                return v_org;
            end;
            $fn$;
            SQL);

        // Trigger function: a person's team must be in their own
        // organization. A trigger rather than a composite FK because ON
        // DELETE SET NULL on a composite FK would null organization_id too
        // and violate users_org_unless_system.
        DB::unprepared(<<<'SQL'
            create or replace function private.user_team_in_same_org()
            returns trigger
            language plpgsql
            set search_path = ''
            as $fn$
            begin
                if new.team_id is not null and not exists (
                    select 1 from public.teams t
                    where t.id = new.team_id and t.organization_id = new.organization_id
                ) then
                    raise exception 'that team belongs to a different organization';
                end if;
                return new;
            end;
            $fn$;
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Never auto-drop shared business logic (or private.sessions, which
        // the RobotClock backend's own login()/logout() depend on) on a
        // rollback outside testing.
    }
};
