<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * This is the RobotClock "users" table itself — there is only one users
 * table. Admin panel staff are the same rows as employees clocking in and
 * out; `name`/`password`/`is_admin` are virtual, mapped onto the real
 * `full_name`/`password_hash`/`role` columns so the rest of the app (Fortify,
 * the frontend) doesn't need to know about that.
 *
 * @property string $id
 * @property string $full_name
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password_hash
 * @property string $password
 * @property string|null $team_id
 * @property string $role
 * @property bool $is_admin
 * @property bool $active
 * @property string|null $organization_id
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'role', 'active', 'team_id', 'organization_id'])]
#[Hidden(['password_hash', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Admin panel staff read/write the live RobotClock database directly.
     * Tests use the local sqlite connection instead, so they stay fast,
     * isolated, and never touch production data.
     */
    public function getConnectionName(): string
    {
        return app()->environment('testing') ? 'sqlite' : 'supabase';
    }

    /**
     * @var list<string>
     */
    protected $appends = ['name', 'is_admin'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'active' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Maps onto the real `full_name` column.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->full_name,
            set: fn (string $value) => ['full_name' => $value],
        );
    }

    /**
     * Maps onto the real `password_hash` column, hashing on write.
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->password_hash,
            set: fn (string $value) => [
                'password_hash' => Hash::isHashed($value) ? $value : Hash::make($value),
            ],
        );
    }

    /**
     * There's no is_admin column — admin panel access is granted to every
     * role above plain member (admin, owner, system).
     */
    protected function isAdmin(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->role, ['admin', 'owner', 'system'], true),
        );
    }

    /**
     * System-tier: full access, across every organization.
     */
    public function isSystem(): bool
    {
        return $this->role === 'system';
    }

    /**
     * Owner or system: manages users/teams and edits the organization
     * (system does so across every organization; owner only their own).
     */
    public function isOwnerOrAbove(): bool
    {
        return in_array($this->role, ['owner', 'system'], true);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return HasMany<Card, $this>
     */
    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'user_id');
    }

    /**
     * @return HasMany<Punch, $this>
     */
    public function punches(): HasMany
    {
        return $this->hasMany(Punch::class, 'user_id');
    }
}
