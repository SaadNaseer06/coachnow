<x-mail::message>
@if ($forCoach)
# A player cancelled

**{{ $booking->athlete_name }}** cancelled their spot.
@else
# Your booking is cancelled

**{{ $booking->athlete_name }}** is no longer booked with {{ $booking->coach?->display_name ?? 'your coach' }}.
@endif

- When: {{ $booking->session_date?->format('l, M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($booking->session_time)->format('g:i A') }}
- Where: {{ $booking->location?->name ?? 'Field TBD' }}
- Reference: {{ $booking->reference }}
@if (! $forCoach)
@if ($refund > 0)
- Refund: ${{ number_format($refund, 2) }} back to your card (usually 5–10 business days)
@else
- No card charge to refund
@endif
@endif
@if ($forCoach && $group)
- Group roster now: {{ $group->capacityLabel() }} · {{ $group->spotsRemaining() }} spot{{ $group->spotsRemaining() === 1 ? '' : 's' }} left
@endif

<x-mail::button :url="$url">
{{ $forCoach ? 'Open my schedule' : 'View my bookings' }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
