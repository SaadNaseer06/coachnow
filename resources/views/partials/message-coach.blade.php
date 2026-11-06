{{-- Contact for the accepting coach. $contact comes from SessionRequest::coachContactFor(). --}}
@once
  @push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/message-coach.css') }}?v={{ @filemtime(public_path('assets/css/message-coach.css')) ?: time() }}">
  @endpush
@endonce
@php
  $phoneDigits = preg_replace('/[^0-9+]/', '', (string) ($contact['phone'] ?? ''));
@endphp
<div class="msg-coach">
  <div class="msg-coach__actions">
    @if (! empty($contact['email']))
      <a class="msg-coach__btn msg-coach__btn--primary" href="mailto:{{ $contact['email'] }}?subject={{ rawurlencode('CoachNow session') }}">Message Coach</a>
    @endif
    @if ($phoneDigits !== '')
      <a class="msg-coach__btn {{ empty($contact['email']) ? 'msg-coach__btn--primary' : '' }}" href="sms:{{ $phoneDigits }}">{{ empty($contact['email']) ? 'Message Coach' : 'Text' }}</a>
      <a class="msg-coach__btn" href="tel:{{ $phoneDigits }}">Call</a>
    @endif
  </div>
  <p class="msg-coach__line"><strong>{{ $contact['name'] }}</strong></p>
  @if (! empty($contact['email']))
    <p class="msg-coach__line">{{ $contact['email'] }}</p>
  @endif
  @if ($phoneDigits !== '')
    <p class="msg-coach__line">{{ $contact['phone'] }}</p>
  @endif
  @if (! empty($contact['payment_note']))
    <p class="msg-coach__line msg-coach__pay"><strong>How to pay:</strong> {{ $contact['payment_note'] }}</p>
  @endif
</div>
