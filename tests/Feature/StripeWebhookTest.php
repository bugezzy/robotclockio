<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Stripe\WebhookSignature;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test_secret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.stripe.webhook_secret' => self::WEBHOOK_SECRET]);
    }

    public function test_marks_the_order_paid_on_checkout_session_completed(): void
    {
        $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_test_123']);

        $payload = json_encode([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_123',
                    'object' => 'checkout.session',
                    'payment_intent' => 'pi_test_123',
                ],
            ],
        ]);

        $response = $this->postWebhook($payload);

        $response->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertSame('pi_test_123', $order->stripe_payment_intent_id);
        $this->assertNotNull($order->paid_at);
    }

    public function test_ignores_events_for_an_unknown_session(): void
    {
        $payload = json_encode([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => ['id' => 'cs_test_does_not_exist', 'object' => 'checkout.session'],
            ],
        ]);

        $this->postWebhook($payload)->assertOk();
    }

    public function test_rejects_a_bad_signature(): void
    {
        $order = Order::factory()->create(['stripe_checkout_session_id' => 'cs_test_123']);

        $payload = json_encode([
            'id' => 'evt_test_123',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_123', 'object' => 'checkout.session']],
        ]);

        $response = $this->call('POST', route('stripe.webhook'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 'invalid',
        ], $payload);

        $response->assertStatus(400);
        $this->assertSame('pending', $order->refresh()->status);
    }

    private function postWebhook(string $payload): TestResponse
    {
        $signature = WebhookSignature::generateSignatureHeader($payload, self::WEBHOOK_SECRET);

        return $this->call('POST', route('stripe.webhook'), [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload);
    }
}
