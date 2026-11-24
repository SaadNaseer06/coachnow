<x-mail::message>
# Weekly sessions cancelled

**{{ $coachName }}** cancelled the remaining sessions in your weekly series:

@foreach ($sessions as $when)
- {{ $when }}
@endforeach

@if ($refund > 0)
Your CoachNow fees for these sessions (${{ number_format($refund, 2) }}) are being refunded to your card, usually within 5–10 business days.
@endif

If you’ve already paid your coach for these sessions, contact them about a refund.

<x-mail::button :url="$dashboardUrl">
View my bookings
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
