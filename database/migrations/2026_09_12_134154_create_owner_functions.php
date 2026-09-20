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
     * Cross-organization functions reachable only by a system-tier caller.
     * Function names stay owner_* for API compatibility with existing
     * callers — only the internal role check changed.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function public.owner_organizations(p_token uuid)
            returns table (id uuid, name text, description text, active boolean, kiosk_key uuid,
                           team_count bigint, member_count bigint)
            language plpgsql
            stable
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
            begin
                select a.role into v_role from private.session_actor(p_token) a;
                if v_role <> 'system' then
                    raise exception 'only a system user can list organizations';
                end if;

                return query
                select o.id, o.name::text, o.description, o.active, o.kiosk_key,
                       (select count(*) from public.teams t where t.organization_id = o.id),
                       (select count(*) from public.users u where u.organization_id = o.id)
                from public.organizations o
                order by o.name;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.owner_save_organization(p_token uuid, p_id uuid, p_name text,
                                                           p_description text, p_active boolean)
            returns uuid
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_id   uuid;
            begin
                select a.role into v_role from private.session_actor(p_token) a;
                if v_role <> 'system' then
                    raise exception 'only a system user can create or rename organizations';
                end if;

                if p_id is null then
                    insert into public.organizations (name, description, active)
                    values (trim(p_name), nullif(trim(coalesce(p_description, '')), ''),
                            coalesce(p_active, true))
                    returning organizations.id into v_id;
                else
                    update public.organizations
                       set name        = trim(p_name),
                           description = nullif(trim(coalesce(p_description, '')), ''),
                           active      = coalesce(p_active, true)
                     where organizations.id = p_id
                    returning organizations.id into v_id;

                    if v_id is null then
                        raise exception 'no such organization';
                    end if;
                end if;

                return v_id;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.owner_delete_organization(p_token uuid, p_id uuid)
            returns void
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_n    bigint;
            begin
                select a.role into v_role from private.session_actor(p_token) a;
                if v_role <> 'system' then
                    raise exception 'only a system user can delete an organization';
                end if;

                select count(*) into v_n from public.users u where u.organization_id = p_id;
                if v_n > 0 then
                    raise exception 'that organization still has % people in it', v_n;
                end if;

                select count(*) into v_n from public.teams t where t.organization_id = p_id;
                if v_n > 0 then
                    raise exception 'that organization still has % teams in it', v_n;
                end if;

                select count(*) into v_n from public.punches p where p.organization_id = p_id;
                if v_n > 0 then
                    raise exception 'that organization has % punches recorded against it', v_n;
                end if;

                delete from public.organizations o where o.id = p_id;

                if not found then
                    raise exception 'no such organization';
                end if;
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
