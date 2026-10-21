<x-mail::message>
# You’re booked

**{{ $kindLabel }}** with **{{ $first->coach?->display_name ?? 'your coach' }}**.

- When: {{ $first->session_date?->format('l, M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($first->session_time)->format('g:i A') }}
- Where: {{ $first->location?->name ?? 'Field TBD' }}{{ $first->location?->area ? ' · '.$first->location->area : '' }}
- Player{{ count($players) === 1 ? '' : 's' }}: {{ implode(', ', $players) }}
@if ($first->groupSession?->age_range)
- Ages: {{ $first->groupSession->age_range }}
@endif
@if ($total > 0)
- Total: ${{ number_format($total, 2) }}@if ($fee > 0) (includes ${{ number_format($fee, 2) }} CoachNow fee)@endif

@endif
- Reference: {{ $first->reference }}

<x-mail::button :url="$dashboardUrl">
View my bookings
</x-mail::button>

Need to cancel? You can cancel from your dashboard up to {{ (int) config('coachnow.payments.cancel_cutoff_hours', 24) }} hours before the session.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
