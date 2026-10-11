<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Portfolio extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'bio',
        'theme',
        'is_published',
        'published_at',
        'settings',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'settings' => 'array',
            'views_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(PortfolioPage::class, 'portfolio_id')->orderBy('sort_order');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(PortfolioAsset::class, 'portfolio_id');
    }

    public function getPublicUrl(): string
    {
        return url('/p/'.$this->slug);
    }
}
