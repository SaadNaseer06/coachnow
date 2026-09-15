<x-mail::message>
# A coach accepted your request

**{{ $coachName }}** accepted session request **{{ $session->reference }}**.

- Location: {{ $session->location_name }}
- When: {{ optional($session->session_date)->format('M j, Y') }}{{ $session->session_time ? ' · '.$session->session_time : '' }}
- Type: {{ $session->session_type }}

<x-mail::button :url="$dashboardUrl">
Open player dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
