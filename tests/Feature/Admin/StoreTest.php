<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use App\StripeCheckoutSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Stripe\Checkout\Session;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_the_store(): void
    {
        Config::set('services.stripe.secret', 'sk_test_123');
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);

        $this->actingAs($owner)
            ->get(route('admin.store.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Store')
                ->where('checkoutEnabled', true)
                ->has('products', 4),
            );
    }

    public function test_checkout_is_reported_disabled_without_a_stripe_key(): void
    {
        Config::set('services.stripe.secret', null);
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->get(route('admin.store.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('checkoutEnabled', false),
            );
    }

    public function test_non_owners_cannot_view_the_store(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();
        $system = User::factory()->system()->create();

        $this->actingAs($admin)->get(route('admin.store.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.store.index'))->assertForbidden();
        $this->actingAs($system)->get(route('admin.store.index'))->assertForbidden();
    }

    public function test_owner_can_check_out(): void
    {
        Config::set('services.stripe.secret', 'sk_test_123');
        $organization = Organization::factory()->create();
        $owner = User::factory()->owner()->create(['organization_id' => $organization->id]);
        $card = Product::factory()->create(['price_cents' => 100]);
        $reader = Product::factory()->create(['price_cents' => 3000]);

        $fakeSession = Session::constructFrom([
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.com/pay/cs_test_123',
        ]);

        $stripe = Mockery::mock(StripeCheckoutSession::class);
        $stripe->shouldReceive('create')
            ->once()
            ->withArgs(fn (array $params) => count($params['line_items']) === 2)
            ->andReturn($fakeSession);
        $this->app->instance(StripeCheckoutSession::class, $stripe);

        $response = $this->actingAs($owner)
            ->withHeaders(['X-Inertia' => 'true'])
            ->post(route('admin.store.checkout'), [
                $card->id => 3,
                $reader->id => 1,
            ]);

        $response->assertStatus(409);
        $this->assertSame('https://checkout.stripe.com/pay/cs_test_123', $response->headers->get('X-Inertia-Location'));

        $order = Order::where('stripe_checkout_session_id', 'cs_test_123')->first();

        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);
        $this->assertSame($organization->id, $order->organization_id);
        $this->assertSame(100 * 3 + 3000 * 1, $order->total_cents);
        $this->assertCount(2, $order->items);
    }

    public function test_checkout_requires_at_least_one_item(): void
    {
        Config::set('services.stripe.secret', 'sk_test_123');
        $owner = User::factory()->owner()->create();
        $card = Product::factory()->create();

        $stripe = Mockery::mock(StripeCheckoutSession::class);
        $stripe->shouldNotReceive('create');
        $this->app->instance(StripeCheckoutSession::class, $stripe);

        $response = $this->actingAs($owner)->post(route('admin.store.checkout'), [
            $card->id => 0,
        ]);

        $response->assertSessionHasErrors('quantities');
        $this->assertSame(0, Order::count());
    }

    public function test_non_owners_cannot_check_out(): void
    {
        $admin = User::factory()->admin()->create();

        $stripe = Mockery::mock(StripeCheckoutSession::class);
        $stripe->shouldNotReceive('create');
        $this->app->instance(StripeCheckoutSession::class, $stripe);

        $this->actingAs($admin)
            ->post(route('admin.store.checkout'), ['standard_card' => 1])
            ->assertForbidden();
    }

    public function test_checkout_is_blocked_without_a_stripe_key(): void
    {
        Config::set('services.stripe.secret', null);
        $owner = User::factory()->owner()->create();
        $card = Product::factory()->create();

        $stripe = Mockery::mock(StripeCheckoutSession::class);
        $stripe->shouldNotReceive('create');
        $this->app->instance(StripeCheckoutSession::class, $stripe);

        $response = $this->actingAs($owner)->post(route('admin.store.checkout'), [
            $card->id => 1,
        ]);

        $response->assertSessionHasErrors('quantities');
        $this->assertSame(0, Order::count());
    }
}
