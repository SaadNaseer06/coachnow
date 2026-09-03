@extends('layouts.app')

@section('title', 'Request a Session | CoachNow')
@section('meta_description', 'Request a training session when coaches are not available. Coaches and players can accept or join open requests until 30 minutes before start.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/request-session.css') }}?v={{ @filemtime(public_path('assets/css/request-session.css')) ?: time() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/payment-methods.css') }}?v={{ @filemtime(public_path('assets/css/payment-methods.css')) ?: time() }}">
@endpush

@section('content')
<main class="req-page" id="requestSessionApp">
  <section id="hero"
    class="relative min-h-[420px] lg:min-h-[440px] pt-[106px] pb-[64px] flex items-center bg-zinc-950 text-white overflow-hidden"
    style="background-image: linear-gradient(90deg, rgba(12,13,14,0.88) 0%, rgba(12,13,14,0.62) 36%, rgba(12,13,14,0.16) 70%, rgba(12,13,14,0.04) 100%), linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.10) 45%, rgba(10,11,12,0.62) 100%), url('{{ asset("assets/hero-bg.png") }}'); background-size: cover; background-position: center bottom;">
    <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 w-full relative z-10">
      <div class="hero-fade-target inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] sm:text-xs font-semibold tracking-[0.08em] uppercase mb-4 shadow-[0_4px_14px_rgba(218,2,12,0.3)]" style="--hero-delay:40ms">Request Session</div>
      <h1 class="hero-fade-target max-w-[820px] text-4xl sm:text-5xl md:text-[3.1rem] lg:text-[3.4rem] font-medium tracking-[-0.01em] text-white leading-[1.04] mb-4" style="--hero-delay:110ms">No coach available?<br>Request a session.</h1>
      <p class="hero-fade-target text-[13px] sm:text-[14px] lg:text-[15px] text-zinc-200/90 max-w-[600px] leading-[1.7] font-light" style="--hero-delay:180ms">Post the session you want and nearby coaches get notified. The first coach to accept hosts it — and other players can join until 30 minutes before it starts.</p>
    </div>
  </section>

  <section class="req-body">
    <div class="req-container">
      <div class="req-grid">

        <div class="req-main">
          <div class="req-banner">
            <span class="req-banner__pill">Testing</span>
            <p>Requests are in testing. Put a <strong>card on file</strong> to submit (no charge). When a coach accepts, a <strong>$10 deposit</strong> is charged automatically. Text alerts and subscriptions launch soon.</p>
          </div>

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

          <div class="req-stage" id="reqStage">
            {{-- Step 1 --}}
            <section class="req-step is-active" data-step="1" aria-labelledby="reqStep1Title">
              <div class="req-card">
                <header class="req-head">
                  <h2 id="reqStep1Title" class="req-title">What session do you need?</h2>
                  <p class="req-lead">Start with the basics. You can leave the request open so any nearby coach can accept it.</p>
                </header>

                <form id="reqFormStep1" class="req-form" novalidate>
                  <div class="req-field">
                    <label for="reqLocation">Location</label>
                    <div class="req-input-wrap">
                      <svg class="req-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 21s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>
                      <input type="text" id="reqLocation" name="location" placeholder="Park, city, or ZIP" required autocomplete="address-level2">
                    </div>
                    <button type="button" class="req-link-btn" id="reqUseLocation">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/></svg>
                      Use my location
                    </button>
                  </div>

                  <div class="req-field-row">
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
                  </div>

                  <div class="req-field">
                    <label for="reqPreferredTime">Preferred time</label>
                    <select id="reqPreferredTime" name="preferred_time" required>
                      <option value="Anytime" selected>Anytime</option>
                      <option value="Morning">Morning (8 AM – 12 PM)</option>
                      <option value="Afternoon">Afternoon (12 PM – 7 PM)</option>
                      <option value="Evening">Evening (7 PM – 10 PM)</option>
                    </select>
                  </div>

                  <div class="req-actions">
                    <button type="submit" class="req-btn req-btn--primary">Find locations</button>
                  </div>
                </form>
              </div>
            </section>

            {{-- Step 2 --}}
            <section class="req-step" data-step="2" aria-labelledby="reqStep2Title" hidden>
              <div class="req-card">
                <header class="req-head req-head--row">
                  <button type="button" class="req-back" data-go-step="1" aria-label="Back to request details">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                  </button>
                  <div>
                    <h2 id="reqStep2Title" class="req-title req-title--sm">Choose a location</h2>
                    <p class="req-lead req-lead--sm">Coaches near this spot will be notified about your request.</p>
                  </div>
                </header>

                <div class="req-locations" id="reqLocations">
                  @foreach([
                    ['id' => 'sommers-bend', 'name' => 'Sommers Bend', 'city' => 'Murrieta, CA', 'distance' => '1.2 mi', 'coaches' => 3, 'image' => 'assets/Background.png'],
                    ['id' => 'winchester-park', 'name' => 'Winchester Sports Park', 'city' => 'Winchester, CA', 'distance' => '2.4 mi', 'coaches' => 2, 'image' => 'assets/Background (1).png'],
                    ['id' => 'bear-creek', 'name' => 'Bear Creek Park', 'city' => 'Murrieta, CA', 'distance' => '3.1 mi', 'coaches' => 1, 'image' => 'assets/hero-bg.png'],
                  ] as $loc)
                  <article class="req-loc-card" data-location-id="{{ $loc['id'] }}" data-location-name="{{ $loc['name'] }}" data-location-city="{{ $loc['city'] }}" tabindex="0" role="button" aria-label="Select {{ $loc['name'] }}">
                    <div class="req-loc-card__media" style="background-image:url('{{ asset($loc['image']) }}')">
                      <span class="req-loc-card__distance">{{ $loc['distance'] }}</span>
                    </div>
                    <div class="req-loc-card__body">
                      <div class="req-loc-card__text">
                        <h3>{{ $loc['name'] }}</h3>
                        <p>{{ $loc['city'] }}</p>
                        <p class="req-loc-card__meta">
                          <span class="req-dot"></span>
                          {{ $loc['coaches'] }} coach{{ $loc['coaches'] > 1 ? 'es' : '' }} nearby
                        </p>
                      </div>
                      <button type="button" class="req-loc-card__cta">View times <span aria-hidden="true">→</span></button>
                    </div>
                  </article>
                  @endforeach
                </div>
              </div>
            </section>

            {{-- Step 3 --}}
            <section class="req-step" data-step="3" aria-labelledby="reqStep3Title" hidden>
              <div class="req-card">
                <header class="req-head req-head--row">
                  <button type="button" class="req-back" data-go-step="2" aria-label="Back to locations">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                  </button>
                  <div>
                    <h2 id="reqStep3Title" class="req-title req-title--sm">Pick a time at <span id="reqSelectedLocationLabel">Sommers Bend</span></h2>
                    <p class="req-lead req-lead--sm">Choose the day and start time you are requesting.</p>
                  </div>
                </header>

                <div class="req-date-strip" id="reqDateStrip" role="group" aria-label="Choose date"></div>

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

            {{-- Step 4 --}}
            <section class="req-step" data-step="4" aria-labelledby="reqStep4Title" hidden>
              <div class="req-card">
                <header class="req-head req-head--row">
                  <button type="button" class="req-back" data-go-step="3" aria-label="Back to time selection">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
                  </button>
                  <div>
                    <h2 id="reqStep4Title" class="req-title req-title--sm">Session details</h2>
                    <p class="req-lead req-lead--sm">Group requests stay open so other players can join.</p>
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
                    <label for="reqPriceRange">Budget per player</label>
                    <p class="req-help">This is what you’re willing to pay <strong>per player</strong> — not a total for the whole group.</p>
                    <select id="reqPriceRange" name="price_range" required>
                      <option value="" disabled selected>Select budget per player</option>
                      <option value="Up to $25 / player">Up to $25 / player</option>
                      <option value="$25 – $50 / player">$25 – $50 / player</option>
                      <option value="$50 – $100 / player">$50 – $100 / player</option>
                      <option value="$100 – $150 / player">$100 – $150 / player</option>
                      <option value="$150+ / player">$150+ / player</option>
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
                    <label for="reqKnowBy">Need to know by</label>
                    <p class="req-help">Tell coaches when you need an answer — they can accept anytime until this cutoff.</p>
                    <div class="req-chips" id="reqKnowByPresets" role="group" aria-label="Quick cutoff options">
                      <button type="button" class="req-chip" data-know-by="2h">In 2 hours</button>
                      <button type="button" class="req-chip" data-know-by="tonight">Tonight 8 PM</button>
                      <button type="button" class="req-chip" data-know-by="tomorrow">Tomorrow 10 AM</button>
                    </div>
                    <div class="req-input-wrap">
                      <svg class="req-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                      <input type="datetime-local" id="reqKnowBy" name="know_by" required>
                    </div>
                  </div>

                  <div class="req-field-row">
                    <div class="req-field">
                      <label for="reqMinPlayers">Min players <span class="req-optional">(optional)</span></label>
                      <input type="number" id="reqMinPlayers" name="min_players" min="1" max="30" inputmode="numeric" placeholder="e.g. 4">
                    </div>
                    <div class="req-field">
                      <label for="reqMaxPlayers">Max players <span class="req-optional">(optional)</span></label>
                      <input type="number" id="reqMaxPlayers" name="max_players" min="1" max="30" inputmode="numeric" placeholder="e.g. 8">
                    </div>
                  </div>

                  <div class="req-field">
                    <label for="reqPlayerLevel">Player level <span class="req-optional">(optional)</span></label>
                    <select id="reqPlayerLevel" name="player_level">
                      <option value="" selected>No preference</option>
                      <option value="Beginner">Beginner</option>
                      <option value="Intermediate">Intermediate</option>
                      <option value="Advanced">Advanced</option>
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
                      <span>Next you’ll put a card on file. Nothing is charged until a coach accepts. Then a <strong>$10 deposit</strong> is taken automatically.</span>
                    </p>
                  </div>
                </form>
              </div>
            </section>

            {{-- Step 5: live request --}}
            <section class="req-step" data-step="5" aria-labelledby="reqSuccessTitle" hidden>
              <div class="req-card req-card--center">
                <div class="req-success__icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
                </div>
                <h2 id="reqSuccessTitle" class="req-title">Your request is live</h2>
                <p class="req-lead req-lead--center">Nearby coaches have been notified. You will get a text as soon as someone accepts.</p>

                <div class="req-live-card">
                  <div class="req-live-card__head">
                    <span class="req-live-card__status"><span class="req-pulse"></span> Open</span>
                    <span class="req-live-card__id" id="reqRequestId">#CN-0000</span>
                  </div>

                  <div class="req-countdown" id="reqAcceptCountdown" aria-live="polite">
                    <p class="req-countdown__label">Need to know by</p>
                    <p class="req-countdown__time" id="reqCountdownDisplay">—</p>
                    <p class="req-countdown__hint" id="reqCountdownHint">First coach to accept hosts this session</p>
                  </div>

                  <dl class="req-live-summary" id="reqLiveSummary"></dl>

                  <div class="req-deposit req-deposit--waiting" id="reqWaitingCoach">
                    <div class="req-deposit__badge req-deposit__badge--wait">Card on file</div>
                    <h3>No charge yet</h3>
                    <p>Your card is saved. When a coach accepts, a <strong>$10 deposit</strong> is charged automatically — same idea as Uber. Nothing is charged if nobody accepts.</p>
                    <p class="req-deposit__note" id="reqCardOnFileNote">Keep this tab open, then Accept &amp; host on the coach dashboard to see the $10 charge.</p>
                  </div>

                  <div class="req-deposit req-deposit--paid" id="reqDepositPaid" hidden>
                    <h3>You’re confirmed</h3>
                    <p>A coach accepted and your <strong>$10 deposit</strong> was charged to the card on file. The session stays open so others can join until 30 minutes before start.</p>
                    <p class="req-deposit__note" id="reqPaidWith"></p>
                    <button type="button" class="req-btn req-btn--ghost" id="reqJoinAsAnother" style="margin-top:0.75rem;width:100%">Demo: join as another player</button>
                  </div>

                  <div class="req-deposit" id="reqJoinDepositPanel" hidden>
                    <div class="req-deposit__badge">Join session</div>
                    <h3>Join with a $10 deposit</h3>
                    <p>Players joining this open session confirm with a $10 deposit. The coach can see who’s paid.</p>
                    @include('partials.payment-methods', ['payPrefix' => 'reqJoinPay'])
                    <button type="button" class="req-btn req-btn--primary" id="reqJoinPayBtn">Pay $10 &amp; join</button>
                    <button type="button" class="req-btn req-btn--ghost" id="reqJoinCancelBtn">Cancel</button>
                    <p class="req-deposit__note">Testing only — no real charge.</p>
                  </div>

                  <div class="req-live-card__footer">
                    <p>Other players can join until <strong>30 minutes before</strong> the session starts.</p>
                  </div>
                </div>

                <div class="req-actions req-actions--stack">
                  <a href="{{ route('player-dashboard') }}" class="req-btn req-btn--primary">Go to player dashboard</a>
                  <button type="button" class="req-btn req-btn--ghost" id="reqStartAnother">Request another session</button>
                </div>
              </div>
            </section>
          </div>
        </div>

        <aside class="req-aside">
          <div class="req-aside-card">
            <h3 class="req-aside-title">How it works</h3>
            <ol class="req-steps-list">
              <li>
                <span class="req-steps-list__num">1</span>
                <div>
                  <strong>You post a request</strong>
                  <p>Choose location, time, and put a card on file. No charge yet.</p>
                </div>
              </li>
              <li>
                <span class="req-steps-list__num">2</span>
                <div>
                  <strong>A coach accepts</strong>
                  <p>Your $10 deposit is charged automatically. The coach just hosts.</p>
                </div>
              </li>
              <li>
                <span class="req-steps-list__num">3</span>
                <div>
                  <strong>Others can join</strong>
                  <p>Joiners pay a $10 deposit too. Open until 30 minutes before start.</p>
                </div>
              </li>
            </ol>
          </div>

          <div class="req-aside-card req-aside-card--dark">
            <h3 class="req-aside-title req-aside-title--light">Why request a session?</h3>
            <ul class="req-benefits">
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                No open slots on a coach calendar? Create your own.
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                Split the cost when other players join your group session.
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                Card on file at request. $10 charges only after a coach accepts.
              </li>
            </ul>
          </div>

          <div class="req-aside-note">
            <p><strong>Coming soon:</strong> SMS alerts, live payments, and subscription plans. The $10 deposit is a prototype to stop fake requests.</p>
          </div>
        </aside>

      </div>
    </div>
  </section>

  <div class="req-pay-modal" id="reqCardModal" hidden data-lenis-prevent>
    <div class="req-pay-modal__backdrop" data-close-card-modal></div>
    <div class="req-pay-modal__panel" role="dialog" aria-modal="true" aria-labelledby="reqCardModalTitle">
      <div class="req-pay-modal__header">
        <button type="button" class="req-pay-modal__close" data-close-card-modal aria-label="Close">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
        <h2 id="reqCardModalTitle">Confirm with a card</h2>
        <p class="req-pay-modal__lead">We’ll save this card to post your request. No charge now — a <strong>$10 deposit</strong> only if a coach accepts.</p>
      </div>
      <div class="req-pay-modal__body" id="reqCardOnFileBox" data-lenis-prevent>
        @include('partials.payment-methods', ['payPrefix' => 'reqFile'])
      </div>
      <div class="req-pay-modal__footer">
        <button type="button" class="req-btn req-btn--primary" id="reqCardConfirmBtn">Post request</button>
        <button type="button" class="req-btn req-btn--ghost" data-close-card-modal>Cancel</button>
      </div>
    </div>
  </div>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
  <script src="{{ asset('assets/js/payment-methods.js') }}?v={{ @filemtime(public_path('assets/js/payment-methods.js')) ?: time() }}"></script>
  <script src="{{ asset('assets/js/request-session.js') }}?v={{ @filemtime(public_path('assets/js/request-session.js')) ?: time() }}"></script>
@endpush
