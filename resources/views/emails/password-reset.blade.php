<x-mail::message>
# Reset your password

Hi {{ $name }}, we got a request to reset the password for your CoachNow account.

<x-mail::button :url="$resetUrl">
Choose a new password
</x-mail::button>

This link works once and expires in {{ $minutes }} minutes.

If you didn’t ask for this, you can ignore this email. Your password stays the same.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
