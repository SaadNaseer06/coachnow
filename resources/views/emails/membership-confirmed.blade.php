<x-mail::message>
# Your Family Membership is active

Thanks for joining. Here are your membership terms:

- Price: ${{ $price }} per month
- First charge: {{ $firstCharge }}
@if ($nextBilling)
- Next billing date: {{ $nextBilling }}
@endif
- Renews automatically every month until you cancel

**To cancel,** open your dashboard and choose Cancel membership. Future charges stop, and you keep your membership until the end of the period you paid for.

<x-mail::button :url="$dashboardUrl">
Manage my membership
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
