<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'email_bidx', 'password', 'google_id', 'avatar', 'role', 'status'])]
#[Hidden(['password', 'remember_token', 'email_bidx'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Use custom UserBuilder for automatic email_bidx blind index resolution.
     */
    public function newEloquentBuilder($query): UserBuilder
    {
        return new UserBuilder($query);
    }

    /**
     * Automatically synchronize email_bidx blind index when email changes.
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            if ($user->isDirty('email') && ! empty($user->email)) {
                $user->email_bidx = static::hashEmail($user->email);
            }
        });
    }

    /**
     * Compute search hash for email lookups.
     */
    public static function hashEmail(?string $email): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }

        return hash_hmac('sha256', strtolower(trim($email)), (string) config('app.key'));
    }

    /**
     * Find user by plaintext email.
     */
    public static function findByEmail(?string $email): ?self
    {
        if (! $email) {
            return null;
        }

        return static::where('email_bidx', static::hashEmail($email))->first();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'name' => 'encrypted',
            'email' => 'encrypted',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function nfcDevices(): HasMany
    {
        return $this->hasMany(NfcDevice::class);
    }

    public function nfcOrders(): HasMany
    {
        return $this->hasMany(NfcOrder::class)->latest();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function portfolio(): HasOne
    {
        return $this->hasOne(Portfolio::class);
    }

    public function portfolioEntitlement(): HasOne
    {
        return $this->hasOne(PortfolioEntitlement::class);
    }

    public function portfolioOrders(): HasMany
    {
        return $this->hasMany(PortfolioOrder::class)->latest();
    }

    public function hasActivePortfolioAccess(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return (bool) ($this->portfolioEntitlement && $this->portfolioEntitlement->isActive());
    }
}
