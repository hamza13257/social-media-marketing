<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_cents',
        'currency',
        'limits',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function limit(string $key, ?int $default = null): ?int
    {
        $limits = $this->limits ?? [];

        if (! array_key_exists($key, $limits)) {
            return $default;
        }

        $value = $limits[$key];

        return $value === null ? null : (int) $value;
    }
}