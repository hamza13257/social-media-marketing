<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Enums\SubscriptionStatus;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => [
                'users' => User::query()->count(),
                'tenants' => Tenant::query()->count(),
                'subscriptions_pending' => Subscription::query()
                    ->where('status', SubscriptionStatus::ManualPending)
                    ->count(),
                'subscriptions_active' => Subscription::query()
                    ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Trialing])
                    ->count(),
                'payment_gateway' => config('billing.gateway')->value,
            ],
        ]);
    }
}