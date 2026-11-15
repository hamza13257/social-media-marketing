<?php

namespace App\Services\Ai;

use App\Models\Brand;
use App\Models\Content;

class AiCaptionService
{
    public function __construct(private AiClient $client) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @return array{caption: string, hashtags: array<int, string>, call_to_action: string|null, warnings: array<int, string>}
     */
    public function generateForContent(Content $content): array
    {
        $content->loadMissing('brand');

        return $this->generate(
            $content->brand,
            $content->topic,
            $content->objective,
            $content->platform->value,
            $content->format->value,
            $content->language,
        );
    }

    /**
     * @return array{caption: string, hashtags: array<int, string>, call_to_action: string|null, warnings: array<int, string>}
     */
    public function generate(
        Brand $brand,
        string $topic,
        ?string $objective,
        string $platform,
        string $format,
        string $language = 'en',
    ): array {
        if (! $this->client->isConfigured()) {
            throw new AiNotConfiguredException('AI is not configured. Set AI_API_KEY in the server environment.');
        }

        $brandContext = $this->brandContext($brand);

        $userPrompt = <<<PROMPT
Create social content for platform "{$platform}" as format "{$format}" in language "{$language}".

Topic: {$topic}
Objective: {$objective}

Return JSON with keys: caption (string), hashtags (array of strings without #), call_to_action (string or null), warnings (array of strings for compliance notes).
PROMPT;

        $raw = $this->client->chatJson([
            ['role' => 'system', 'content' => $brandContext],
            ['role' => 'user', 'content' => $userPrompt],
        ], [
            'caption' => 'string',
            'hashtags' => ['string'],
            'call_to_action' => 'string|null',
            'warnings' => ['string'],
        ]);

        return [
            'caption' => (string) ($raw['caption'] ?? ''),
            'hashtags' => array_values(array_map('strval', $raw['hashtags'] ?? [])),
            'call_to_action' => isset($raw['call_to_action']) ? (string) $raw['call_to_action'] : null,
            'warnings' => array_values(array_map('strval', $raw['warnings'] ?? [])),
        ];
    }

    protected function brandContext(Brand $brand): string
    {
        $pillars = $brand->content_pillars ? implode(', ', $brand->content_pillars) : 'n/a';
        $tone = $brand->tone_of_voice ?? 'professional';
        $audience = $brand->target_audience ?? 'general audience';

        return "Brand: {$brand->name}. Tone: {$tone}. Audience: {$audience}. Content pillars: {$pillars}. "
            .'Do not fabricate metrics, testimonials, or regulated claims.';
    }
}