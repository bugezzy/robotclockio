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
        if (app()->environment('testing') || Schema::hasColumn('products', 'image_url')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->text('image_url')->nullable()->after('description');
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

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image_url');
        });
    }
};
