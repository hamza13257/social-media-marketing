<?php

namespace App\Services\Publishing;

use App\Models\Content;
use App\Models\SocialAccount;

interface PublishingAdapter
{
    public function supports(SocialAccount $account, Content $content): bool;

    /**
     * @return array{external_post_id: string, response: array<string, mixed>}
     */
    public function publish(SocialAccount $account, Content $content): array;
}