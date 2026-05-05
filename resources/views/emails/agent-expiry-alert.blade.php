<x-mail::message>
# Membership expires today

Hi {{ $agentName }},

Your Penurwill membership expires **today** ({{ $expiresAt }}). Please renew immediately to avoid suspension of your account.

<x-mail::button :url="config('app.url').'/agent/profile'">
Renew now
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
