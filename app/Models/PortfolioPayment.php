<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'proof_file',
        'sender_bank',
        'sender_account_name',
        'amount_paid',
        'notes',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    /**
     * Encrypt user financial & sensitive payment transfer details at rest in MySQL.
     */
    protected function casts(): array
    {
        return [
            'sender_bank' => 'encrypted',
            'sender_account_name' => 'encrypted',
            'notes' => 'encrypted',
            'amount_paid' => 'integer',
            'verified_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PortfolioOrder::class, 'order_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PortfolioPaymentMethod::class, 'payment_method_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
