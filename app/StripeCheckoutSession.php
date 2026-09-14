<?php

namespace App;

use Stripe\Checkout\Session;
use Stripe\StripeClient;

/**
 * A thin wrapper around the one Stripe call App\Http\Controllers\Admin\
 * StoreController needs. Exists so tests can swap in a fake instead of
 * fighting Stripe\StripeClient's magic-property service tree
 * (`$client->checkout->sessions->create(...)`).
 */
class StripeCheckoutSession
{
    public function __construct(private readonly StripeClient $stripe) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): Session
    {
        return $this->stripe->checkout->sessions->create($params);
    }
}
