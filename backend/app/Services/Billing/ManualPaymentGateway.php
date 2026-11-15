<?php

namespace App\Services\Billing;

use App\Enums\PaymentGatewayDriver;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Mail\SubscriptionActivatedMail;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ManualPaymentGateway implements PaymentGateway
{
    public function driver(): string
    {
        return PaymentGatewayDriver::Manual->value;
    }

    public function beginCheckout(Tenant $tenant, SubscriptionPlan $plan): array
    {
        DB::transaction(function () use ($tenant, $plan) {
            Subscription::query()->updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'subscription_plan_id' => $plan->id,
                    'status' => SubscriptionStatus::ManualPending,
                    'payment_gateway' => PaymentGatewayDriver::Manual,
                    'manual_payment_notes' => null,
                    'activated_at' => null,
                    'expires_at' => null,
                ]
            );
        });

        return [
            'instructions' => config('billing.manual.instructions'),
        ];
    }

    public function markManualPaymentReceived(
        Subscription $subscription,
        string $notes,
        int $activatedByUserId,
    ): Subscription {
        $subscription->fill([
            'status' => SubscriptionStatus::Active,
            'manual_payment_notes' => $notes,
            'activated_by_user_id' => $activatedByUserId,
            'activated_at' => now(),
            'expires_at' => now()->addMonth(),
            'cancelled_at' => null,
        ]);
        $subscription->save();

        $fresh = $subscription->fresh(['plan', 'tenant']);

        if (config('mail.enabled')) {
            $owner = $fresh->tenant->users()->wherePivot('role', 'owner')->first();

            if ($owner) {
                Mail::to($owner->email)->queue(new SubscriptionActivatedMail($fresh));
            }
        }

        return $fresh;
    }
}