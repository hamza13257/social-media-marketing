<?php

namespace App\Services\Publishing;

use App\Enums\ContentPlatform;
use App\Enums\ContentStatus;
use App\Enums\PublishingLogStatus;
use App\Enums\SocialConnectionStatus;
use App\Enums\SocialPlatform;
use App\Models\Content;
use App\Models\PublishingLog;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\DB;

class PublishingOrchestrator
{
    /** @param  iterable<PublishingAdapter>  $adapters */
    public function __construct(private iterable $adapters) {}

    public function publishContent(int $contentId, ?int $socialAccountId = null, ?string $idempotencyKey = null): PublishingLog
    {
        return DB::transaction(function () use ($contentId, $socialAccountId, $idempotencyKey) {
            /** @var Content $content */
            $content = Content::query()->lockForUpdate()->findOrFail($contentId);

            if (in_array($content->status, [ContentStatus::Published, ContentStatus::Publishing], true)) {
                throw new \RuntimeException('Content is already published or publishing.');
            }

            if (! $content->isEligibleToPublish()) {
                throw new \RuntimeException('Content must be approved or scheduled before publishing.');
            }

            $key = $idempotencyKey ?? $this->defaultIdempotencyKey($content);

            if (PublishingLog::query()->where('idempotency_key', $key)->where('status', PublishingLogStatus::Success)->exists()) {
                throw new \RuntimeException('This publish attempt was already completed.');
            }

            $existing = PublishingLog::query()->where('idempotency_key', $key)->first();
            if ($existing !== null && $existing->status === PublishingLogStatus::Pending) {
                return $existing;
            }

            $account = $this->resolveSocialAccount($content, $socialAccountId);

            $log = PublishingLog::query()->create([
                'tenant_id' => $content->tenant_id,
                'content_id' => $content->id,
                'social_account_id' => $account->id,
                'platform' => $content->platform->value,
                'status' => PublishingLogStatus::Pending,
                'idempotency_key' => $key,
                'started_at' => now(),
            ]);

            $content->social_account_id = $account->id;
            $content->transitionTo(ContentStatus::Publishing);
            $content->save();

            try {
                $adapter = $this->adapterFor($account, $content);
                $message = $this->buildRequestPreview($content);
                $result = $adapter->publish($account, $content);

                $log->fill([
                    'status' => PublishingLogStatus::Success,
                    'external_post_id' => $result['external_post_id'],
                    'request_payload' => $message,
                    'response_payload' => $result['response'],
                    'finished_at' => now(),
                ])->save();

                $content->external_post_id = $result['external_post_id'];
                $content->published_at = now();
                $content->transitionTo(ContentStatus::Published);
                $content->save();

                return $log->fresh();
            } catch (\Throwable $exception) {
                $log->fill([
                    'status' => PublishingLogStatus::Failed,
                    'error_message' => $exception->getMessage(),
                    'finished_at' => now(),
                ])->save();

                $content->transitionTo(ContentStatus::Failed);
                $content->save();

                throw $exception;
            }
        });
    }

    protected function resolveSocialAccount(Content $content, ?int $socialAccountId): SocialAccount
    {
        if ($socialAccountId !== null) {
            $account = SocialAccount::query()
                ->where('tenant_id', $content->tenant_id)
                ->where('id', $socialAccountId)
                ->firstOrFail();
        } elseif ($content->social_account_id !== null) {
            $account = SocialAccount::query()->findOrFail($content->social_account_id);
        } else {
            $platform = match ($content->platform) {
                ContentPlatform::Instagram => SocialPlatform::InstagramProfessional,
                ContentPlatform::Facebook => SocialPlatform::FacebookPage,
            };

            $account = SocialAccount::query()
                ->where('tenant_id', $content->tenant_id)
                ->where('brand_id', $content->brand_id)
                ->where('platform', $platform)
                ->where('connection_status', SocialConnectionStatus::Connected)
                ->orderByDesc('connected_at')
                ->first();
        }

        if ($account === null) {
            throw new \RuntimeException('No connected social account found for this brand and platform.');
        }

        if ($account->connection_status !== SocialConnectionStatus::Connected) {
            throw new \RuntimeException('Social account is not connected.');
        }

        return $account;
    }

    protected function adapterFor(SocialAccount $account, Content $content): PublishingAdapter
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($account, $content)) {
                return $adapter;
            }
        }

        throw new \RuntimeException('No publishing adapter for this account/platform.');
    }

    protected function defaultIdempotencyKey(Content $content): string
    {
        return 'content:'.$content->id.':v'.$content->version;
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildRequestPreview(Content $content): array
    {
        return [
            'content_id' => $content->id,
            'platform' => $content->platform->value,
            'format' => $content->format->value,
            'caption' => $content->caption,
        ];
    }
}