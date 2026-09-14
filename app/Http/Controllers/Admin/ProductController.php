<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * List the store catalog, active and inactive alike. System only —
     * this is the only role with a reason to administer what owners buy.
     */
    public function index(Request $request): Response
    {
        abort_unless($request->user()->isSystem(), 403);

        return Inertia::render('admin/Products', [
            'products' => Product::orderBy('name')
                ->get()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'image_url' => $product->image_url,
                    'price_cents' => $product->price_cents,
                    'active' => $product->active,
                    'created_at' => $product->created_at->toIso8601String(),
                ]),
        ]);
    }

    /**
     * Add a new catalog item. System only.
     */
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSystem(), 403);

        $data = $this->validated($request);

        Product::create($data);

        return back();
    }

    /**
     * Update a catalog item's name and price. Past orders keep their own
     * snapshot, so this never rewrites what was already charged. System
     * only.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->isSystem(), 403);

        $data = $this->validated($request);

        // An unchecked checkbox submits nothing at all, so "active" is read
        // separately rather than through the validated array.
        $data['active'] = $request->boolean('active');

        $product->update($data);

        return back();
    }

    /**
     * Remove a catalog item. Past order_items keep their own snapshot of
     * its name and price, so this doesn't touch order history. System
     * only.
     */
    public function destroy(Request $request, Product $product): RedirectResponse
    {
        abort_unless($request->user()->isSystem(), 403);

        $product->delete();

        return back();
    }

    /**
     * @return array{name: string, description: string|null, price_cents: int, image_url?: string}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $result = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_cents' => (int) round($data['price'] * 100),
        ];

        // Only ever set when a new file was actually uploaded — an update
        // with no new image must leave the existing one alone.
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $result['image_url'] = Storage::disk('public')->url($path);
        }

        return $result;
    }
}
