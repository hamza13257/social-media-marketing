<?php

namespace App\Models;

use App\Enums\PublishingLogStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishingLog extends Model
{
    protected $fillable = [
        'tenant_id',
        'content_id',
        'social_account_id',
        'platform',
        'status',
        'idempotency_key',
        'external_post_id',
        'request_payload',
        'response_payload',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PublishingLogStatus::class,
            'request_payload' => 'array',
            'response_payload' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }
}