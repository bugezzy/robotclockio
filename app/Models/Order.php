<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A store checkout — one Stripe Checkout Session per row. The webhook is
 * the only thing that ever moves `status` from 'pending' to 'paid'; the
 * browser coming back to the success page is not treated as proof of
 * payment.
 *
 * @property string $id
 * @property string $organization_id
 * @property string|null $created_by
 * @property string $stripe_checkout_session_id
 * @property string|null $stripe_payment_intent_id
 * @property string $status
 * @property int $total_cents
 * @property string $currency
 * @property Carbon $created_at
 * @property Carbon|null $paid_at
 */
#[Fillable(['organization_id', 'created_by', 'stripe_checkout_session_id', 'stripe_payment_intent_id', 'status', 'total_cents', 'currency', 'paid_at'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUuids;

    protected $connection = 'supabase';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_cents' => 'integer',
            'created_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
