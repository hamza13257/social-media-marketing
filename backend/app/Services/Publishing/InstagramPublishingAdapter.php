<?php

namespace App\Services\Publishing;

use App\Enums\SocialPlatform;
use App\Models\Content;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;

class InstagramPublishingAdapter implements PublishingAdapter
{
    public function supports(SocialAccount $account, Content $content): bool
    {
        return $account->platform === SocialPlatform::InstagramProfessional;
    }

    public function publish(SocialAccount $account, Content $content): array
    {
        $imageUrl = $this->resolveImageUrl($content);

        if ($imageUrl === null) {
            throw new \RuntimeException('Instagram publishing requires a public image URL in media_assets.');
        }

        $igUserId = $account->external_id;
        $version = config('meta.graph_version');
        $caption = $this->composeCaption($content);

        $container = Http::asForm()->post("https://graph.facebook.com/{$version}/{$igUserId}/media", [
            'image_url' => $imageUrl,
            'caption' => $caption,
            'access_token' => $account->access_token,
        ])->throw()->json();

        $creationId = (string) ($container['id'] ?? '');
        if ($creationId === '') {
            throw new \RuntimeException('Instagram media container creation failed.');
        }

        $publish = Http::asForm()->post("https://graph.facebook.com/{$version}/{$igUserId}/media_publish", [
            'creation_id' => $creationId,
            'access_token' => $account->access_token,
        ])->throw()->json();

        $postId = (string) ($publish['id'] ?? $creationId);

        return [
            'external_post_id' => $postId,
            'response' => ['container' => $container, 'publish' => $publish],
        ];
    }

    protected function resolveImageUrl(Content $content): ?string
    {
        $assets = $content->media_assets ?? [];
        foreach ($assets as $asset) {
            if (is_array($asset) && ($asset['type'] ?? '') === 'image' && ! empty($asset['url'])) {
                return (string) $asset['url'];
            }
            if (is_string($asset) && filter_var($asset, FILTER_VALIDATE_URL)) {
                return $asset;
            }
        }

        return null;
    }

    protected function composeCaption(Content $content): string
    {
        $parts = array_filter([
            $content->caption,
            $content->hashtags
                ? implode(' ', array_map(fn ($t) => str_starts_with($t, '#') ? $t : '#'.$t, $content->hashtags))
                : null,
        ]);

        return implode("\n\n", $parts);
    }
}