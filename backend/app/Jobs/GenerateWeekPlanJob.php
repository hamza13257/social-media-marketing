<?php

namespace App\Jobs;

use App\Models\WeekPlan;
use App\Services\Content\WeekPlanGenerationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateWeekPlanJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $uniqueFor = 3600;

    public function __construct(public int $weekPlanId) {}

    public function uniqueId(): string
    {
        return 'generate-week-plan-'.$this->weekPlanId;
    }

    public function handle(WeekPlanGenerationService $service): void
    {
        $plan = WeekPlan::query()->findOrFail($this->weekPlanId);
        $service->run($plan);
    }
}