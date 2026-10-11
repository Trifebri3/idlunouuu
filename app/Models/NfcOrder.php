<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'nfc_device_id',
    'order_number',
    'card_variant',
    'card_printed_name',
    'custom_image',
    'phone_or_whatsapp',
    'shipping_address',
    'notes',
    'payment_method',
    'payment_status',
    'status',
    'amount',
])]
class NfcOrder extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'card_printed_name' => 'encrypted',
            'phone_or_whatsapp' => 'encrypted',
            'shipping_address' => 'encrypted',
            'notes' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(NfcDevice::class, 'nfc_device_id');
    }
}
