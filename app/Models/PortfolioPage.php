<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'portfolio_id',
        'title',
        'page_type',
        'sort_order',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'content' => 'array',
        ];
    }

    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class, 'portfolio_id');
    }
}
