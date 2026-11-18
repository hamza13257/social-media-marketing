<?php

namespace App\Models;

use App\Enums\ContentFormat;
use App\Enums\ContentPlatform;
use App\Enums\ContentStatus;
use Database\Factories\ContentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    /** @use HasFactory<ContentFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'created_by_user_id',
        'topic',
        'objective',
        'platform',
        'format',
        'language',
        'caption',
        'hashtags',
        'call_to_action',
        'ai_generated_at',
        'media_assets',
        'post_structure',
        'scheduled_at',
        'status',
        'version',
        'internal_notes',
        'social_account_id',
        'external_post_id',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'platform' => ContentPlatform::class,
            'format' => ContentFormat::class,
            'status' => ContentStatus::class,
            'hashtags' => 'array',
            'media_assets' => 'array',
            'post_structure' => 'array',
            'scheduled_at' => 'datetime',
            'ai_generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function publishingLogs(): HasMany
    {
        return $this->hasMany(PublishingLog::class);
    }

    public function isEligibleToPublish(): bool
    {
        return in_array($this->status, [ContentStatus::Approved, ContentStatus::Scheduled], true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function approvalLogs(): HasMany
    {
        return $this->hasMany(ContentApprovalLog::class);
    }

    public function transitionTo(ContentStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw new \InvalidArgumentException("Cannot transition from {$this->status->value} to {$status->value}.");
        }

        $this->status = $status;
    }
}