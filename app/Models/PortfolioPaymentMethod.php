<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PortfolioPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_number',
        'account_holder',
        'instructions',
        'qr_image_url',
        'is_active',
    ];

    /**
     * Encrypt sensitive bank account details in database using AES-256-CBC.
     */
    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'account_holder' => 'encrypted',
            'is_active' => 'boolean',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PortfolioPayment::class, 'payment_method_id');
    }
}
