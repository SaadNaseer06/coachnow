@extends('layouts.app')

@section('title', 'Request a Session | CoachNow')
@section('meta_description', 'Request a training session when coaches are not available. Coaches and players can accept or join open requests until 30 minutes before start.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/request-session.css') }}">
@endpush

@section('content')
<main class="req-session" id="requestSessionApp">
  {{-- Testing / future subscription banner --}}
  <div class="req-banner">
    <span class="req-banner__pill">Testing</span>
    <p>Request sessions are free during testing. SMS alerts, coach accept timers, and subscriptions launch soon.</p>
  </div>

  {{-- Progress --}}
  <div class="req-progress" aria-hidden="true">
    <div class="req-progress__track">
      <div class="req-progress__fill" id="reqProgressFill"></div>
    </div>
    <ol class="req-progress__steps" id="reqProgressSteps">
      <li class="is-active" data-step="1">Request</li>
      <li data-step="2">Location</li>
      <li data-step="3">Time</li>
      <li data-step="4">Details</li>
    </ol>
  </div>

  {{-- Step 1: Request form --}}
  <section class="req-step is-active" data-step="1" aria-labelledby="reqStep1Title">
    <div class="req-shell">
      <header class="req-head">
        <h1 id="reqStep1Title" class="req-title">Request a Session</h1>
        <p class="req-lead">Tell us what you are looking for and we will match you with available coaches — or leave the request open for others to accept.</p>
      </header>

      <form id="reqFormStep1" class="req-form" novalidate>
        <div class="req-field">
          <label for="reqLocation">Location</label>
          <div class="req-input-wrap">
            <svg class="req-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
            <input type="text" id="reqLocation" name="location" placeholder="Enter location or use my location" required autocomplete="address-level2">
          </div>
          <button type="button" class="req-link-btn" id="reqUseLocation">Use my location</button>
        </div>

        <div class="req-field">
          <label for="reqSport">Sport</label>
          <select id="reqSport" name="sport" required>
            <option value="Soccer" selected>Soccer</option>
            <option value="Basketball">Basketball</option>
            <option value="Baseball">Baseball</option>
            <option value="Softball">Softball</option>
            <option value="Tennis">Tennis</option>
          </select>
        </div>

        <div class="req-field">
          <label for="reqDate">Date</label>
          <div class="req-input-wrap">
            <svg class="req-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <input type="date" id="reqDate" name="date" required>
          </div>
        </div>

        <div class="req-field">
          <label for="reqPreferredTime">Preferred time</label>
          <select id="reqPreferredTime" name="preferred_time" required>
            <option value="Anytime" selected>Anytime</option>
            <option value="Morning">Morning (8 AM – 12 PM)</option>
            <option value="Afternoon">Afternoon (12 PM – 5 PM)</option>
            <option value="Evening">Evening (5 PM – 9 PM)</option>
          </select>
        </div>

        <div class="req-actions">
          <button type="submit" class="req-btn req-btn--primary">Find locations</button>
        </div>
      </form>
    </div>
  </section>

  {{-- Step 2: Choose location --}}
  <section class="req-step" data-step="2" aria-labelledby="reqStep2Title" hidden>
    <div class="req-shell">
      <header class="req-head req-head--row">
        <button type="button" class="req-back" data-go-step="1" aria-label="Back to request form">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div>
          <h2 id="reqStep2Title" class="req-title req-title--sm">Choose location</h2>
          <p class="req-lead req-lead--sm">Pick where you want to train. Nearby coaches will be notified.</p>
        </div>
      </header>

      <div class="req-locations" id="reqLocations">
        @foreach([
          ['id' => 'sommers-bend', 'name' => 'Sommers Bend', 'city' => 'Murrieta, CA', 'distance' => '1.2 mi', 'coaches' => 3, 'image' => 'assets/Background.png'],
          ['id' => 'winchester-park', 'name' => 'Winchester Sports Park', 'city' => 'Winchester, CA', 'distance' => '2.4 mi', 'coaches' => 2, 'image' => 'assets/Background (1).png'],
          ['id' => 'bear-creek', 'name' => 'Bear Creek Park', 'city' => 'Murrieta, CA', 'distance' => '3.1 mi', 'coaches' => 1, 'image' => 'assets/hero-bg.png'],
        ] as $loc)
        <article class="req-loc-card" data-location-id="{{ $loc['id'] }}" data-location-name="{{ $loc['name'] }}" data-location-city="{{ $loc['city'] }}">
          <div class="req-loc-card__media" style="background-image:url('{{ asset($loc['image']) }}')">
            <span class="req-loc-card__distance">{{ $loc['distance'] }}</span>
          </div>
          <div class="req-loc-card__body">
            <h3>{{ $loc['name'] }}</h3>
            <p>{{ $loc['city'] }}</p>
            <p class="req-loc-card__meta">{{ $loc['coaches'] }} coach{{ $loc['coaches'] > 1 ? 'es' : '' }} may be available</p>
            <button type="button" class="req-loc-card__cta">View times <span aria-hidden="true">→</span></button>
          </div>
        </article>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Step 3: Pick a time --}}
  <section class="req-step" data-step="3" aria-labelledby="reqStep3Title" hidden>
    <div class="req-shell">
      <header class="req-head req-head--row">
        <button type="button" class="req-back" data-go-step="2" aria-label="Back to locations">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div>
          <h2 id="reqStep3Title" class="req-title req-title--sm"><span id="reqSelectedLocationLabel">Sommers Bend</span></h2>
          <p class="req-lead req-lead--sm">Select a date and time for your session.</p>
        </div>
      </header>

      <div class="req-date-strip" id="reqDateStrip" role="tablist" aria-label="Choose date"></div>

      <div class="req-time-groups" id="reqTimeGroups">
        <div class="req-time-group" data-period="Morning">
          <h3>Morning</h3>
          <div class="req-time-slots" id="reqMorningSlots"></div>
        </div>
        <div class="req-time-group" data-period="Afternoon">
          <h3>Afternoon</h3>
          <div class="req-time-slots" id="reqAfternoonSlots"></div>
        </div>
        <div class="req-time-group" data-period="Evening">
          <h3>Evening</h3>
          <div class="req-time-slots" id="reqEveningSlots"></div>
        </div>
      </div>

      <p class="req-note">All times shown in your local time zone.</p>

      <div class="req-actions">
        <button type="button" class="req-btn req-btn--primary" id="reqToDetailsBtn" disabled>Next: session details</button>
      </div>
    </div>
  </section>

  {{-- Step 4: Session details --}}
  <section class="req-step" data-step="4" aria-labelledby="reqStep4Title" hidden>
    <div class="req-shell">
      <header class="req-head req-head--row">
        <button type="button" class="req-back" data-go-step="3" aria-label="Back to time selection">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <div>
          <h2 id="reqStep4Title" class="req-title req-title--sm">Tell us about your session</h2>
          <p class="req-lead req-lead--sm">Group sessions stay open so other players can join until 30 minutes before start.</p>
        </div>
      </header>

      <form id="reqFormStep4" class="req-form" novalidate>
        <div class="req-field">
          <label for="reqAgeRange">Age range</label>
          <select id="reqAgeRange" name="age_range" required>
            <option value="" disabled selected>Select age range</option>
            <option value="U8 (7–8 years)">U8 (7–8 years)</option>
            <option value="U10 (9–10 years)">U10 (9–10 years)</option>
            <option value="U12 (11–12 years)">U12 (11–12 years)</option>
            <option value="U14 (13–14 years)">U14 (13–14 years)</option>
            <option value="U16 (15–16 years)">U16 (15–16 years)</option>
            <option value="U18 (17–18 years)">U18 (17–18 years)</option>
            <option value="Adult (18+)">Adult (18+)</option>
          </select>
        </div>

        <div class="req-field">
          <label for="reqPriceRange">Price range (per player)</label>
          <select id="reqPriceRange" name="price_range" required>
            <option value="" disabled selected>Select price range</option>
            <option value="Any">Any</option>
            <option value="$25 – $50">$25 – $50</option>
            <option value="$50 – $100">$50 – $100</option>
            <option value="$100 – $150">$100 – $150</option>
            <option value="$150+">$150+</option>
          </select>
        </div>

        <div class="req-field">
          <label for="reqSessionType">Session type</label>
          <select id="reqSessionType" name="session_type" required>
            <option value="" disabled selected>Select session type</option>
            <option value="Speed Agility Quickness (SAQ) — Group Session">Speed Agility Quickness (SAQ) — Group Session</option>
            <option value="Skills Training — Group Session">Skills Training — Group Session</option>
            <option value="Technical Development — Group Session">Technical Development — Group Session</option>
            <option value="Small-Sided Games — Group Session">Small-Sided Games — Group Session</option>
            <option value="Goalkeeper Training — Group Session">Goalkeeper Training — Group Session</option>
            <option value="Private 1-on-1 Session">Private 1-on-1 Session</option>
          </select>
        </div>

        <div class="req-field">
          <label for="reqNotes">Additional details <span class="req-optional">(optional)</span></label>
          <textarea id="reqNotes" name="notes" rows="3" placeholder="Any specific goals or requests?"></textarea>
        </div>

        <div class="req-summary" id="reqSummary" aria-live="polite"></div>

        <div class="req-actions req-actions--stack">
          <button type="submit" class="req-btn req-btn--primary">Submit request</button>
          <p class="req-disclaimer">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            Coaches receive a text alert and have <strong>15 minutes</strong> to accept. Players are notified when a coach accepts. The request stays open for others to join until <strong>30 minutes before</strong> the session starts.
          </p>
        </div>
      </form>
    </div>
  </section>

  {{-- Success / live request state --}}
  <section class="req-step" data-step="5" aria-labelledby="reqSuccessTitle" hidden>
    <div class="req-shell req-shell--narrow">
      <div class="req-success">
        <div class="req-success__icon" aria-hidden="true">✓</div>
        <h2 id="reqSuccessTitle" class="req-title">Request submitted</h2>
        <p class="req-lead">Your session request is live. Nearby coaches and players can respond via SMS (coming soon in testing).</p>

        <div class="req-live-card">
          <div class="req-live-card__head">
            <span class="req-live-card__status">Open</span>
            <span class="req-live-card__id" id="reqRequestId">#CN-0000</span>
          </div>

          <div class="req-countdown" id="reqAcceptCountdown" aria-live="polite">
            <p class="req-countdown__label">Coach accept window</p>
            <p class="req-countdown__time" id="reqCountdownDisplay">15:00</p>
            <p class="req-countdown__hint">First coach to accept hosts the session</p>
          </div>

          <dl class="req-live-summary" id="reqLiveSummary"></dl>

          <div class="req-live-card__footer">
            <p>Others can join this request until <strong>30 minutes before</strong> the session starts.</p>
          </div>
        </div>

        <div class="req-actions req-actions--stack">
          <a href="{{ route('player-dashboard') }}" class="req-btn req-btn--primary">View player dashboard</a>
          <button type="button" class="req-btn req-btn--ghost" id="reqStartAnother">Request another session</button>
        </div>
      </div>
    </div>
  </section>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/request-session.js') }}"></script>
@endpush
