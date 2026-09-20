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
     * The public-facing, unauthenticated surface: an admin/owner signs in
     * with login(), and the kiosk (holding only an organization's kiosk_key,
     * no account) reads its roster and records punches.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function public.login(p_email text, p_password text)
            returns table (token uuid, id uuid, full_name text, email text, role text,
                           organization_id uuid, organization_name text)
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_user  public.users;
                v_token uuid;
                v_org   text;
            begin
                -- This server's pgcrypto doesn't recognise the $2y$ prefix
                -- PHP's bcrypt hasher writes (App\Models\User::password()) —
                -- it silently falls back to DES and never matches. $2a/$2b/
                -- $2x/$2y are the same bcrypt algorithm under the hood, so
                -- normalizing the prefix before hashing/comparing verifies
                -- correctly no matter which side wrote the hash.
                select u.* into v_user
                from public.users u
                where u.email = lower(trim(p_email))
                  and u.active
                  and u.role in ('admin', 'owner', 'system')
                  and u.password_hash is not null
                  and regexp_replace(u.password_hash, '^\$2[abxy]\$', '$2a$')
                      = extensions.crypt(p_password, regexp_replace(u.password_hash, '^\$2[abxy]\$', '$2a$'));

                if v_user.id is null then
                    return;
                end if;

                if v_user.organization_id is not null and not exists (
                    select 1 from public.organizations o
                    where o.id = v_user.organization_id and o.active
                ) then
                    return;
                end if;

                select o.name into v_org
                from public.organizations o where o.id = v_user.organization_id;

                delete from private.sessions s where s.expires_at <= now();

                insert into private.sessions (user_id) values (v_user.id)
                returning private.sessions.token into v_token;

                return query select v_token, v_user.id, v_user.full_name::text, v_user.email::text,
                                    v_user.role::text, v_user.organization_id, v_org;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.logout(p_token uuid)
            returns void
            language sql
            security definer
            set search_path = ''
            as $fn$
                delete from private.sessions s where s.token = p_token;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.kiosk_roster(p_kiosk_key uuid)
            returns table (card_uid text, display_name text, team_name text)
            language plpgsql
            stable
            security definer
            set search_path = ''
            as $fn$
            declare
                v_org uuid;
            begin
                v_org := private.kiosk_org(p_kiosk_key);

                return query
                select c.card_uid, u.full_name::text, t.name::text
                from public.cards c
                join public.users u on u.id = c.user_id
                left join public.teams t on t.id = u.team_id
                where c.revoked_at is null
                  and u.active
                  and u.organization_id = v_org;
            end;
            $fn$;
            SQL);

        DB::unprepared(<<<'SQL'
            create or replace function public.record_punches(p_kiosk_key uuid, p_punches jsonb)
            returns jsonb
            language plpgsql
            security definer
            set search_path = ''
            as $fn$
            declare
                v_org       uuid;
                v_item      jsonb;
                v_id        uuid;
                v_uid       text;
                v_direction text;
                v_at        timestamptz;
                v_user      uuid;
                v_accepted  uuid[] := '{}';
                v_rejected  jsonb  := '[]'::jsonb;
            begin
                v_org := private.kiosk_org(p_kiosk_key);

                if p_punches is null or jsonb_typeof(p_punches) <> 'array' then
                    raise exception 'record_punches expects a json array';
                end if;

                if jsonb_array_length(p_punches) > 500 then
                    raise exception 'batch too large: % punches (max 500)', jsonb_array_length(p_punches);
                end if;

                for v_item in select * from jsonb_array_elements(p_punches)
                loop
                    begin
                        v_id := (v_item ->> 'id')::uuid;
                    exception when others then
                        v_id := null;
                    end;

                    if v_id is null then
                        v_rejected := v_rejected || jsonb_build_object(
                            'id', v_item ->> 'id', 'reason', 'missing or malformed id');
                        continue;
                    end if;

                    v_uid := upper(regexp_replace(coalesce(v_item ->> 'card_uid', ''), '[^0-9A-Fa-f]', '', 'g'));
                    if v_uid = '' then
                        v_rejected := v_rejected || jsonb_build_object(
                            'id', v_id, 'reason', 'missing or malformed card_uid');
                        continue;
                    end if;

                    v_direction := lower(coalesce(v_item ->> 'direction', ''));
                    if v_direction not in ('in', 'out') then
                        v_rejected := v_rejected || jsonb_build_object(
                            'id', v_id, 'reason', 'direction must be in or out');
                        continue;
                    end if;

                    begin
                        v_at := (v_item ->> 'punched_at')::timestamptz;
                    exception when others then
                        v_at := null;
                    end;

                    if v_at is null then
                        v_rejected := v_rejected || jsonb_build_object(
                            'id', v_id, 'reason', 'missing or malformed punched_at');
                        continue;
                    end if;

                    if v_at > now() + interval '1 minute' then
                        v_rejected := v_rejected || jsonb_build_object(
                            'id', v_id, 'reason', 'punched_at is in the future');
                        continue;
                    end if;

                    if v_at < now() - interval '30 days' then
                        v_rejected := v_rejected || jsonb_build_object(
                            'id', v_id, 'reason', 'punched_at is more than 30 days old');
                        continue;
                    end if;

                    v_user := null;
                    select c.user_id into v_user
                    from public.cards c
                    join public.users u on u.id = c.user_id
                    where c.card_uid = v_uid
                      and c.revoked_at is null
                      and u.organization_id = v_org;

                    insert into public.punches (id, card_uid, user_id, organization_id, direction, punched_at)
                    values (v_id, v_uid, v_user, v_org, v_direction, v_at)
                    on conflict (id) do nothing;

                    v_accepted := v_accepted || v_id;
                end loop;

                return jsonb_build_object('accepted', to_jsonb(v_accepted), 'rejected', v_rejected);
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
