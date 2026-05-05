<x-mail::message>
# Renewal reminder

Hi {{ $agentName }},

Your Penurwill membership expires on **{{ $expiresAt }}**. Please log in to renew before the expiry date to keep earning commissions.

<x-mail::button :url="config('app.url').'/agent/profile'">
View your profile
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
