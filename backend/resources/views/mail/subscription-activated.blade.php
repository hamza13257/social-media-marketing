<x-mail::message>
# Subscription active

Your **{{ $subscription->plan->name }}** plan for **{{ $subscription->tenant->name }}** is now active.

You can create brands, connect social accounts, and publish content.

<x-mail::button :url="config('app.frontend_url').'/dashboard'">
Go to workspace
</x-mail::button>
</x-mail::message>