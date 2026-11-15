<?php

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'logo_path',
        'website_url',
        'brand_colors',
        'target_audience',
        'language',
        'tone_of_voice',
        'marketing_objectives',
        'products_services',
        'content_pillars',
        'brand_guidelines',
        'knowledge_documents',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'brand_colors' => 'array',
            'marketing_objectives' => 'array',
            'products_services' => 'array',
            'content_pillars' => 'array',
            'knowledge_documents' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}