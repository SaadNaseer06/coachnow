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
  data-slots='@json($slotsJson)'
  data-book-url="{{ route('bookings.store') }}"
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
          <h2 class="book-step">2. Choose a time</h2>
          <div class="book-times" id="bookTimes"></div>
          <p class="book-hint" id="bookTimeHint">Select a date to see open times.</p>

          <h2 class="book-step">3. Field for this slot</h2>
          <div class="book-field" id="bookField" aria-live="polite">
            <strong>Pick a time</strong>
            <span>Location appears when you choose a slot.</span>
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
            <div><dt>Price</dt><dd>${{ number_format((float) $coach->rate, 0) }}</dd></div>
          </dl>
          <p class="book-error" id="bookError" hidden></p>
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
  <script src="{{ asset('assets/js/book-coach.js') }}?v={{ @filemtime(public_path('assets/js/book-coach.js')) ?: time() }}"></script>
@endpush
