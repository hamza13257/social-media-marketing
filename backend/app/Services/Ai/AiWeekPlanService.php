<?php

namespace App\Services\Ai;

use App\Models\Brand;
use Carbon\Carbon;

class AiWeekPlanService
{
    public function __construct(private AiClient $client) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    /**
     * @param  array<int, array{day_offset: int, time: string, format: string, platform: string}>  $slots
     * @return array<int, array{day_offset: int, time: string, format: string, platform: string, topic: string, objective: string}>
     */
    public function enrichSlots(Brand $brand, string $brief, Carbon $weekStart, array $slots): array
    {
        if (! $this->client->isConfigured()) {
            throw new AiNotConfiguredException('AI is not configured.');
        }

        $weekLabel = $weekStart->toDateString();
        $slotJson = json_encode($slots, JSON_THROW_ON_ERROR);

        $system = "Brand: {$brand->name}. Tone: ".($brand->tone_of_voice ?? 'professional')
            .'. Audience: '.($brand->target_audience ?? 'general').'. '
            .'Pillars: '.($brand->content_pillars ? implode(', ', $brand->content_pillars) : 'n/a');

        $user = <<<PROMPT
Week starting {$weekLabel}.
Creative brief from the marketer:
{$brief}

Pre-built schedule slots (do not add or remove slots; keep index order):
{$slotJson}

Return JSON:
- slots: array with one object per input slot, same order, each with:
  - day_offset, time, format, platform (unchanged from input)
  - topic (short title, unique across the week)
  - objective (one sentence)
  - notes (optional production note)

Avoid duplicate topics. Match format (reel = video angle, story = ephemeral hook, carousel = multi-slide idea).
PROMPT;

        $raw = $this->client->chatJson([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $user],
        ], []);

        $enriched = $raw['slots'] ?? [];
        if (! is_array($enriched) || count($enriched) !== count($slots)) {
            return $this->fallbackEnrich($slots, $brief);
        }

        return array_values(array_map(static function (array $row, int $i) use ($slots): array {
            $base = $slots[$i];

            return [
                'day_offset' => (int) ($row['day_offset'] ?? $base['day_offset']),
                'time' => (string) ($row['time'] ?? $base['time']),
                'format' => (string) ($row['format'] ?? $base['format']),
                'platform' => (string) ($row['platform'] ?? $base['platform']),
                'topic' => (string) ($row['topic'] ?? 'Post '.($i + 1)),
                'objective' => (string) ($row['objective'] ?? ''),
                'notes' => isset($row['notes']) ? (string) $row['notes'] : null,
            ];
        }, $enriched, array_keys($enriched)));
    }

    /**
     * @param  array<int, array{day_offset: int, time: string, format: string, platform: string}>  $slots
     * @return array<int, array{day_offset: int, time: string, format: string, platform: string, topic: string, objective: string}>
     */
    private function fallbackEnrich(array $slots, string $brief): array
    {
        $snippet = mb_substr($brief, 0, 80);

        return array_map(static function (array $slot, int $i) use ($snippet): array {
            return [
                ...$slot,
                'topic' => $snippet !== '' ? "{$snippet} — part ".($i + 1) : 'Weekly post '.($i + 1),
                'objective' => 'Support the weekly brief.',
                'notes' => null,
            ];
        }, $slots, array_keys($slots));
    }
}