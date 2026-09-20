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
        if (Schema::hasTable('teams')) {
            return;
        }

        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->index('organization_id', 'teams_organization_id_idx');
            // Team names are unique per organization, not globally.
            $table->unique(['organization_id', 'name'], 'teams_name_unique_per_org');
        });

        DB::statement(<<<'SQL'
            alter table teams
                add constraint teams_name_check check (length(trim(name)) > 0)
            SQL);

        DB::statement(<<<'SQL'
            comment on table teams is 'Groups users for reporting. Carries no permissions.'
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
