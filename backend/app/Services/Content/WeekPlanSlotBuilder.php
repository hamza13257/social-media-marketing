<?php

namespace App\Services\Content;

use App\Enums\ContentFormat;
use App\Enums\ContentPlatform;
use Carbon\Carbon;

class WeekPlanSlotBuilder
{
    /**
     * @return array<int, array{day_offset: int, time: string, format: string, platform: string}>
     */
    public function build(Carbon $weekStart, array $rhythm): array
    {
        $postsPerDay = (int) ($rhythm['posts_per_day'] ?? 1);
        $reelsPerWeek = (int) ($rhythm['reels_per_week'] ?? 0);
        $storiesPerDay = (int) ($rhythm['stories_per_day'] ?? 0);
        $platforms = $rhythm['platforms'] ?? ['instagram'];

        $postTimes = ['10:00', '14:00', '18:00', '20:30'];
        $storyTimes = ['09:00', '19:00'];
        $reelTime = '17:00';

        $slots = [];
        $reelDayOffsets = $this->spreadAcrossWeek($reelsPerWeek);

        for ($offset = 0; $offset < 7; $offset++) {
            $platform = $this->pickPlatform($platforms, $offset);

            for ($i = 0; $i < $postsPerDay; $i++) {
                $slots[] = [
                    'day_offset' => $offset,
                    'time' => $postTimes[$i] ?? $postTimes[0],
                    'format' => $i % 2 === 0 ? ContentFormat::SingleImage->value : ContentFormat::Carousel->value,
                    'platform' => $platform,
                ];
            }

            for ($i = 0; $i < $storiesPerDay; $i++) {
                $slots[] = [
                    'day_offset' => $offset,
                    'time' => $storyTimes[$i] ?? $storyTimes[0],
                    'format' => ContentFormat::Story->value,
                    'platform' => ContentPlatform::Instagram->value,
                ];
            }

            if (in_array($offset, $reelDayOffsets, true)) {
                $slots[] = [
                    'day_offset' => $offset,
                    'time' => $reelTime,
                    'format' => ContentFormat::Reel->value,
                    'platform' => ContentPlatform::Instagram->value,
                ];
            }
        }

        return $slots;
    }

    /**
     * @return array<int, int>
     */
    private function spreadAcrossWeek(int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $offsets = [];
        $step = max(1, (int) floor(7 / $count));
        for ($i = 0; $i < $count; $i++) {
            $offsets[] = min(6, $i * $step);
        }

        return array_values(array_unique($offsets));
    }

    private function pickPlatform(array $platforms, int $dayOffset): string
    {
        $platforms = array_values(array_filter($platforms));
        if ($platforms === []) {
            return ContentPlatform::Instagram->value;
        }

        return (string) $platforms[$dayOffset % count($platforms)];
    }
}