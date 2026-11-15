<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\SubscriptionPlan;
use App\Services\Billing\BillingManager;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        private CurrentTenant $currentTenant,
        private BillingManager $billing,
    ) {}

    public function subscription(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $subscription = $tenant->subscription()->with('plan')->first();

        return response()->json([
            'data' => $subscription ? new SubscriptionResource($subscription) : null,
            'gateway' => config('billing.gateway')->value,
            'manual_instructions' => config('billing.manual.instructions'),
        ]);
    }

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::query()->where('is_public', true)->orderBy('price_cents')->get();

        return response()->json(['data' => SubscriptionPlanResource::collection($plans)]);
    }

    public function requestPlan(Request $request): JsonResponse
    {
        $tenant = $request->attributes->get('tenant');
        $this->authorize('manageBilling', $tenant);

        $data = $request->validate([
            'plan_slug' => ['required', 'string', 'exists:subscription_plans,slug'],
        ]);

        $checkout = $this->billing->requestPlan($tenant, $data['plan_slug']);

        return response()->json([
            'message' => 'Plan change requested.',
            'checkout' => $checkout,
            'subscription' => new SubscriptionResource($tenant->subscription()->with('plan')->first()),
        ]);
    }
}