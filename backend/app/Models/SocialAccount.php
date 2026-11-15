<?php

namespace App\Models;

use App\Enums\SocialConnectionStatus;
use App\Enums\SocialPlatform;
use Database\Factories\SocialAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    /** @use HasFactory<SocialAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'brand_id',
        'platform',
        'external_id',
        'name',
        'username',
        'profile_picture_url',
        'access_token',
        'token_expires_at',
        'scopes',
        'connection_status',
        'metadata',
        'last_error',
        'connected_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected function casts(): array
    {
        return [
            'platform' => SocialPlatform::class,
            'connection_status' => SocialConnectionStatus::class,
            'access_token' => 'encrypted',
            'scopes' => 'array',
            'metadata' => 'array',
            'token_expires_at' => 'datetime',
            'connected_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }
}