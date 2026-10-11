<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'username',
    'display_name',
    'bio',
    'avatar',
    'theme',
    'is_published',
])]
class Profile extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'display_name' => 'encrypted',
            'bio' => 'encrypted',
        ];
    }

    /**
     * Normalize username to lowercase.
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => $value !== null ? strtolower(trim($value)) : null,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProfileComponent::class)->orderBy('sort_order');
    }

    public function visibleComponents(): HasMany
    {
        return $this->hasMany(ProfileComponent::class)
            ->where('is_visible', true)
            ->orderBy('sort_order');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ProfileEvent::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
