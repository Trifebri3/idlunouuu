<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_amount',
        'max_discount',
        'quota',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
            'max_discount' => 'integer',
            'quota' => 'integer',
            'used_count' => 'integer',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PortfolioOrder::class, 'voucher_id');
    }

    /**
     * Determine if voucher is currently usable.
     */
    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->quota > 0 && $this->used_count >= $this->quota) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Calculate discount for a given base amount.
     */
    public function calculateDiscount(int $baseAmount): int
    {
        if (! $this->isValid()) {
            return 0;
        }

        if ($this->discount_type === 'percent') {
            $discount = (int) round(($baseAmount * $this->discount_amount) / 100);
            if ($this->max_discount !== null && $discount > $this->max_discount) {
                $discount = (int) $this->max_discount;
            }

            return min($discount, $baseAmount);
        }

        // Fixed discount
        return min((int) $this->discount_amount, $baseAmount);
    }
}
