<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioEntitlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'status',
        'starts_at',
        'expires_at',
        'max_pdf_files',
        'max_pdf_size_mb',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'max_pdf_files' => 'integer',
            'max_pdf_size_mb' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PortfolioOrder::class, 'order_id');
    }

    /**
     * Check if entitlement grants active access right now.
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get days remaining for active subscription.
     */
    public function daysRemaining(): int
    {
        if ($this->expires_at === null) {
            return 999; // unlimited/lifetime
        }

        if ($this->expires_at->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expires_at, false);
    }
}
