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
     * Card UIDs that have been scanned at a kiosk but aren't tied to anyone
     * (a punch is recorded with a null user_id when the card isn't issued).
     * A UID that currently has an active card is left out, so what remains
     * are cards still waiting to be issued — including revoked ones that
     * got scanned. Like every admin_* function, it resolves the caller through
     * private.session_actor(p_token) and filters on that caller's
     * organization — a system-tier caller sees across all of them.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function public.admin_unassigned_card_scans(p_token uuid)
            returns table (card_uid text, last_seen timestamptz, scans bigint)
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
                select p.card_uid, max(p.punched_at), count(*)
                from public.punches p
                where p.user_id is null
                  and (v_role = 'system' or p.organization_id = v_org)
                  and not exists (select 1 from public.cards c
                                  where c.card_uid = p.card_uid and c.revoked_at is null)
                group by p.card_uid
                order by max(p.punched_at) desc;
            end;
            $fn$;
            SQL);

        DB::statement('revoke all on function public.admin_unassigned_card_scans(uuid) from public, anon, authenticated');
        DB::statement('grant execute on function public.admin_unassigned_card_scans(uuid) to anon');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('drop function if exists public.admin_unassigned_card_scans(uuid)');
    }
};
