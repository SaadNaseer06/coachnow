@extends('layouts.app')

@section('title', ($session->session_type ?: 'Open session request').' at '.($session->location?->name ?? $session->location_name).' | CoachNow')
@section('meta_description', 'Join an open training request on CoachNow — '.($session->sport ?: 'training').' at '.($session->location?->name ?? $session->location_name).'.')
@section('body_class', 'book-flow')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/book-coach.css') }}?v={{ @filemtime(public_path('assets/css/book-coach.css')) ?: time() }}">
@endpush

@section('content')
@php
  $isAthlete = auth()->user()?->isAthlete();
  $waiting = $session->status === 'open';
  $spotsLeft = $session->spotsLeft();
  $confirmedCount = $session->confirmedCount();
  $pendingCount = $session->pendingJoinCount();
  $needsPhone = auth()->check() && blank(auth()->user()->phone);
@endphp
<main class="book-page" id="openRequestApp"
  data-join-url="{{ route('session-requests.join', $session->reference) }}"
>
  <section class="book-hero">
    <div class="book-wrap">
      <p class="book-kicker">Open session request</p>
      <h1>{{ $session->session_type ?: 'Training session' }}</h1>
      <p class="book-lead">
        {{ $session->location?->name ?? $session->location_name }} ·
        {{ $session->session_date?->format('D, M j') ?? 'Flexible date' }}@if($session->session_time) at {{ \Illuminate\Support\Carbon::parse($session->session_time)->format('g:i A') }}@endif
      </p>
    </div>
  </section>

  <section class="book-body">
    <div class="book-wrap book-grid">
      <div class="book-panel">
        <div class="book-field">
          <strong>{{ $waiting ? 'Waiting for a coach' : 'Coach: '.($session->hostCoach?->display_name ?? 'Coach') }}</strong>
          <div class="book-field__meta">
            @if ($session->price_per_player !== null)
              <span><strong>Session price:</strong> {{ $session->priceLabel() }}</span>
            @elseif ($session->price_range)
              <span><strong>Budget:</strong> {{ $session->price_range }}</span>
            @endif
            @if ($session->sport)
              <span><strong>Sport:</strong> {{ $session->sport }}</span>
            @endif
            <span><strong>Where:</strong> {{ $session->location?->name ?? $session->location_name }}@if($session->location?->area ?? $session->location_city) · {{ $session->location?->area ?? $session->location_city }}@endif</span>
            @if ($session->age_range)
              <span><strong>Ages:</strong> {{ $session->age_range }}</span>
            @endif
            @if ($session->player_level)
              <span><strong>Level:</strong> {{ $session->player_level }}</span>
            @endif
            <span>
              <strong>Players:</strong>
              {{ $confirmedCount }} confirmed · {{ $pendingCount }} pending
              @if ($spotsLeft !== null)
                · {{ $spotsLeft }} spot{{ $spotsLeft === 1 ? '' : 's' }} left
              @endif
            </span>
          </div>
        </div>
        <p class="book-hint" style="margin-top:1rem">
          @if ($waiting)
            No payment through CoachNow. Once a coach accepts, they’ll set the price and you’ll arrange payment directly with them.
          @else
            You pay the coach directly, including any deposit. CoachNow doesn’t collect this payment. Your place is held once the coach confirms you.
          @endif
        </p>
      </div>

      <aside class="book-aside">
        <div class="book-summary" id="openRequestSummary">
          @if ($myPlace)
            @if ($myPlace->status === \App\Models\SessionRequestPlayer::DECLINED)
              <h2>Not confirmed</h2>
              <p class="book-hint">The coach couldn’t confirm your place on this session.</p>
            @elseif ($myPlace->status === \App\Models\SessionRequestPlayer::CANCELLED)
              <h2>Place cancelled</h2>
              <p class="book-hint">You’re no longer on this session.</p>
            @elseif ($myPlace->isConfirmed())
              <h2>You’re confirmed</h2>
              <p class="book-hint">Your place on this session is confirmed.</p>
            @elseif ($myPlace->isRequester())
              <h2>{{ $coachContact ? 'Arrange payment' : 'Your request' }}</h2>
              <p class="book-hint">{{ $coachContact ? 'Your coach will confirm your place once your deposit is received.' : 'You’ll hear from us as soon as a coach accepts.' }}</p>
            @else
              <h2>Pending coach approval</h2>
              <p class="book-hint">{{ $coachContact ? 'Your request is in. Message the coach to arrange payment, and they’ll confirm your place.' : 'Your request is in. Once a coach accepts, you can message them to arrange payment.' }}</p>
            @endif
            @if ($coachContact)
              @include('partials.message-coach', ['contact' => $coachContact])
            @endif
            <a href="{{ route('player-dashboard') }}#my-requests" class="book-btn">Go to my dashboard</a>
          @else
            <h2>Join this session</h2>
            @if ($isFull)
              <p class="book-error" style="display:block">This session is full.</p>
            @elseif ($requestExpired ?? false)
              <p class="book-error" style="display:block">No coach accepted this request before its deadline, so it’s closed.</p>
            @elseif ($joinClosed)
              <p class="book-error" style="display:block">Joining closed 1 hour before the session.</p>
            @elseif (! auth()->check())
              <a href="{{ route('login') }}?redirect={{ urlencode(request()->getRequestUri()) }}" class="book-btn">Sign in to join</a>
            @elseif (! $isAthlete)
              <p class="book-hint">Sign in with a parent/player account to join.</p>
              @if (auth()->user()->isCoach() && $waiting)
                <a href="{{ route('coach.dashboard', ['requests' => 'open']) }}" class="book-btn">View &amp; accept as coach</a>
              @endif
            @elseif (! auth()->user()->hasMemberAccess())
              <p class="book-hint">Joining session requests is part of the Family Membership.</p>
              @include('partials.membership-terms')
              <form method="POST" action="{{ route('subscription.checkout') }}">
                @csrf
                <button type="submit" class="book-btn">Become a member</button>
              </form>
            @else
              @if ($needsPhone)
                <label class="book-field-label" for="openRequestPhone">Your phone number</label>
                <input class="book-input" type="tel" id="openRequestPhone" maxlength="40" autocomplete="tel" placeholder="(555) 123-4567">
                <p class="book-hint">The coach uses this to arrange payment with you.</p>
              @endif
              <p class="book-error" id="openRequestError" hidden></p>
              <button type="button" class="book-btn" id="openRequestJoinBtn">Request to Join</button>
              <p class="book-hint">No payment now. The coach confirms your place once payment is arranged.</p>
            @endif
          @endif
          <a href="{{ route('training-near-you') }}" class="book-link">Back to Training Near You</a>
        </div>
        <div class="book-success" id="openRequestSuccess" hidden>
          <h2>Pending coach approval</h2>
          <p id="openRequestSuccessText"></p>
          <a href="{{ request()->getRequestUri() }}" class="book-btn" id="openRequestContinue">Message Coach</a>
          <a href="{{ route('player-dashboard') }}#my-requests" class="book-link">Go to dashboard</a>
        </div>
      </aside>
    </div>
  </section>
