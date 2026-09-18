<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        if (app()->environment('testing') || Schema::hasColumn('users', 'discord_user_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->text('discord_user_id')->nullable()->unique()->after('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('discord_user_id');
        });
    }
};
