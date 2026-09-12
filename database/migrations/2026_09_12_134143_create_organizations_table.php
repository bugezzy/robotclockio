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
        if (app()->environment('testing') || Schema::hasTable('organizations')) {
            return;
        }

        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->uuid('kiosk_key')->unique()->default(new Expression('gen_random_uuid()'));
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            alter table organizations
                add constraint organizations_name_check check (length(trim(name)) > 0)
            SQL);

        DB::statement(<<<'SQL'
            comment on table organizations is
                'The tenant. Everything else in this schema belongs to exactly one of these.'
            SQL);

        DB::statement(<<<'SQL'
            comment on column organizations.kiosk_key is
                'What a kiosk presents to prove which organization it is. Treat as extractable from any '
                'install of that organization''s app, and rotate it when one is lost.'
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        Schema::dropIfExists('organizations');
    }
};
