<x-mail::message>
# New commission earned

You earned a **{{ $commissionType }}** commission of **RM {{ number_format($amount, 2) }}** from sale #{{ $saleId }}.

<x-mail::button :url="config('app.url').'/agent/commissions'">
View commissions
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
