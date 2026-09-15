<x-mail::message>
# Session request submitted

Your request **{{ $session->reference }}** is live and waiting for a coach.

- Location: {{ $session->location_name }}
- When: {{ optional($session->session_date)->format('M j, Y') }}{{ $session->session_time ? ' · '.$session->session_time : '' }}
- Type: {{ $session->session_type }}

<x-mail::button :url="$dashboardUrl">
View on dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
