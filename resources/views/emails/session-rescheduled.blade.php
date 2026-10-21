<x-mail::message>
# Your session time changed

**{{ $coachName }}** moved your session.

- Was: ~~{{ $oldWhen }}~~
- **Now: {{ $newWhen }}**
- Where: {{ $booking->location?->name ?? 'Field TBD' }}
- Player{{ count($players) === 1 ? '' : 's' }}: {{ implode(', ', $players) }}

If the new time doesn’t work, reply to your coach or cancel from your dashboard.

<x-mail::button :url="$dashboardUrl">
View my bookings
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
