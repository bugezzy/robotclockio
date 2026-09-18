<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The `users` table here only ever runs during testing: the real one
     * lives on the `supabase` connection (default connection now that it's
     * `DB_CONNECTION=supabase`), created by
     * database/migrations/*_create_supabase_users_table.php. This mirrors
     * that table's shape so tests — which run against sqlite instead (see
     * App\Models\User::getConnectionName()) — exercise the same columns.
     * `password_reset_tokens` and `sessions` don't collide with anything in
     * the backend's schema, so they're created for real too.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            Schema::create('users', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('full_name');
                $table->string('email')->nullable()->unique();
                $table->string('password_hash')->nullable();
                $table->uuid('team_id')->nullable();
                $table->string('role')->default('member');
                $table->boolean('active')->default(true);
                $table->uuid('organization_id')->nullable();
                $table->text('discord_user_id')->nullable()->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->environment('testing')) {
            Schema::dropIfExists('users');
        }

        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
