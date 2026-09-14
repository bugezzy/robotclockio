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
        if (app()->environment('testing')) {
            return;
        }

        if (! Schema::hasTable('license_issuances')) {
            Schema::create('license_issuances', function (Blueprint $table) {
                $table->uuid('id')->primary()->default(new Expression('gen_random_uuid()'));
                $table->foreignUuid('organization_id')->constrained()->cascadeOnDelete();
                $table->foreignUuid('issued_by')->nullable()->constrained('users')->nullOnDelete();
                $table->uuid('kiosk_key');
                $table->timestampTz('issued_at')->useCurrent();

                $table->index('organization_id', 'license_issuances_organization_id_idx');
            });

            DB::statement(<<<'SQL'
                comment on table license_issuances is
                    'Audit trail of RCLK1 kiosk licence generation. The licence string itself is never '
                    'stored, only the fact and circumstances of having issued one.'
                SQL);

            DB::statement(<<<'SQL'
                comment on column license_issuances.kiosk_key is
                    'The organization''s kiosk_key at the moment this licence was issued, so a later '
                    'rotation doesn''t erase which key a past licence actually carried.'
                SQL);
        }

        // Supabase grants anon/authenticated full access to every new public
        // table by default; every other table gets this revoke in
        // apply_grants, but this table was added after that migration.
        DB::statement('revoke all on public.license_issuances from anon, authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        Schema::dropIfExists('license_issuances');
    }
};
