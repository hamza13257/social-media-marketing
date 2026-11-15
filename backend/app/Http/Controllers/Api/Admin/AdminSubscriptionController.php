<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\PaymentGatewayDriver;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\Billing\ManualPaymentGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    public function pending(): JsonResponse
    {
        $items = Subscription::query()
            ->with(['plan', 'tenant'])
            ->where('status', SubscriptionStatus::ManualPending)
            ->latest('updated_at')
            ->get();

        return response()->json(['data' => SubscriptionResource::collection($items)]);
    }

    public function activate(Request $request, Subscription $subscription): JsonResponse
    {
        if ($subscription->status !== SubscriptionStatus::ManualPending) {
            return response()->json([
                'message' => 'Only manual_pending subscriptions can be activated manually.',
            ], 422);
        }

        $data = $request->validate([
            'notes' => ['required', 'string', 'max:2000'],
        ]);

        $updated = app(ManualPaymentGateway::class)->markManualPaymentReceived(
            $subscription,
            $data['notes'],
            (int) $request->user()->id,
        );

        return response()->json([
            'message' => 'Subscription activated.',
            'data' => new SubscriptionResource($updated),
        ]);
    }

    public function upsertForTenant(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'plan_slug' => ['required', 'string', 'exists:subscription_plans,slug'],
            'status' => ['required', 'in:manual_pending,active,suspended'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $plan = SubscriptionPlan::query()->where('slug', $data['plan_slug'])->firstOrFail();

        if ($data['status'] === SubscriptionStatus::Active->value) {
            $subscription = Subscription::query()->firstOrNew(['tenant_id' => $tenant->id]);
            $subscription->subscription_plan_id = $plan->id;
            $subscription->payment_gateway = PaymentGatewayDriver::Manual;
            $subscription->status = SubscriptionStatus::ManualPending;
            $subscription->save();

            $subscription = app(ManualPaymentGateway::class)->markManualPaymentReceived(
                $subscription,
                $data['notes'] ?? 'Activated from admin.',
                (int) $request->user()->id,
            );

            return response()->json([
                'message' => 'Subscription activated.',
                'data' => new SubscriptionResource($subscription),
            ]);
        }

        $subscription = Subscription::query()->updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => SubscriptionStatus::from($data['status']),
                'payment_gateway' => PaymentGatewayDriver::Manual,
                'manual_payment_notes' => $data['notes'] ?? null,
                'activated_at' => null,
                'expires_at' => null,
            ]
        );

        return response()->json([
            'message' => 'Subscription saved.',
            'data' => new SubscriptionResource($subscription->load(['plan', 'tenant'])),
        ]);
    }

    public function suspend(Subscription $subscription): JsonResponse
    {
        $subscription->status = SubscriptionStatus::Suspended;
        $subscription->save();

        return response()->json([
            'message' => 'Subscription suspended.',
            'data' => new SubscriptionResource($subscription->fresh(['plan'])),
        ]);
    }
}