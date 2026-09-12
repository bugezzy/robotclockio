<?php

namespace App\Models;

use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A RobotClock customer account. Everything else in this schema belongs to
 * exactly one organization.
 *
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property string $kiosk_key
 * @property bool $active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'description', 'active'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory, HasUuids;

    protected $connection = 'supabase';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Team, $this>
     */
    public function teams(): HasMany
    {
        return $this->hasMany(Team::class);
    }

    /**
     * @return HasMany<User, $this>
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Punch, $this>
     */
    public function punches(): HasMany
    {
        return $this->hasMany(Punch::class);
    }
}
