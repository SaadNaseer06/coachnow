<x-mail::message>
# New booking: {{ $kindLabel }}

**{{ $parentName }}** booked {{ count($players) }} player{{ count($players) === 1 ? '' : 's' }} with you.

- When: {{ $first->session_date?->format('l, M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($first->session_time)->format('g:i A') }}
- Where: {{ $first->location?->name ?? 'Field TBD' }}
- Player{{ count($players) === 1 ? '' : 's' }}: {{ implode(', ', $players) }}
@if ($payout > 0)
- Your share: ${{ number_format($payout, 2) }}
@endif
@if ($group)
- Group roster: {{ $group->capacityLabel() }} · {{ $group->spotsRemaining() }} spot{{ $group->spotsRemaining() === 1 ? '' : 's' }} left
@endif

@if ($isPrivateGroup && $group && $group->spotsRemaining() > 0)
This group is **private**. Want to fill the {{ $group->spotsRemaining() }} remaining spot{{ $group->spotsRemaining() === 1 ? '' : 's' }}? Set an age range and choose **Open to public** in Manage session.
@endif

<x-mail::button :url="$scheduleUrl">
Open my schedule
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
