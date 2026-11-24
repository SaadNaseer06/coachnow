<x-mail::message>
# You’re booked

**{{ $kindLabel }}** with **{{ $first->coach?->display_name ?? 'your coach' }}**.

@if (count($sessions) > 1)
- {{ count($sessions) }} sessions{{ $isSeries ? ' (weekly series)' : '' }}:
@foreach ($sessions as $when)
    - {{ $when }}
@endforeach
@else
- When: {{ $first->session_date?->format('l, M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($first->session_time)->format('g:i A') }}
@endif
- Where: {{ $first->location?->name ?? 'Field TBD' }}{{ $first->location?->area ? ' · '.$first->location->area : '' }}
- Player{{ count($players) === 1 ? '' : 's' }}: {{ implode(', ', $players) }}
@if ($first->groupSession?->age_range)
- Ages: {{ $first->groupSession->age_range }}
@endif
@if ($feePaid > 0)
- CoachNow fee paid by card: ${{ number_format($feePaid, 2) }}
@elseif ($feeWaived)
- CoachNow fee: none, you’re a member
@endif
- Reference: {{ $first->reference }}

@if ($dueToCoach > 0)
**Pay {{ $contact['name'] ?? 'your coach' }} ${{ number_format($dueToCoach, 2) }} directly.**
@if (! empty($contact['payment_note']))
How to pay: {{ $contact['payment_note'] }}
@else
Your coach will let you know how to pay.
@endif

@endif
@if (! empty($contact['phone']) || ! empty($contact['email']))
Contact {{ $contact['name'] }}: {{ collect([$contact['phone'] ?? null, $contact['email'] ?? null])->filter()->implode(' · ') }}
@endif

<x-mail::button :url="$dashboardUrl">
View my bookings
</x-mail::button>

Need to cancel? You can cancel from your dashboard up to {{ (int) config('coachnow.payments.cancel_cutoff_hours', 24) }} hours before the session. Your coach handles any refund of session payments.@if ($feePaid > 0) The CoachNow fee is non-refundable if you cancel.@endif


Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
