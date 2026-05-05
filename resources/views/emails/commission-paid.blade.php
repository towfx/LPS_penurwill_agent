<x-mail::message>
# Commission paid

Your commission of **RM {{ number_format($amount, 2) }}** has been paid on **{{ $paidAt }}**.

<x-mail::button :url="config('app.url').'/agent/payouts'">
View payouts
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
