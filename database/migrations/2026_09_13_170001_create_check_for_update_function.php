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
     * Unauthenticated by design, same as kiosk_roster()/record_punches() —
     * a kiosk has no account, only a build of the app asking "is there
     * something newer than me for my platform?" There's nothing
     * organization-specific here, so no kiosk key is needed either.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function public.check_for_update(p_platform text, p_current_version text)
            returns table (update_available boolean, latest_version text, download_url text)
            language plpgsql
            stable
            security definer
            set search_path = ''
            as $fn$
            declare
                v_release public.releases;
            begin
                select r.* into v_release
                from public.releases r
                where r.platform = lower(trim(p_platform))
                  and r.is_latest;

                if v_release.id is null then
                    return query select false, null::text, null::text;
                    return;
                end if;

                return query select
                    v_release.version is distinct from trim(p_current_version),
                    v_release.version::text,
                    v_release.url::text;
            end;
            $fn$;
            SQL);

        DB::statement('revoke all on function public.check_for_update(text, text) from public, anon, authenticated');
        DB::statement('grant execute on function public.check_for_update(text, text) to anon');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Never auto-drop shared business logic on a rollback outside testing.
    }
};
