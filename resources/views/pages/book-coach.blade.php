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
  data-book-multi-url="{{ route('bookings.store-multiple') }}"
  data-slots-url="{{ route('coaches.slots', $coach->publicToken()) }}"
  data-horizon="{{ now()->addDays((int) config('coachnow.booking.horizon_days', 180))->toDateString() }}"
  data-max-sessions="{{ (int) config('coachnow.booking.max_sessions_per_checkout', 24) }}"
  data-services='@json($services ?? [])'
  data-initial-service="{{ $initialService ?? '' }}"
  data-join-url="{{ url('/api/group-sessions') }}"
  data-dashboard-url="{{ route('player-dashboard') }}"
>
  <section class="book-hero">
    <div class="book-wrap">
      <p class="book-kicker">Direct booking</p>
      <h1>Book {{ $coach->display_name }}</h1>
      <p class="book-lead">Pick one or more open times — or repeat weekly — and confirm them all in one checkout.</p>
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
        @php
          $serviceHints = [
              'private' => 'Book open times — one, several dates, or weekly.',
              'group' => 'Join an open group, or book a private group for your players.',
              'camp' => 'Join an upcoming camp or clinic.',
              'team' => 'Arranged with the coach — send a team training request.',
          ];
        @endphp
        <h2>1. Choose the type of training</h2>
        <div class="book-services" id="bookServices" role="radiogroup" aria-label="Type of training">
          @foreach ($services ?? [] as $service)
            <button type="button" class="book-service" data-service="{{ $service['key'] }}" role="radio" aria-checked="false">
              <strong>{{ $service['label'] }}</strong>
              <span>{{ $serviceHints[$service['kind']] ?? $serviceHints['private'] }}</span>
            </button>
          @endforeach
        </div>

        <div class="book-team" id="bookTeamPanel" hidden>
          <strong>Team training is arranged with {{ $coach->display_name }}</strong>
          <p>Tell the coach your team size, ages and preferred times — they’ll confirm the details with you.</p>
          <a class="book-btn book-btn--small" href="{{ route('request-session', ['coach' => $coach->publicToken(), 'session' => 'Team Training — Group Session']) }}">Request team training</a>
        </div>

        <div id="bookAfterService" hidden>
        <h2 class="book-step">2. Choose a date</h2>
        {{-- Month calendar: only days with the coach's open times can be picked. --}}
        <div class="book-cal" id="bookCalendar">
          <div class="book-cal__head">
            <button type="button" class="book-cal__nav" id="bookCalPrev" aria-label="Previous month">&lsaquo;</button>
            <strong id="bookCalMonth">—</strong>
            <button type="button" class="book-cal__nav" id="bookCalNext" aria-label="Next month">&rsaquo;</button>
          </div>
          <div class="book-cal__dow"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
          <div class="book-cal__grid" id="bookCalGrid" role="grid" aria-label="Available dates"></div>
          <p class="book-hint" id="bookCalStatus" aria-live="polite"></p>
        </div>
        <p class="book-empty" id="bookNoDates" hidden>No open times this month. Try the next month, or <a href="{{ route('request-session', ['coach' => $coach->publicToken()]) }}">request a session</a>.</p>

        <div id="bookSlotSteps">
          <div class="book-legend" aria-label="Slot color guide">
            <span class="book-legend__item"><i class="book-legend__swatch is-open"></i> Open time</span>
            <span class="book-legend__item"><i class="book-legend__swatch is-group"></i> Open group session</span>
            <span class="book-legend__item"><i class="book-legend__swatch is-full"></i> Full</span>
          </div>

          <h2 class="book-step">3. Choose a time</h2>
          <p class="book-hint" id="bookMultiHint">Tip: pick times on several dates to book them together.</p>
          <div class="book-times" id="bookTimes"></div>
          <p class="book-hint" id="bookTimeHint">Select a date to see open times.</p>

          {{-- Weekly repeat: only weeks where the coach is free get added. --}}
          <div class="book-repeat" id="bookRepeat" hidden>
            <strong>Repeat weekly</strong>
            <p class="book-hint" id="bookRepeatLabel">Same time each week.</p>
            <div class="book-repeat__days" id="bookRepeatDays" role="group" aria-label="Days of the week">
              @foreach (['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 0] as $label => $dow)
                <label class="book-repeat__day"><input type="checkbox" value="{{ $dow }}"><span>{{ $label }}</span></label>
              @endforeach
            </div>
            <div class="book-repeat__row">
              <label for="bookRepeatWeeks">For</label>
              <select id="bookRepeatWeeks">
                <option value="4">4 weeks</option>
                <option value="8">8 weeks</option>
                <option value="12">12 weeks</option>
              </select>
              <button type="button" class="book-btn book-btn--small" id="bookRepeatAdd">Add weekly sessions</button>
            </div>
            <p class="book-hint" id="bookRepeatResult" aria-live="polite"></p>
          </div>

          <h2 class="book-step">4. Session details</h2>
          <div class="book-field" id="bookField" aria-live="polite">
            <strong>Pick a time</strong>
            <span>Location and group details appear when you choose a slot.</span>
          </div>
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
            <div id="sumSessionsRow" class="book-sessions" hidden><dt>Sessions</dt><dd><ul id="sumSessions"></ul></dd></div>
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
