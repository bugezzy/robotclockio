<?php

namespace App\Models;

use Database\Factories\ReleaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single build of the kiosk desktop app for one platform. History is
 * append-only — a new upload for a platform creates a new row and moves
 * `is_latest` onto it, rather than overwriting the previous release.
 *
 * @property string $id
 * @property string $platform
 * @property string $version
 * @property string $url
 * @property bool $is_latest
 * @property string|null $created_by
 * @property Carbon $created_at
 */
#[Fillable(['platform', 'version', 'url', 'is_latest', 'created_by'])]
class Release extends Model
{
    /** @use HasFactory<ReleaseFactory> */
    use HasFactory, HasUuids;

    protected $connection = 'supabase';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_latest' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
