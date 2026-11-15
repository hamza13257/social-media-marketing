<?php

namespace App\Services\Publishing;

use App\Enums\SocialPlatform;
use App\Models\Content;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\Http;

class FacebookPublishingAdapter implements PublishingAdapter
{
    public function supports(SocialAccount $account, Content $content): bool
    {
        return $account->platform === SocialPlatform::FacebookPage;
    }

    public function publish(SocialAccount $account, Content $content): array
    {
        $message = $this->composeMessage($content);
        $pageId = $account->external_id;
        $version = config('meta.graph_version');

        $response = Http::asForm()->post("https://graph.facebook.com/{$version}/{$pageId}/feed", [
            'message' => $message,
            'access_token' => $account->access_token,
        ])->throw()->json();

        $postId = (string) ($response['id'] ?? '');

        if ($postId === '') {
            throw new \RuntimeException('Facebook API did not return a post id.');
        }

        return [
            'external_post_id' => $postId,
            'response' => $response,
        ];
    }

    protected function composeMessage(Content $content): string
    {
        $parts = array_filter([
            $content->caption,
            $content->call_to_action,
            $content->hashtags
                ? implode(' ', array_map(fn ($t) => str_starts_with($t, '#') ? $t : '#'.$t, $content->hashtags))
                : null,
        ]);

        return implode("\n\n", $parts);
    }
}