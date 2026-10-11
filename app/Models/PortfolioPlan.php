<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'promo_price',
        'is_promo_active',
        'promo_ends_at',
        'duration_days',
        'max_pdf_files',
        'max_pdf_size_mb',
        'features',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'promo_price' => 'integer',
            'is_promo_active' => 'boolean',
            'promo_ends_at' => 'datetime',
            'duration_days' => 'integer',
            'max_pdf_files' => 'integer',
            'max_pdf_size_mb' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PortfolioOrder::class, 'plan_id');
    }

    /**
     * Compute current effective price considering active promo.
     */
    public function getEffectivePrice(): int
    {
        if ($this->is_promo_active && $this->promo_price !== null) {
            if ($this->promo_ends_at === null || $this->promo_ends_at->isFuture()) {
                return (int) $this->promo_price;
            }
        }

        return (int) $this->price;
    }
}
