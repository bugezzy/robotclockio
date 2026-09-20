<?php

namespace Tests\Feature\Admin;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_system_can_view_the_catalog(): void
    {
        $system = User::factory()->system()->create();
        Product::factory()->count(2)->create();

        $this->actingAs($system)
            ->get(route('admin.products.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/Products')
                ->has('products', 6), // 4 seeded by migration + 2 created here
            );
    }

    public function test_non_system_cannot_view_the_catalog(): void
    {
        $owner = User::factory()->owner()->create();
        $admin = User::factory()->admin()->create();
        $member = User::factory()->create();

        $this->actingAs($owner)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($member)->get(route('admin.products.index'))->assertForbidden();
    }

    public function test_system_can_create_a_product(): void
    {
        $system = User::factory()->system()->create();

        $this->actingAs($system)->post(route('admin.products.store'), [
            'name' => 'Deluxe RFID Card',
            'price' => '4.50',
        ])->assertSessionHasNoErrors();

        $product = Product::where('name', 'Deluxe RFID Card')->first();

        $this->assertNotNull($product);
        $this->assertSame(450, $product->price_cents);
        $this->assertTrue($product->active);
    }

    public function test_non_system_cannot_create_a_product(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->post(route('admin.products.store'), [
            'name' => 'Deluxe RFID Card',
            'price' => '4.50',
        ])->assertForbidden();

        $this->assertSame(0, Product::where('name', 'Deluxe RFID Card')->count());
    }

    public function test_creating_a_product_requires_a_positive_price(): void
    {
        $system = User::factory()->system()->create();

        $this->actingAs($system)->post(route('admin.products.store'), [
            'name' => 'Free Sample',
            'price' => '0',
        ])->assertSessionHasErrors('price');
    }

    public function test_system_can_update_a_product(): void
    {
        $system = User::factory()->system()->create();
        $product = Product::factory()->create(['name' => 'Old Name', 'price_cents' => 100, 'active' => true]);

        $this->actingAs($system)->put(route('admin.products.update', $product), [
            'name' => 'New Name',
            'price' => '9.99',
        ])->assertSessionHasNoErrors();

        $product->refresh();

        $this->assertSame('New Name', $product->name);
        $this->assertSame(999, $product->price_cents);
        $this->assertFalse($product->active);
    }

    public function test_system_can_upload_a_picture_when_creating_a_product(): void
    {
        Storage::fake('public');
        $system = User::factory()->system()->create();

        $this->actingAs($system)->post(route('admin.products.store'), [
            'name' => 'Deluxe RFID Card',
            'price' => '4.50',
            'image' => UploadedFile::fake()->image('card.jpg'),
        ])->assertSessionHasNoErrors();

        $product = Product::where('name', 'Deluxe RFID Card')->first();

        $this->assertNotNull($product->image_url);
        Storage::disk('public')->assertExists('products/'.basename($product->image_url));
    }

    public function test_creating_a_product_rejects_a_non_image_file(): void
    {
        Storage::fake('public');
        $system = User::factory()->system()->create();

        $this->actingAs($system)->post(route('admin.products.store'), [
            'name' => 'Deluxe RFID Card',
            'price' => '4.50',
            'image' => UploadedFile::fake()->create('receipt.pdf', 100),
        ])->assertSessionHasErrors('image');
    }

    public function test_updating_a_product_without_a_new_picture_keeps_the_existing_one(): void
    {
        Storage::fake('public');
        $system = User::factory()->system()->create();
        $product = Product::factory()->create(['image_url' => 'https://example.com/old.jpg']);

        $this->actingAs($system)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'price' => '9.99',
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://example.com/old.jpg', $product->fresh()->image_url);
    }

    public function test_updating_a_product_replaces_its_picture(): void
    {
        Storage::fake('public');
        $system = User::factory()->system()->create();
        $product = Product::factory()->create(['image_url' => 'https://example.com/old.jpg']);

        $this->actingAs($system)->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'price' => '9.99',
            'image' => UploadedFile::fake()->image('new.jpg'),
        ])->assertSessionHasNoErrors();

        $newImageUrl = $product->fresh()->image_url;

        $this->assertNotSame('https://example.com/old.jpg', $newImageUrl);
        Storage::disk('public')->assertExists('products/'.basename($newImageUrl));
    }

    public function test_non_system_cannot_update_a_product(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create(['name' => 'Old Name']);

        $this->actingAs($owner)->put(route('admin.products.update', $product), [
            'name' => 'New Name',
            'price' => '9.99',
        ])->assertForbidden();

        $this->assertSame('Old Name', $product->fresh()->name);
    }

    public function test_deleting_a_product_does_not_touch_past_order_items(): void
    {
        $system = User::factory()->system()->create();
        $product = Product::factory()->create();
        $orderItem = OrderItem::factory()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
        ]);

        $this->actingAs($system)
            ->delete(route('admin.products.destroy', $product))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, Product::whereKey($product->id)->count());

        $orderItem->refresh();
        $this->assertSame($product->name, $orderItem->product_name);
    }

    public function test_non_system_cannot_delete_a_product(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create();

        $this->actingAs($owner)
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->assertSame(1, Product::whereKey($product->id)->count());
    }
}
