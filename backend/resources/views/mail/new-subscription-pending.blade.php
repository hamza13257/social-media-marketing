<x-mail::message>
# Subscription pending

**{{ $user->name }}** ({{ $user->email }}) created **{{ $tenant->name }}**.

Activate the subscription in the admin console when payment is confirmed.

<x-mail::button :url="config('app.frontend_url').'/admin/subscriptions'">
Review queue
</x-mail::button>
</x-mail::message>