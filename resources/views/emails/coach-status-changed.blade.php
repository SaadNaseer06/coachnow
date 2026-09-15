<x-mail::message>
# Profile update for {{ $coach->display_name }}

@if ($status === 'active')
Your CoachNow profile is **live** on Find a Coach. Athletes can now discover and book you.
@elseif ($status === 'paused')
Your CoachNow profile was **paused** and is hidden from Find a Coach until an admin reactivates it.
@else
Your CoachNow application status is now **{{ $status }}**.
@endif

<x-mail::button :url="$profileUrl">
Open My Profile
</x-mail::button>

@if ($status === 'active')
Or browse the public listing:

<x-mail::button :url="$findUrl" color="success">
View Find a Coach
</x-mail::button>
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
