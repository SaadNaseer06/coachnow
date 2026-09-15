<x-mail::message>
# Welcome to CoachNow, {{ $name }}

Thanks for joining as a **{{ $role }}**. You're all set to start finding coaches, booking sessions, and building your training plan.

<x-mail::button :url="$dashboardUrl">
Open your dashboard
</x-mail::button>

If you didn’t create this account, you can ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
