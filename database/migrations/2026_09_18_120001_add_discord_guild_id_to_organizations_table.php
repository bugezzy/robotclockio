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
        if (Schema::hasColumn('organizations', 'discord_guild_id')) {
            return;
        }

        Schema::table('organizations', function (Blueprint $table) {
            $table->text('discord_guild_id')->nullable()->unique()->after('kiosk_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('discord_guild_id');
        });
    }
};
