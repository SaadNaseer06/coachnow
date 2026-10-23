@extends('layouts.app')

@section('title', ($session->session_type ?: 'Open session request').' at '.($session->location?->name ?? $session->location_name).' | CoachNow')
@section('meta_description', 'Join an open training request on CoachNow — '.($session->sport ?: 'training').' at '.($session->location?->name ?? $session->location_name).'.')
@section('body_class', 'book-flow')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/book-coach.css') }}?v={{ @filemtime(public_path('assets/css/book-coach.css')) ?: time() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/payment-methods.css') }}?v={{ @filemtime(public_path('assets/css/payment-methods.css')) ?: time() }}">
@endpush

@section('content')
@php
  $isAthlete = auth()->user()?->isAthlete();
  $waiting = $session->status === 'open';
  $looking = $session->max_players !== null ? max(0, (int) $session->max_players - $joinedCount) : null;
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
          <strong>{{ $waiting ? 'Waiting for a coach' : 'Coach confirmed: '.($session->hostCoach?->display_name ?? 'Coach') }}</strong>
          <div class="book-field__meta">
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
            @if ($session->price_range)
              <span><strong>Budget:</strong> {{ $session->price_range }}</span>
            @endif
            <span><strong>Players:</strong> {{ $joinedCount }} joined @if($looking !== null) · {{ $looking }} spot{{ $looking === 1 ? '' : 's' }} left @endif</span>
          </div>
        </div>
        <p class="book-hint" style="margin-top:1rem">
          @if ($waiting)
            Join now to share the session. Your card is saved and the ${{ number_format($depositAmount, 0) }} deposit + ${{ number_format($convenienceFee, 0) }} CoachNow fee is charged only if a coach accepts.
          @else
            A coach is hosting this session. Join with a ${{ number_format($depositAmount, 0) }} deposit + ${{ number_format($convenienceFee, 0) }} CoachNow fee until 1 hour before it starts.
          @endif
        </p>
      </div>

      <aside class="book-aside">
        <div class="book-summary" id="openRequestSummary">
          <h2>Join this session</h2>
          @if ($alreadyIn)
            <p class="book-hint">You’re already on this request. Track it under My session requests.</p>
            <a href="{{ route('player-dashboard') }}#my-requests" class="book-btn">Go to my dashboard</a>
          @elseif ($isFull)
            <p class="book-error" style="display:block">This session is full.</p>
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
            <p class="book-hint">Joining session requests is part of the Family Membership (${{ number_format((float) config('coachnow.subscription.price', 10), 0) }}/month — also waives booking fees).</p>
            <form method="POST" action="{{ route('subscription.checkout') }}">
              @csrf
              <button type="submit" class="book-btn">Become a member</button>
            </form>
          @else
            @include('partials.payment-methods', ['payPrefix' => 'openReqPay'])
            <p class="book-error" id="openRequestError" hidden></p>
            <button type="button" class="book-btn" id="openRequestJoinBtn">
              {{ $waiting ? 'Save card & join' : 'Pay $'.number_format($depositAmount + $convenienceFee, 0).' & join' }}
            </button>
          @endif
          <a href="{{ route('training-near-you') }}" class="book-link">Back to Training Near You</a>
        </div>
        <div class="book-success" id="openRequestSuccess" hidden>
          <h2>You’re in</h2>
          <p id="openRequestSuccessText"></p>
          <a href="{{ route('player-dashboard') }}#my-requests" class="book-btn">Go to dashboard</a>
        </div>
      </aside>
    </div>
  </section>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/stripe-checkout.js') }}?v={{ @filemtime(public_path('assets/js/stripe-checkout.js')) ?: time() }}"></script>
  <script src="{{ asset('assets/js/payment-methods.js') }}?v={{ @filemtime(public_path('assets/js/payment-methods.js')) ?: time() }}"></script>
  <script>
  (() => {
    const root = document.getElementById('openRequestApp');
    const btn = document.getElementById('openRequestJoinBtn');
    if (!root || !btn) return;

    const errorEl = document.getElementById('openRequestError');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const payRoot = document.querySelector('[data-payment-root="openReqPay"]');
    window.CoachNowPayment?.mount(payRoot);

    btn.addEventListener('click', async () => {
      errorEl.hidden = true;
      const wallet = window.CoachNowPayment?.api(payRoot);
      window.CoachNowBusy?.setBusy(btn, { label: 'Processing…' });
      try {
        const result = await wallet?.authorize?.();
        if (!result?.ok) return;
        const paidWith = result.method?.label || 'Card on file';

        const res = await fetch(root.dataset.joinUrl, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify({
            paid_with: paidWith,
            card_on_file: paidWith,
            stripe_payment_method_id: result.stripe_payment_method_id || null,
          }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || 'Could not join this session.');

        document.getElementById('openRequestSummary').hidden = true;
        document.getElementById('openRequestSuccess').hidden = false;
        document.getElementById('openRequestSuccessText').textContent = data.message || 'You joined this session.';
      } catch (err) {
        errorEl.hidden = false;
        errorEl.textContent = err.message || 'Could not join this session.';
      } finally {
        window.CoachNowBusy?.clearBusy(btn);
      }
    });
  })();
  </script>
@endpush
