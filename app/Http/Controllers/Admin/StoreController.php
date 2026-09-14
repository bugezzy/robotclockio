<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\StripeCheckoutSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class StoreController extends Controller
{
    /**
     * Show the store: the fixed catalog, plus this organization's past
     * orders. Owner only — the only role with an organization to ship to
     * and a reason to spend its money.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isOwner(), 403);

        return Inertia::render('admin/Store', [
            'checkoutEnabled' => filled(config('services.stripe.secret')),
            'products' => Product::where('active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'imageUrl' => $product->image_url,
                    'priceCents' => $product->price_cents,
                ]),
            'orders' => Order::with('items')
                ->where('organization_id', $request->user()->organization_id)
                ->latest('created_at')
                ->limit(20)
                ->get()
                ->map(fn (Order $order) => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'total_cents' => $order->total_cents,
                    'currency' => $order->currency,
                    'created_at' => $order->created_at->toIso8601String(),
                    'items' => $order->items->map(fn (OrderItem $item) => [
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                    ]),
                ]),
        ]);
    }

    /**
     * Start a Stripe Checkout session for the requested quantities. The
     * order is recorded as "pending" before redirecting — the webhook is
     * what confirms it was actually paid for.
     */
    public function checkout(Request $request, StripeCheckoutSession $stripe): HttpResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        if (blank(config('services.stripe.secret'))) {
            return back()->withErrors(['quantities' => 'Checkout is not available right now.']);
        }

        $products = Product::where('active', true)->get();

        $data = $request->validate(
            $products->mapWithKeys(fn (Product $product) => [$product->id => ['nullable', 'integer', 'min:0', 'max:1000']])->all(),
        );

        $lineItems = $products
            ->map(fn (Product $product) => ['product' => $product, 'quantity' => (int) ($data[$product->id] ?? 0)])
            ->filter(fn (array $item) => $item['quantity'] > 0)
            ->values();

        if ($lineItems->isEmpty()) {
            return back()->withErrors(['quantities' => 'Choose at least one item to order.']);
        }

        $totalCents = $lineItems->sum(fn (array $item) => $item['product']->price_cents * $item['quantity']);

        $session = $stripe->create([
            'mode' => 'payment',
            'line_items' => $lineItems->map(fn (array $item) => [
                'quantity' => $item['quantity'],
                'price_data' => [
                    'currency' => 'usd',
                    'unit_amount' => $item['product']->price_cents,
                    'product_data' => ['name' => $item['product']->name],
                ],
            ])->all(),
            'success_url' => route('admin.store.success').'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('admin.store.index'),
        ]);

        DB::connection((new Order)->getConnectionName())->transaction(function () use ($request, $session, $lineItems, $totalCents) {
            $order = Order::create([
                'organization_id' => $request->user()->organization_id,
                'created_by' => $request->user()->id,
                'stripe_checkout_session_id' => $session->id,
                'status' => 'pending',
                'total_cents' => $totalCents,
                'currency' => 'usd',
            ]);

            foreach ($lineItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'quantity' => $item['quantity'],
                    'unit_price_cents' => $item['product']->price_cents,
                ]);
            }
        });

        return Inertia::location($session->url);
    }

    /**
     * Where Stripe sends the browser back after a successful checkout.
     * Payment isn't confirmed here — only the webhook does that — this
     * just reflects whatever the order's current status already is.
     */
    public function success(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isOwner(), 403);

        $order = Order::where('stripe_checkout_session_id', $request->query('session_id'))
            ->where('organization_id', $request->user()->organization_id)
            ->first();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $order
                ? "Thanks! Your order is {$order->status}."
                : 'Thanks! Your order is being processed.',
        ]);

        return redirect()->route('admin.store.index');
    }
}
