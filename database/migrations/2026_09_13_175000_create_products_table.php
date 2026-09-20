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
        if (Schema::hasTable('products')) {
            return;
        }

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(new Expression('gen_random_uuid()'));
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('price_cents');
            $table->boolean('active')->default(true);
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
        });

        DB::statement(<<<'SQL'
            alter table products
                add constraint products_name_check check (length(trim(name)) > 0)
            SQL);

        DB::statement(<<<'SQL'
            alter table products
                add constraint products_price_cents_check check (price_cents > 0)
            SQL);

        DB::statement(<<<'SQL'
            comment on table products is
                'The store catalog, administered by system. order_items snapshots each '
                'item''s name and price at checkout time, so editing or deleting a product '
                'here never rewrites what a past order actually charged.'
            SQL);

        $uuid = fn () => DB::raw('gen_random_uuid()');

        DB::table('products')->insert([
            ['id' => $uuid(), 'name' => 'Standard RFID Card', 'description' => 'A basic RFID card for clocking in and out.', 'price_cents' => 100, 'active' => true],
            ['id' => $uuid(), 'name' => 'Premium RFID Card', 'description' => 'A durable, printable RFID card for clocking in and out.', 'price_cents' => 200, 'active' => true],
            ['id' => $uuid(), 'name' => 'Standard RFID Reader', 'description' => 'A kiosk-ready RFID reader for recording punches.', 'price_cents' => 2500, 'active' => true],
            ['id' => $uuid(), 'name' => 'Premium RFID Reader', 'description' => 'A faster, more durable RFID reader for high-traffic kiosks.', 'price_cents' => 3000, 'active' => true],
        ]);

        // Managed exclusively through the admin panel; the kiosk has no
        // business with the catalog.
        DB::statement('revoke all on public.products from anon, authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
