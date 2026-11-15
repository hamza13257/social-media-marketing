<x-mail::message>
# Welcome, {{ $user->name }}

Your organization **{{ $tenant->name }}** is ready in SocialPilot Marketing.

Complete manual payment if required, then sign in to connect Meta accounts and publish content.

<x-mail::button :url="config('app.frontend_url').'/login'">
Open dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>