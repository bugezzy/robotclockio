<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * The store catalog, administered by system. `OrderItem` snapshots a
 * product's name and price at checkout time, so editing or deleting a
 * product here never rewrites what a past order actually charged.
 *
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string|null $image_url
 * @property int $price_cents
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'image_url', 'price_cents', 'active'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUuids;

    protected $connection = 'supabase';

    /**
     * Tests use the local sqlite connection instead, so they stay fast,
     * isolated, and never touch production data.
     */
    public function getConnectionName(): string
    {
        return app()->environment('testing') ? 'sqlite' : 'supabase';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
