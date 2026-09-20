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
        if (Schema::hasTable('orders')) {
            return;
        }

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(new Expression('gen_random_uuid()'));
            $table->foreignUuid('organization_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('stripe_checkout_session_id')->unique();
            $table->text('stripe_payment_intent_id')->nullable();
            $table->string('status')->default('pending');
            $table->integer('total_cents');
            $table->string('currency')->default('usd');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('paid_at')->nullable();

            $table->index('organization_id', 'orders_organization_id_idx');
        });

        DB::statement(<<<'SQL'
            alter table orders
                add constraint orders_status_check check (status in ('pending', 'paid', 'canceled'))
            SQL);

        DB::statement(<<<'SQL'
            comment on table orders is
                'A store checkout — one Stripe Checkout Session per row. Starts pending; the '
                'checkout.session.completed webhook (StripeWebhookController) is what marks it paid.'
            SQL);

        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(new Expression('gen_random_uuid()'));
            $table->foreignUuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained()->nullOnDelete();
            $table->text('product_name');
            $table->integer('quantity');
            $table->integer('unit_price_cents');
            $table->timestampTz('created_at')->useCurrent();

            $table->index('order_id', 'order_items_order_id_idx');
            $table->index('product_id', 'order_items_product_id_idx');
        });

        DB::statement(<<<'SQL'
            alter table order_items
                add constraint order_items_quantity_check check (quantity > 0)
            SQL);

        DB::statement(<<<'SQL'
            comment on column order_items.product_name is
                'Snapshot of the product''s name at checkout time — kept even if the '
                'product is later renamed or deleted from the catalog.'
            SQL);

        DB::statement(<<<'SQL'
            comment on column order_items.unit_price_cents is
                'Snapshot of App\Models\Product::price_cents at checkout time — later '
                'catalog price changes must never rewrite what a past order actually charged.'
            SQL);

        // Managed exclusively through the admin panel and the Stripe
        // webhook; the kiosk has no business with either table.
        DB::statement('revoke all on public.orders, public.order_items from anon, authenticated');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
