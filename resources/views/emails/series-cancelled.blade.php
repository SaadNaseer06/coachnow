<x-mail::message>
# Weekly sessions cancelled

**{{ $coachName }}** cancelled the remaining sessions in your weekly series:

@foreach ($sessions as $when)
- {{ $when }}
@endforeach

@if ($refund > 0)
${{ number_format($refund, 2) }} is being refunded to your card (usually 5–10 business days).
@endif

<x-mail::button :url="$dashboardUrl">
View my bookings
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
