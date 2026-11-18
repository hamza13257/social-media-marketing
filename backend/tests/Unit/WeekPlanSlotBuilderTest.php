<?php

namespace Tests\Unit;

use App\Services\Content\WeekPlanSlotBuilder;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class WeekPlanSlotBuilderTest extends TestCase
{
    public function test_builds_expected_slot_count(): void
    {
        $builder = new WeekPlanSlotBuilder;
        $slots = $builder->build(Carbon::parse('2026-11-17'), [
            'posts_per_day' => 1,
            'reels_per_week' => 1,
            'stories_per_day' => 0,
            'platforms' => ['instagram'],
        ]);

        $this->assertGreaterThanOrEqual(8, count($slots));
    }
}