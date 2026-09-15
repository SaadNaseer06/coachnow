<x-mail::message>
# New coach application

**{{ $coach->display_name }}** just registered and is waiting for review.

- Specialty: {{ $coach->specialty ?: '—' }}
- Experience: {{ $coach->experience ?: '—' }}
- Email: {{ $coach->user?->email ?: '—' }}

<x-mail::button :url="$adminUrl">
Review in admin
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
