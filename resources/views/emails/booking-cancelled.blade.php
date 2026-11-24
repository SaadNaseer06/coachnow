<x-mail::message>
@if ($forCoach)
# A player cancelled

**{{ $booking->athlete_name }}** cancelled their spot.
@elseif ($byCoach)
# Your session was cancelled

**{{ $booking->coach?->display_name ?? 'Your coach' }}** cancelled this session for **{{ $players }}**.
@else
# Your booking is cancelled

**{{ $players }}** is no longer booked with {{ $booking->coach?->display_name ?? 'your coach' }}.
@endif

- When: {{ $booking->session_date?->format('l, M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($booking->session_time)->format('g:i A') }}
- Where: {{ $booking->location?->label() ?? 'Field TBD' }}
- Reference: {{ $booking->reference }}
@if ($feeRefund > 0)
- CoachNow fee refunded: ${{ number_format($feeRefund, 2) }} back to your card (usually 5–10 business days)
@endif
@if ($forCoach && $group)
- Group roster now: {{ $group->capacityLabel() }} · {{ $group->spotsRemaining() }} spot{{ $group->spotsRemaining() === 1 ? '' : 's' }} left
@endif

@if ($forCoach)
If they’ve already paid you, please arrange any refund with them directly.
@else
If you’ve already paid your coach, contact them about a refund{{ $booking->coach?->user?->phone ? ': '.$booking->coach->user->phone : '' }}.
@endif

<x-mail::button :url="$url">
{{ $forCoach ? 'Open my schedule' : 'View my bookings' }}
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
