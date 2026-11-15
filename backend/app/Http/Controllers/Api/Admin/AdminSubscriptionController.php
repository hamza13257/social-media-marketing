<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
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