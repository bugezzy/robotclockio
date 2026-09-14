<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The RobotClock database this app owns, not this application's own
     * default connection.
     */
    protected $connection = 'supabase';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (app()->environment('testing') || Schema::hasTable('releases')) {
            return;
        }

        Schema::create('releases', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(new Expression('gen_random_uuid()'));
            $table->string('platform');
            $table->string('version');
            $table->text('url');
            $table->boolean('is_latest')->default(false);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['platform', 'version']);
            $table->index('platform', 'releases_platform_idx');
        });

        DB::statement(<<<'SQL'
            alter table releases
                add constraint releases_platform_check check (platform in ('windows', 'linux'))
            SQL);

        DB::statement(<<<'SQL'
            alter table releases
                add constraint releases_version_check check (length(trim(version)) > 0)
            SQL);

        // One "latest" tag per platform.
        DB::statement(<<<'SQL'
            create unique index releases_one_latest_per_platform
                on releases (platform) where is_latest
            SQL);

        DB::statement(<<<'SQL'
            comment on table releases is
                'Kiosk desktop app build history, one row per (platform, version). The kiosk checks '
                'public.check_for_update() against whichever row currently has is_latest.'
            SQL);

        // Managed exclusively through the admin panel (system users); the
        // kiosk only ever reaches this via the security-definer function
        // below, never the table directly.
        DB::statement('revoke all on public.releases from anon, authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        Schema::dropIfExists('releases');
    }
};
