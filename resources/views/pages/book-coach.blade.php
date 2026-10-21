@extends('layouts.app')

@section('title', 'Book '.$coach->display_name.' | CoachNow')
@section('meta_description', 'Book an available session with '.$coach->display_name.' — pick a time and confirm the field.')
@section('body_class', 'book-flow')
@section('hide_footer', true)
@section('hide_preloader', true)

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/book-coach.css') }}?v={{ @filemtime(public_path('assets/css/book-coach.css')) ?: time() }}">
@endpush

@section('content')
<main class="book-page" id="bookCoachApp"
  data-coach="{{ $coach->publicToken() }}"
  data-coach-name="{{ $coach->display_name }}"
  data-rate="{{ (float) $coach->rate }}"
  data-convenience-fee="{{ (float) ($convenienceFee ?? config('coachnow.payments.convenience_fee', 2)) }}"
  data-focus-group="{{ $focusGroupId ?? '' }}"
  data-user-name="{{ auth()->user()?->name }}"
  data-group-rate="{{ $coach->offersGroupBooking() ? (float) $coach->group_rate : '' }}"
  data-max-group-size="{{ (int) ($coach->max_group_size ?? 6) }}"
  data-slots='@json($slotsJson)'
  data-book-url="{{ route('bookings.store') }}"
  data-join-url="{{ url('/api/group-sessions') }}"
  data-dashboard-url="{{ route('player-dashboard') }}"
>
  <section class="book-hero">
    <div class="book-wrap">
      <p class="book-kicker">Direct booking</p>
      <h1>Book {{ $coach->display_name }}</h1>
      <p class="book-lead">Pick an open time — the field for that slot fills in automatically. Confirm and you’re done.</p>
      <div class="book-coach-chip">
        <img src="{{ $coach->photoUrl() }}" alt="">
        <div>
          <strong>{{ $coach->display_name }}</strong>
          <span>${{ number_format((float) $coach->rate, 0) }}/session · {{ $coach->specialty ?: ($coach->sport ?: 'Coach') }}</span>
        </div>
      </div>
    </div>
  </section>

  <section class="book-body">
    <div class="book-wrap book-grid">
      <div class="book-panel">
        <h2>1. Choose a date</h2>
        <div class="book-dates" id="bookDates" role="listbox" aria-label="Available dates"></div>
        <p class="book-empty" id="bookNoDates" hidden>This coach has no open times in the next two weeks. Try <a href="{{ route('request-session', ['coach' => $coach->publicToken()]) }}">Request a session</a> instead.</p>

        <div id="bookSlotSteps">
          <div class="book-legend" aria-label="Slot color guide">
            <span class="book-legend__item"><i class="book-legend__swatch is-open"></i> Open time</span>
            <span class="book-legend__item"><i class="book-legend__swatch is-group"></i> Open group session</span>
            <span class="book-legend__item"><i class="book-legend__swatch is-full"></i> Full</span>
          </div>

          <h2 class="book-step">2. Choose a time</h2>
          <div class="book-times" id="bookTimes"></div>
          <p class="book-hint" id="bookTimeHint">Select a date to see open times.</p>

          <h2 class="book-step">3. Session details</h2>
          <div class="book-field" id="bookField" aria-live="polite">
            <strong>Pick a time</strong>
            <span>Location and group details appear when you choose a slot.</span>
          </div>
        </div>
      </div>

      <aside class="book-aside">
        <div class="book-summary">
          <h2>Confirm</h2>
          <dl>
            <div><dt>Coach</dt><dd id="sumCoach">{{ $coach->display_name }}</dd></div>
            <div><dt>Date</dt><dd id="sumDate">—</dd></div>
            <div><dt>Time</dt><dd id="sumTime">—</dd></div>
            <div><dt>Field</dt><dd id="sumField">—</dd></div>
            <div id="sumTypeRow" hidden><dt>Type</dt><dd id="sumType">—</dd></div>
            <div id="sumAgeRow" hidden><dt>Ages</dt><dd id="sumAge">—</dd></div>
            <div id="sumSpotsRow" hidden><dt>Spots</dt><dd id="sumSpots">—</dd></div>
            <div><dt>Price</dt><dd id="sumPrice">${{ number_format((float) $coach->rate, 0) }}</dd></div>
            <div id="sumTotalRow" hidden><dt>Total</dt><dd id="sumTotal">—</dd></div>
          </dl>
          <div class="book-mode" id="bookMode" role="radiogroup" aria-label="Session type" hidden>
            <button type="button" class="book-mode__opt is-selected" data-book-mode="single" role="radio" aria-checked="true">1-on-1</button>
            <button type="button" class="book-mode__opt" data-book-mode="group" role="radio" aria-checked="false">Private group</button>
          </div>
          <div class="book-players" id="bookPlayers" hidden>
            <div class="book-players__head">
              <label for="bookPlayerCount">Players joining</label>
              <select id="bookPlayerCount"></select>
            </div>
            <div class="book-players__names" id="bookPlayerNames"></div>
            <p class="book-hint">One CoachNow fee per booking, no matter how many players you add.</p>
          </div>
          <p class="book-error" id="bookError" hidden></p>
          <div id="bookPayPanel" class="book-pay-panel" hidden>
            <p class="book-hint" id="bookPayHint">Pay securely in CoachNow — card and Apple Pay where supported.</p>
            <div id="bookStripeMount" class="book-stripe-mount"></div>
            <button type="button" class="book-btn" id="bookPayConfirmBtn">Pay & confirm</button>
            <button type="button" class="book-link" id="bookPayCancelBtn">Cancel payment</button>
          </div>
          <button type="button" class="book-btn" id="bookConfirmBtn" disabled>Confirm booking</button>
          <a href="{{ route('coach-profile', $coach) }}" class="book-link">Back to profile</a>
          <a href="{{ route('request-session', ['coach' => $coach->publicToken()]) }}" class="book-link">Or request an open session</a>
        </div>
        <div class="book-success" id="bookSuccess" hidden>
          <h2>You’re booked</h2>
          <p id="bookSuccessText"></p>
          <a href="{{ route('player-dashboard') }}" class="book-btn">Go to dashboard</a>
        </div>
      </aside>
    </div>
  </section>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/stripe-checkout.js') }}?v={{ @filemtime(public_path('assets/js/stripe-checkout.js')) ?: time() }}"></script>
  <script src="{{ asset('assets/js/book-coach.js') }}?v={{ @filemtime(public_path('assets/js/book-coach.js')) ?: time() }}"></script>
@endpush
