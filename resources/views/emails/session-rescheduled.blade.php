<x-mail::message>
# {{ $placeChanged && ! $timeChanged ? 'Your session location changed' : ($placeChanged ? 'Your session changed' : 'Your session time changed') }}

**{{ $coachName }}** updated your session.

@if ($timeChanged)
- Was: ~~{{ $oldWhen }}~~
- **Now: {{ $newWhen }}**
@else
- When: {{ $newWhen }}
@endif
@if ($placeChanged)
- Location was: ~~{{ $oldWhere }}~~
- **Location now: {{ $newWhere }}**
@else
- Where: {{ $newWhere }}
@endif
- Player{{ count($players) === 1 ? '' : 's' }}: {{ implode(', ', $players) }}

@if ($directionsUrl)
[Get directions]({{ $directionsUrl }})
@endif

If the change doesn’t work for you, contact your coach or cancel from your dashboard.

<x-mail::button :url="$dashboardUrl">
View my bookings
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
