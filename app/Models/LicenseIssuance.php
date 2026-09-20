<?php

namespace App\Models;

use Database\Factories\LicenseIssuanceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An audit record of an RCLK1 kiosk licence being issued — the licence
 * string itself is never persisted (see App\License), only who generated
 * one, for which organization, against which kiosk key, and when.
 *
 * @property string $id
 * @property string $organization_id
 * @property string|null $issued_by
 * @property string $kiosk_key
 * @property Carbon $issued_at
 */
#[Fillable(['organization_id', 'issued_by', 'kiosk_key', 'issued_at'])]
class LicenseIssuance extends Model
{
    /** @use HasFactory<LicenseIssuanceFactory> */
    use HasFactory, HasUuids;

    protected $connection = 'supabase';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
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
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
