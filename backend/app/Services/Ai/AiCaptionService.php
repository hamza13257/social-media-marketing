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
     * @return array{
     *     caption: string,
     *     hashtags: array<int, string>,
     *     call_to_action: string|null,
     *     warnings: array<int, string>,
     *     post_structure: array<string, mixed>
     * }
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
     * @return array{
     *     caption: string,
     *     hashtags: array<int, string>,
     *     call_to_action: string|null,
     *     warnings: array<int, string>,
     *     post_structure: array<string, mixed>
     * }
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
        $formatGuide = $this->formatGuide($platform, $format);

        $userPrompt = <<<PROMPT
You are building a complete {$platform} post blueprint, NOT a generic blog paragraph.

Platform: {$platform}
Format: {$format}
Language: {$language}
Topic: {$topic}
Objective: {$objective}

{$formatGuide}

Return JSON with:
- publish_text (string): the exact text that will be posted on Meta (caption for IG, post body for FB). Include line breaks where the platform expects them. For carousel, summarize the story in the caption while slides hold detail.
- caption (string): same as publish_text (duplicate for compatibility)
- hashtags (array of strings without #)
- call_to_action (string or null)
- warnings (array of compliance notes)
- post_structure (object):
  - platform (string)
  - format (string)
  - hook (string|null): opening line for reels/stories
  - slides (array): for carousel — each item { "title", "body", "visual_idea" } (3-5 slides)
  - story_frames (array): for story — each item { "text", "sticker_idea" } (2-4 frames)
  - reel_script (object|null): { "hook", "scenes": ["..."], "on_screen_text": ["..."] } for reel
  - visual_suggestions (array of strings): what image/video the human should use (MVP does not generate binaries)
  - image_alt (string|null): accessibility text for single_image

Do not output HTML. Images are not generated — only copy and creative direction.
PROMPT;

        $raw = $this->client->chatJson([
            ['role' => 'system', 'content' => $brandContext],
            ['role' => 'user', 'content' => $userPrompt],
        ], []);

        $publishText = (string) ($raw['publish_text'] ?? $raw['caption'] ?? '');
        $postStructure = is_array($raw['post_structure'] ?? null) ? $raw['post_structure'] : [];
        $postStructure['platform'] = $platform;
        $postStructure['format'] = $format;
        if (! isset($postStructure['visual_suggestions']) && isset($raw['visual_suggestions'])) {
            $postStructure['visual_suggestions'] = $raw['visual_suggestions'];
        }

        return [
            'caption' => $publishText,
            'hashtags' => array_values(array_map('strval', $raw['hashtags'] ?? [])),
            'call_to_action' => isset($raw['call_to_action']) ? (string) $raw['call_to_action'] : null,
            'warnings' => array_values(array_map('strval', $raw['warnings'] ?? [])),
            'post_structure' => $postStructure,
        ];
    }

    protected function formatGuide(string $platform, string $format): string
    {
        $platformRules = match ($platform) {
            'instagram' => 'Instagram: short hook first line, 3-8 hashtags at end, emoji sparingly, line breaks for readability.',
            'facebook' => 'Facebook: conversational tone, 1-2 short paragraphs, optional question to drive comments.',
            default => 'Match native social tone.',
        };

        $formatRules = match ($format) {
            'carousel' => 'Carousel: provide 3-5 slides with distinct headlines; caption teases the swipe.',
            'reel' => 'Reel: punchy hook in 3 seconds, scene beats, on-screen text suggestions, caption supplements not repeats.',
            'story' => 'Story: 2-4 quick frames, one idea per frame, CTA on last frame.',
            'single_image' => 'Single image: one strong visual idea + caption that complements the image.',
            'article' => 'Article-style link post: headline + short summary + CTA to read more.',
            default => 'Standard feed post.',
        };

        return $platformRules."\n".$formatRules;
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