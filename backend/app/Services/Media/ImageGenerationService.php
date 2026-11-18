<?php

namespace App\Services\Media;

use App\Models\Content;

/**
 * Phase 3 hook — returns null until IMAGE_PROVIDER and IMAGE_API_KEY are configured.
 */
class ImageGenerationService
{
    public function isConfigured(): bool
    {
        return config('image.provider') !== 'none'
            && (string) config('image.openai_compatible.api_key') !== '';
    }

    /**
     * @return array{type: string, url: string}|null
     */
    public function generateForContent(Content $content): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        // TODO: call provider and upload to media library when Phase 3 lands.
        return null;
    }
}