<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioAsset extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'user_id',
        'type',
        'title',
        'file_path',
        'thumbnail_path',
        'file_size_bytes',
        'mime_type',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class, 'portfolio_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
