<?php

use Illuminate\Database\Migrations\Migration;
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
        if (Schema::hasTable('cards')) {
            return;
        }

        Schema::create('cards', function (Blueprint $table) {
            $table->text('card_uid')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('label')->nullable();
            $table->timestampTz('issued_at')->useCurrent();
            $table->timestampTz('revoked_at')->nullable();

            $table->index('user_id', 'cards_user_id_idx');
        });

        DB::statement(<<<'SQL'
            alter table cards
                add constraint cards_card_uid_check
                check (card_uid ~ '^[0-9A-F]+$' and length(card_uid) between 4 and 32)
            SQL);

        // One active (non-revoked) card per person.
        DB::statement(<<<'SQL'
            create unique index cards_one_active_per_user
                on cards (user_id) where revoked_at is null
            SQL);

        DB::statement(<<<'SQL'
            comment on table cards is 'Card issuance history. A lost card is revoked, never deleted.'
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
