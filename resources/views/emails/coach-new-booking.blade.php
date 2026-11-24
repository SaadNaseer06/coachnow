<x-mail::message>
# New booking: {{ $kindLabel }}

**{{ $parentName }}** booked {{ count($players) }} player{{ count($players) === 1 ? '' : 's' }} with you.

@if (count($sessions) > 1)
- {{ count($sessions) }} sessions{{ $isSeries ? ' (weekly series)' : '' }}:
@foreach ($sessions as $when)
    - {{ $when }}
@endforeach
@else
- When: {{ $first->session_date?->format('l, M j, Y') }} · {{ \Illuminate\Support\Carbon::parse($first->session_time)->format('g:i A') }}
@endif
- Where: {{ $first->location?->name ?? 'Field TBD' }}
- Player{{ count($players) === 1 ? '' : 's' }}: {{ implode(', ', $players) }}
@if ($due > 0)
- They pay you directly: ${{ number_format($due, 2) }}
@endif
@if ($parent?->phone || $parent?->email)
- Contact: {{ collect([$parent->phone, $parent->email])->filter()->implode(' · ') }}
@endif
@if ($group)
- Group roster: {{ $group->capacityLabel() }} · {{ $group->spotsRemaining() }} spot{{ $group->spotsRemaining() === 1 ? '' : 's' }} left
@endif

@if ($isPrivateGroup && $group && $group->spotsRemaining() > 0)
This group is **private**. Want to fill the {{ $group->spotsRemaining() }} remaining spot{{ $group->spotsRemaining() === 1 ? '' : 's' }}? Set an age range and choose **Open to public** in Manage session.
@endif

@if ($due > 0 && blank($howToPay))
Families don’t know how to pay you yet. [Add your payment details]({{ $profileUrl }}) to your profile so they see them after booking.
@endif

<x-mail::button :url="$scheduleUrl">
Open my schedule
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
