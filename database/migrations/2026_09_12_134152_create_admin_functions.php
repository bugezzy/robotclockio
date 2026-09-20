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
     * The admin/owner/system management surface. Every function resolves
     * the caller through private.session_actor(p_token) and filters on that
     * caller's organization (a system-tier caller sees across all of them)
     * — that filter is the security boundary, written out in each function
     * rather than hidden behind something that could be left off.
     *
     * Role tiers, each a superset of the one before: admin (reads their
     * org's data, issues/revokes cards) < owner (also manages users/teams,
     * edits their own organization) < system (all of the above, across
     * every organization, plus creating/licensing organizations — see
     * create_owner_functions).
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function public.admin_users(p_token uuid)
            returns table (id uuid, full_name text, email text, role text, active boolean,
                           team_id uuid, team_name text, card_uid text, has_password boolean,
                           organization_id uuid, organization_name text)
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
                select u.id, u.full_name::text, u.email::text, u.role::text, u.active,
                       u.team_id, t.name::text,
                       (select c.card_uid from public.cards c
                         where c.user_id = u.id and c.revoked_at is null limit 1),
                       u.password_hash is not null,
                       u.organization_id, o.name::text
                from public.users u
                left join public.teams t on t.id = u.team_id
                left join public.organizations o on o.id = u.organization_id
                where v_role = 'system' or u.organization_id = v_org
                order by o.name nulls first, u.full_name;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_save_user(p_token uuid, p_id uuid, p_full_name text, p_email text,
                                                   p_role text, p_team_id uuid, p_active boolean,
                                                   p_organization_id uuid)
            returns uuid
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role   text;
                v_org    uuid;
                v_target uuid;
                v_id     uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                if v_role not in ('owner', 'system') then
                    raise exception 'not permitted';
                end if;

                v_target := private.target_org(v_role, v_org, p_organization_id);

                if p_role = 'system' and v_role <> 'system' then
                    raise exception 'only a system user can appoint a system user';
                end if;

                if p_id is null then
                    insert into public.users (full_name, email, role, team_id, active, organization_id)
                    values (trim(p_full_name), nullif(trim(lower(coalesce(p_email, ''))), ''),
                            coalesce(p_role, 'member'), p_team_id, coalesce(p_active, true), v_target)
                    returning users.id into v_id;
                else
                    update public.users
                       set full_name       = trim(p_full_name),
                           email           = nullif(trim(lower(coalesce(p_email, ''))), ''),
                           role            = coalesce(p_role, 'member'),
                           team_id         = p_team_id,
                           active          = coalesce(p_active, true),
                           organization_id = v_target
                     where users.id = p_id
                       and (v_role = 'system' or users.organization_id = v_org)
                    returning users.id into v_id;

                    if v_id is null then
                        raise exception 'no such user';
                    end if;
                end if;

                return v_id;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_set_password(p_token uuid, p_user_id uuid, p_password text)
            returns void
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_org  uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                if v_role not in ('owner', 'system') then
                    raise exception 'not permitted';
                end if;

                if length(coalesce(p_password, '')) < 8 then
                    raise exception 'password must be at least 8 characters';
                end if;

                update public.users u
                   set password_hash = extensions.crypt(p_password, extensions.gen_salt('bf'))
                 where u.id = p_user_id
                   and (v_role = 'system' or u.organization_id = v_org);

                if not found then
                    raise exception 'no such user';
                end if;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_delete_user(p_token uuid, p_user_id uuid)
            returns void
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_org  uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                if v_role not in ('owner', 'system') then
                    raise exception 'not permitted';
                end if;

                delete from public.users u
                 where u.id = p_user_id
                   and (v_role = 'system' or u.organization_id = v_org);

                if not found then
                    raise exception 'no such user';
                end if;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_teams(p_token uuid)
            returns table (id uuid, name text, description text, active boolean, member_count bigint,
                           organization_id uuid, organization_name text)
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
                select t.id, t.name::text, t.description, t.active,
                       (select count(*) from public.users u where u.team_id = t.id),
                       t.organization_id, o.name::text
                from public.teams t
                join public.organizations o on o.id = t.organization_id
                where v_role = 'system' or t.organization_id = v_org
                order by o.name, t.name;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_save_team(p_token uuid, p_id uuid, p_name text,
                                                   p_description text, p_active boolean,
                                                   p_organization_id uuid)
            returns uuid
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role   text;
                v_org    uuid;
                v_target uuid;
                v_id     uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                if v_role not in ('owner', 'system') then
                    raise exception 'not permitted';
                end if;

                v_target := private.target_org(v_role, v_org, p_organization_id);

                if p_id is null then
                    insert into public.teams (name, description, active, organization_id)
                    values (trim(p_name), nullif(trim(coalesce(p_description, '')), ''),
                            coalesce(p_active, true), v_target)
                    returning teams.id into v_id;
                else
                    update public.teams
                       set name            = trim(p_name),
                           description     = nullif(trim(coalesce(p_description, '')), ''),
                           active          = coalesce(p_active, true),
                           organization_id = v_target
                     where teams.id = p_id
                       and (v_role = 'system' or teams.organization_id = v_org)
                    returning teams.id into v_id;

                    if v_id is null then
                        raise exception 'no such team';
                    end if;
                end if;

                return v_id;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_delete_team(p_token uuid, p_id uuid)
            returns void
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_org  uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                if v_role not in ('owner', 'system') then
                    raise exception 'not permitted';
                end if;

                delete from public.teams t
                 where t.id = p_id
                   and (v_role = 'system' or t.organization_id = v_org);

                if not found then
                    raise exception 'no such team';
                end if;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_cards(p_token uuid)
            returns table (card_uid text, user_id uuid, full_name text, label text,
                           issued_at timestamptz, revoked_at timestamptz)
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
                select c.card_uid, c.user_id, u.full_name::text, c.label, c.issued_at, c.revoked_at
                from public.cards c
                join public.users u on u.id = c.user_id
                where v_role = 'system' or u.organization_id = v_org
                order by c.revoked_at nulls first, u.full_name, c.issued_at desc;
            end;
            $fn$;
            SQL);

        // Issuing and revoking cards is admin's core job — no rank gate
        // beyond the admin+ that session_actor already requires.
        DB::unprepared(<<<'SQL'
            create or replace function public.admin_issue_card(p_token uuid, p_card_uid text,
                                                       p_user_id uuid, p_label text)
            returns text
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_org  uuid;
                v_uid  text;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                v_uid := upper(regexp_replace(coalesce(p_card_uid, ''), '[^0-9A-Fa-f]', '', 'g'));
                if length(v_uid) < 4 then
                    raise exception 'that does not look like a card uid';
                end if;

                if not exists (
                    select 1 from public.users u
                    where u.id = p_user_id and (v_role = 'system' or u.organization_id = v_org)
                ) then
                    raise exception 'no such user';
                end if;

                if exists (
                    select 1 from public.cards c
                    join public.users u on u.id = c.user_id
                    where c.card_uid = v_uid
                      and c.revoked_at is null
                      and u.organization_id is distinct from
                          (select u2.organization_id from public.users u2 where u2.id = p_user_id)
                ) then
                    raise exception 'that card is already issued in another organization';
                end if;

                update public.cards c
                   set revoked_at = now()
                 where c.user_id = p_user_id and c.revoked_at is null;

                insert into public.cards (card_uid, user_id, label)
                values (v_uid, p_user_id, nullif(trim(coalesce(p_label, '')), ''))
                on conflict (card_uid) do update
                    set user_id    = excluded.user_id,
                        label      = excluded.label,
                        issued_at  = now(),
                        revoked_at = null;

                return v_uid;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_revoke_card(p_token uuid, p_card_uid text)
            returns void
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_org  uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                update public.cards c
                   set revoked_at = now()
                 where c.card_uid = p_card_uid
                   and c.revoked_at is null
                   and exists (
                       select 1 from public.users u
                       where u.id = c.user_id and (v_role = 'system' or u.organization_id = v_org)
                   );

                if not found then
                    raise exception 'no such active card';
                end if;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_rotate_kiosk_key(p_token uuid, p_organization_id uuid)
            returns uuid
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role   text;
                v_org    uuid;
                v_target uuid;
                v_key    uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                if v_role not in ('owner', 'system') then
                    raise exception 'not permitted';
                end if;

                v_target := private.target_org(v_role, v_org, p_organization_id);

                update public.organizations o
                   set kiosk_key = gen_random_uuid()
                 where o.id = v_target
                returning o.kiosk_key into v_key;

                if v_key is null then
                    raise exception 'no such organization';
                end if;

                return v_key;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.admin_organization(p_token uuid)
            returns table (id uuid, name text, description text, active boolean, kiosk_key uuid,
                           team_count bigint, member_count bigint)
            language plpgsql
            stable
            security definer
            set search_path = ''
            as $fn$
            declare
                v_org uuid;
            begin
                select a.organization_id into v_org from private.session_actor(p_token) a;

                return query
                select o.id, o.name::text, o.description, o.active, o.kiosk_key,
                       (select count(*) from public.teams t where t.organization_id = o.id),
                       (select count(*) from public.users u where u.organization_id = o.id)
                from public.organizations o
                where o.id = v_org;
            end;
            $fn$;
            SQL);

        // New: an owner editing their own organization's name/description.
        // Distinct from owner_save_organization (system-only, any org by
        // id, and the only way to create a new organization at all).
        DB::unprepared(<<<'SQL'
            create or replace function public.admin_save_organization(p_token uuid, p_name text, p_description text)
            returns uuid
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_role text;
                v_org  uuid;
                v_id   uuid;
            begin
                select a.role, a.organization_id into v_role, v_org from private.session_actor(p_token) a;

                if v_role not in ('owner', 'system') then
                    raise exception 'not permitted';
                end if;

                if v_org is null then
                    raise exception 'no organization is associated with your session';
                end if;

                update public.organizations
                   set name        = trim(p_name),
                       description = nullif(trim(coalesce(p_description, '')), '')
                 where organizations.id = v_org
                returning organizations.id into v_id;

                if v_id is null then
                    raise exception 'no such organization';
                end if;

                return v_id;
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
