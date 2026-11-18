<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeekPlan extends Model
{
    protected $fillable = [
        'tenant_id',
        'brand_id',
        'created_by_user_id',
        'week_start',
        'brief',
        'rhythm',
        'status',
        'generated_summary',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'rhythm' => 'array',
            'generated_summary' => 'array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}