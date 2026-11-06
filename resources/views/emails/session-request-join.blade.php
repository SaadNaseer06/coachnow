<x-mail::message>
# {{ $player->name }} asked to join

**{{ $player->name }}** asked to join your session.

- Session: {{ $session->session_type }}
- When: {{ $session->whenLabel() }}
- Location: {{ $session->location_name }}
@if ($session->priceLabel())
- Price: {{ $session->priceLabel() }}
@endif

Contact them to arrange payment. They aren't on the session until you confirm them.

- Email: {{ $player->user?->email ?? 'Not provided' }}
- Phone: {{ $player->user?->phone ?: 'Not provided' }}

<x-mail::button :url="$dashboardUrl">
Review this request
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
