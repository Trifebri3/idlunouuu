<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PortfolioOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'plan_id',
        'voucher_id',
        'original_price',
        'discount_amount',
        'final_amount',
        'payment_status',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'original_price' => 'integer',
            'discount_amount' => 'integer',
            'final_amount' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PortfolioPlan::class, 'plan_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(PortfolioVoucher::class, 'voucher_id');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(PortfolioPayment::class, 'order_id')->latestOfMany();
    }

    public function entitlement(): HasOne
    {
        return $this->hasOne(PortfolioEntitlement::class, 'order_id');
    }
}
