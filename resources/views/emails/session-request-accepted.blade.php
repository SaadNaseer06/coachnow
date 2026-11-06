<x-mail::message>
# A coach accepted

**{{ $coachName }}** accepted session request **{{ $session->reference }}**.

- Location: {{ $session->location_name }}
- When: {{ $session->whenLabel() }}
- Type: {{ $session->session_type }}
@if ($session->priceLabel())
- Price: {{ $session->priceLabel() }}, paid directly to the coach
@endif

## Next step: arrange payment

Contact {{ $coachName }} to arrange payment, including any deposit. They'll confirm your place once it's received. CoachNow doesn't handle this payment.

- Email: {{ $session->hostCoach?->user?->email ?? 'Not provided' }}
- Phone: {{ $session->hostCoach?->user?->phone ?: 'Not provided' }}
@if ($session->payment_note)
- How to pay: {{ $session->payment_note }}
@endif

<x-mail::button :url="$dashboardUrl">
Open player dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
