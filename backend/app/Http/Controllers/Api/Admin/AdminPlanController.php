<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::query()->orderBy('price_cents')->get();

        return response()->json(['data' => SubscriptionPlanResource::collection($plans)]);
    }

    public function update(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'price_cents' => ['sometimes', 'integer', 'min:0'],
            'is_public' => ['sometimes', 'boolean'],
            'limits' => ['sometimes', 'array'],
        ]);

        $plan->fill($data);
        $plan->save();

        return response()->json([
            'message' => 'Plan updated.',
            'data' => new SubscriptionPlanResource($plan),
        ]);
    }
}