<x-mail::message>
@if ($confirmed)
# Your place is confirmed

**{{ $coachName }}** confirmed your place.
@else
# Your place wasn't confirmed

**{{ $coachName }}** couldn't confirm your place on this session.
@endif

- Session: {{ $session->session_type }}
- When: {{ $session->whenLabel() }}
- Location: {{ $session->location_name }}

<x-mail::button :url="$dashboardUrl">
Open player dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
