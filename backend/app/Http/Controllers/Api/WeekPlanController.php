<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\WeekPlan;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeekPlanController extends Controller
{
    public function __construct(private CurrentTenant $currentTenant) {}

    public function index(): JsonResponse
    {
        $plans = WeekPlan::query()
            ->with(['brand:id,name', 'author:id,name'])
            ->where('tenant_id', $this->currentTenant->id())
            ->orderByDesc('week_start')
            ->limit(20)
            ->get();

        return response()->json(['data' => $plans]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'brand_id' => ['required', 'integer', 'exists:brands,id'],
            'week_start' => ['required', 'date'],
            'brief' => ['required', 'string', 'max:5000'],
            'rhythm' => ['required', 'array'],
            'rhythm.posts_per_day' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'rhythm.reels_per_week' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'rhythm.stories_per_day' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'rhythm.platforms' => ['sometimes', 'array'],
            'rhythm.platforms.*' => ['string', 'in:instagram,facebook'],
            'rhythm.generate_captions' => ['sometimes', 'boolean'],
        ]);

        $brand = Brand::query()->findOrFail($data['brand_id']);
        if ((int) $brand->tenant_id !== $this->currentTenant->id()) {
            abort(403);
        }

        $plan = WeekPlan::query()->updateOrCreate(
            [
                'tenant_id' => $this->currentTenant->id(),
                'brand_id' => $data['brand_id'],
                'week_start' => $data['week_start'],
            ],
            [
                'created_by_user_id' => $request->user()->id,
                'brief' => $data['brief'],
                'rhythm' => $data['rhythm'],
                'status' => 'draft',
            ]
        );

        $plan->load(['brand:id,name', 'author:id,name']);

        return response()->json([
            'data' => $plan,
            'message' => 'Week plan saved. Full AI auto-fill and image generation are planned in the next phase — see docs/ROADMAP-WEEKLY-AUTOMATION.md.',
        ], 201);
    }
}