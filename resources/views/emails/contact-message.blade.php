<x-mail::message>
# New contact message

**From:** {{ $payload['name'] }} ({{ $payload['email'] }})  
**Topic:** {{ $payload['topic'] }}  
**Sent from:** {{ $payload['source'] ?? 'Contact page' }}

{{ $payload['message'] }}

Reply directly to this email to respond to the sender.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