</main>
@endsection

@push('scripts')
  <script>
  (() => {
    const root = document.getElementById('openRequestApp');
    const btn = document.getElementById('openRequestJoinBtn');
    if (!root || !btn) return;

    const errorEl = document.getElementById('openRequestError');
    const phoneEl = document.getElementById('openRequestPhone');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    btn.addEventListener('click', async () => {
      errorEl.hidden = true;
      if (phoneEl && !phoneEl.value.trim()) {
        errorEl.hidden = false;
        errorEl.textContent = 'Add a phone number so the coach can reach you.';
        phoneEl.focus();
        return;
      }

      window.CoachNowBusy?.setBusy(btn, { label: 'Sending…' });
      try {
        const res = await fetch(root.dataset.joinUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({ phone: phoneEl?.value.trim() || null }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Could not send your request to join.');

        document.getElementById('openRequestSummary').hidden = true;
        document.getElementById('openRequestSuccess').hidden = false;
        document.getElementById('openRequestSuccessText').textContent = data.message || 'Your request to join is in.';
        // Nobody to message until a coach accepts.
        const next = document.getElementById('openRequestContinue');
        if (!data.data?.coach_contact) next.textContent = 'View this session';
      } catch (err) {
        errorEl.hidden = false;
        errorEl.textContent = err.message || 'Could not send your request to join.';
      } finally {
        window.CoachNowBusy?.clearBusy(btn);
      }
    });
  })();
  </script>
@endpush
