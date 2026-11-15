<?php

namespace App\Services\Auth;

use App\Enums\SubscriptionStatus;
use App\Enums\TenantRole;
use App\Mail\WelcomeRegisteredMail;
use App\Mail\NewSubscriptionPendingMail;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Support\Facades\Mail;
trait RegistersMarketingWorkspace
{
    use GeneratesUniqueSlug;

    protected function provisionDefaultTenant(User $user, ?string $organizationName = null): Tenant
    {
        $name = $organizationName ?? $user->name."'s Workspace";

        $tenant = new Tenant(['name' => $name]);
        $tenant->slug = $this->uniqueSlugFor($tenant, $name);
        $tenant->save();

        $tenant->users()->attach($user->id, [
            'role' => TenantRole::Owner->value,
        ]);

        $plan = SubscriptionPlan::query()
            ->where('slug', config('billing.default_plan_slug'))
            ->firstOrFail();

        Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => SubscriptionStatus::ManualPending,
            'payment_gateway' => config('billing.gateway'),
        ]);

        $this->dispatchRegistrationEmails($user, $tenant);

        return $tenant;
    }

    protected function dispatchRegistrationEmails(User $user, Tenant $tenant): void
    {
        if (! config('mail.enabled')) {
            return;
        }

        Mail::to($user->email)->queue(new WelcomeRegisteredMail($user, $tenant));

        $adminEmail = config('mail.admin_address');
        if ($adminEmail) {
            Mail::to($adminEmail)->queue(new NewSubscriptionPendingMail($user, $tenant));
        }
    }

}