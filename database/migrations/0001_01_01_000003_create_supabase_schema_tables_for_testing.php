<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * These tables only ever run during testing: the real ones live on the
     * `supabase` connection, created by this app's own `create_*_table`
     * migrations. This mirrors their shape so tests — which run against
     * sqlite instead (see `getConnectionName()` on each of
     * App\Models\{Organization,Team,Card,Punch,LicenseIssuance}, and
     * App\Models\Role, which already uses the default connection in both
     * environments) — exercise the same columns, without the Postgres-only
     * CHECK constraints, partial unique indexes, or cross-table foreign
     * keys the live tables carry. App\Models\{Release,Order,OrderItem,Product}
     * follow the same `getConnectionName()` pattern.
     */
    public function up(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->text('description');
            $table->unsignedSmallInteger('rank')->unique();
        });

        DB::table('roles')->insert([
            ['id' => (string) Str::uuid(), 'name' => 'member', 'description' => 'Plain employee. No admin dashboard access.', 'rank' => 0],
            ['id' => (string) Str::uuid(), 'name' => 'admin', 'description' => 'Team admin. Adds and removes cards.', 'rank' => 1],
            ['id' => (string) Str::uuid(), 'name' => 'owner', 'description' => 'Creates teams and edits the organization.', 'rank' => 2],
            ['id' => (string) Str::uuid(), 'name' => 'system', 'description' => 'Full access to every aspect. Creates and licenses organizations.', 'rank' => 3],
        ]);

        Schema::create('organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->uuid('kiosk_key')->nullable()->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->uuid('organization_id');
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('cards', function (Blueprint $table) {
            $table->text('card_uid')->primary();
            $table->uuid('user_id');
            $table->text('label')->nullable();
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
        });

        Schema::create('punches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('card_uid');
            $table->uuid('user_id')->nullable();
            $table->text('direction');
            $table->timestamp('punched_at');
            $table->timestamp('recorded_at')->useCurrent();
            $table->uuid('organization_id');
        });

        Schema::create('license_issuances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('issued_by')->nullable();
            $table->uuid('kiosk_key');
            $table->timestamp('issued_at')->useCurrent();
        });

        Schema::create('releases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('platform');
            $table->string('version');
            $table->text('url');
            $table->boolean('is_latest')->default(false);
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['platform', 'version']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('image_url')->nullable();
            $table->integer('price_cents');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('products')->insert([
            ['id' => (string) Str::uuid(), 'name' => 'Standard RFID Card', 'description' => 'A basic RFID card for clocking in and out.', 'price_cents' => 100, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'name' => 'Premium RFID Card', 'description' => 'A durable, printable RFID card for clocking in and out.', 'price_cents' => 200, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'name' => 'Standard RFID Reader', 'description' => 'A kiosk-ready RFID reader for recording punches.', 'price_cents' => 2500, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'name' => 'Premium RFID Reader', 'description' => 'A faster, more durable RFID reader for high-traffic kiosks.', 'price_cents' => 3000, 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('created_by')->nullable();
            $table->text('stripe_checkout_session_id')->unique();
            $table->text('stripe_payment_intent_id')->nullable();
            $table->string('status')->default('pending');
            $table->integer('total_cents');
            $table->string('currency')->default('usd');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('paid_at')->nullable();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('product_id')->nullable();
            $table->text('product_name');
            $table->integer('quantity');
            $table->integer('unit_price_cents');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! app()->environment('testing')) {
            return;
        }

        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('releases');
        Schema::dropIfExists('license_issuances');
        Schema::dropIfExists('punches');
        Schema::dropIfExists('cards');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('roles');
    }
};
