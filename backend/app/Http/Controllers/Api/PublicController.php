<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class PublicController extends Controller
{
    public function bootstrap(): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->where('is_public', true)
            ->orderBy('price_cents')
            ->get();

        return response()->json([
            'data' => [
                'app_name' => config('app.name'),
                'tagline' => 'Social media marketing for growing brands.',
                'google_auth_enabled' => (bool) config('services.google.client_id'),
                'payment_gateway' => config('billing.gateway')->value,
                'plans' => SubscriptionPlanResource::collection($plans),
                'features' => [
                    'Multi-brand workspace',
                    'Meta Facebook & Instagram',
                    'AI captions (Groq)',
                    'Approvals & scheduling',
                    'Manual or Stripe billing',
                ],
            ],
        ]);
    }
}