<?php

namespace App\Models;

use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A physical card's issuance history. A lost card is revoked via
 * `revoked_at`, never deleted, so `card_uid` can be reused by a later card.
 *
 * @property string $card_uid
 * @property string $user_id
 * @property string|null $label
 * @property Carbon $issued_at
 * @property Carbon|null $revoked_at
 */
#[Fillable(['card_uid', 'user_id', 'label', 'issued_at', 'revoked_at'])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    protected $connection = 'supabase';

    protected $primaryKey = 'card_uid';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
