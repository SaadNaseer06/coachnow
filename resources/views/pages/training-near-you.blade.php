@extends('layouts.app')

@section('title', 'Training Near You | CoachNow')
@section('meta_description', 'Discover open group sessions and public session requests near you — filter by distance, sport, and age.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}?v={{ @filemtime(public_path('assets/css/find-a-coach.css')) ?: time() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/training-near-you.css') }}?v={{ @filemtime(public_path('assets/css/training-near-you.css')) ?: time() }}">
@endpush

@section('content')
<main>

  <section id="hero"
    class="relative min-h-[520px] lg:min-h-[560px] pt-[106px] pb-[64px] flex items-center bg-zinc-950 text-white overflow-hidden"
    style="
      background-image:
        linear-gradient(90deg, rgba(12,13,14,0.82) 0%, rgba(12,13,14,0.58) 34%, rgba(12,13,14,0.12) 68%, rgba(12,13,14,0.02) 100%),
        linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.08) 43%, rgba(10,11,12,0.58) 100%),
        url('{{ asset("assets/hero-bg.png") }}');
      background-size: cover;
      background-position: center bottom;
    ">
    <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10">
      <div
        class="inline-flex items-center px-4 py-2 xl:px-5 xl:py-2.5 rounded-full bg-brand-red text-white text-xs sm:text-sm xl:text-[0.95rem] font-medium tracking-[0.01em] uppercase mb-4 xl:mb-5 shadow-[0_4px_14px_rgba(218,2,12,0.3)]">
        FIND. BOOK. PLAY.
      </div>

      <h1 class="max-w-[900px] text-4xl sm:text-5xl md:text-[3.35rem] lg:text-[3.65rem] xl:text-[3.9rem] font-medium tracking-[0.01em] text-white leading-none mb-4 xl:mb-5">
        Training Near You
      </h1>

      <p class="text-[12px] sm:text-[12.5px] md:text-[13px] lg:text-[14px] xl:text-[15px] text-zinc-200/90 max-w-[650px] xl:max-w-[720px] tracking-[0.002em] leading-[1.55] mb-[36px] xl:mb-[40px] font-light">
        Browse open group sessions coaches have shared publicly, plus open session requests from other families. Filter by distance, sport, and age — Book Now when a spot fits.
      </p>

      <form id="tnyFilters" method="GET" action="{{ route('training-near-you') }}" class="w-full max-w-[1100px]">
        <input type="hidden" name="lat" id="tnyLat" value="{{ $filters['lat'] ?? '' }}">
        <input type="hidden" name="lng" id="tnyLng" value="{{ $filters['lng'] ?? '' }}">

        <div class="tny-search-bar">
          <div class="tny-search-bar__field tny-search-bar__field--grow">
            <label for="tnyLocation">Location</label>
            <div class="tny-search-bar__control">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <input
                type="text"
                id="tnyLocation"
                name="location"
                value="{{ $locationQuery }}"
                placeholder="City, park, or ZIP"
                autocomplete="off"
              >
              @php
                $hasLocationFilter = filled($filters['lat'] ?? null)
                  || filled($filters['lng'] ?? null)
                  || filled($locationQuery);
              @endphp
              <button
                type="button"
                class="tny-search-bar__clear"
                id="tnyClearLocation"
                title="Clear location — show all areas"
                aria-label="Clear location filter"
                @unless ($hasLocationFilter) hidden @endunless
              >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path d="M18 6 6 18M6 6l12 12"></path>
                </svg>
              </button>
              <button type="button" class="tny-search-bar__locate" id="tnyNearMe" title="Use my location" aria-label="Use my location">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <circle cx="12" cy="12" r="3"></circle>
                  <path d="M12 2v3M12 19v3M2 12h3M19 12h3"></path>
                </svg>
              </button>
            </div>
          </div>

          <div class="tny-search-bar__divider" aria-hidden="true"></div>

          <div class="tny-search-bar__field">
            <label for="tnyRadius">Radius</label>
            <div class="tny-search-bar__control">
              <select id="tnyRadius" name="radius">
                @foreach ([5, 10, 20] as $miles)
                  <option value="{{ $miles }}" @selected((int) $radius === $miles)>{{ $miles }} miles</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="tny-search-bar__divider" aria-hidden="true"></div>

          <div class="tny-search-bar__field">
            <label for="tnySport">Sport</label>
            <div class="tny-search-bar__control">
              <select id="tnySport" name="sport">
                <option value="">All sports</option>
                @foreach ($sports as $sportOption)
                  <option value="{{ $sportOption }}" @selected(strcasecmp((string) $sport, (string) $sportOption) === 0)>{{ $sportOption }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="tny-search-bar__divider" aria-hidden="true"></div>

          <div class="tny-search-bar__field">
            <label for="tnyAge">Age</label>
            <div class="tny-search-bar__control">
              <select id="tnyAge" name="age">
                <option value="">All ages</option>
                @foreach ($ageOptions as $ageOption)
                  <option value="{{ $ageOption }}" @selected($age === $ageOption)>{{ $ageOption }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <button type="submit" class="tny-search-bar__submit">Search</button>
        </div>

        <p class="mt-3 text-[12px] sm:text-[13px] text-zinc-300/85 font-light" id="tnyStatus">
          @if ($geocodeFailed)
            Couldn’t find that location — try a city or ZIP, or use your current location.
          @elseif ($searchOrigin)
            Showing opportunities within {{ $radius }} miles of {{ $searchOrigin['label'] ?? 'your search' }} · next 7 days
            <button type="button" class="tny-reset-link" id="tnyClearLocationStatus" data-tny-clear-location>
              Clear location · show all areas
            </button>
          @else
            Showing the next 7 days. Add a location or use the locate icon to sort by distance.
          @endif
        </p>
      </form>
    </div>
  </section>

  <section class="pt-10 pb-12 lg:pt-12 lg:pb-14 bg-[#0C0D0E] text-white">
    <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">

      <div class="mb-8 lg:mb-10">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
          <div>
            <h2 class="text-[1.5rem] sm:text-[1.75rem] lg:text-[1.95rem] font-medium tracking-[-0.02em] text-white leading-tight">Open Group Sessions</h2>
            <p class="mt-1.5 text-[13px] text-zinc-400 font-light">Coaches opened these leftover spots to the public</p>
          </div>
          <p class="text-[12px] text-zinc-500 shrink-0">{{ count($openGroups) }} listed</p>
        </div>

        @if (count($openGroups))
          <div class="tny-grid">
            @foreach ($openGroups as $item)
              <article class="tny-card is-group">
                <div class="tny-card__top">
                  <div>
                    <span class="tny-badge tny-badge--group">Open group</span>
                    @if (!empty($item['full']))
                      <span class="tny-badge tny-badge--full">Full</span>
                    @endif
                    <h3>{{ $item['title'] }}</h3>
                  </div>
                  @if ($item['distance_miles'] !== null)
                    <span class="tny-card__dist">{{ number_format((float) $item['distance_miles'], 1) }} mi</span>
                  @endif
                </div>
                <div class="tny-card__meta">
                  <span><strong>Coach:</strong> {{ $item['coach_name'] }}</span>
                  @if (!empty($item['sport']))
                    <span><strong>Sport:</strong> {{ $item['sport'] }}</span>
                  @endif
                  <span><strong>When:</strong> {{ $item['date_label'] }} · {{ $item['time_label'] }}</span>
                  <span><strong>Where:</strong> {{ $item['location_name'] }}{{ !empty($item['location_area']) ? ' · '.$item['location_area'] : '' }}</span>
                  @if (!empty($item['age_range']))
                    <span><strong>Ages:</strong> {{ $item['age_range'] }}</span>
                  @endif
                  <span><strong>Price:</strong> {{ $item['price_label'] }}</span>
                  <span><strong>Spots:</strong> {{ $item['capacity_label'] }}{{ isset($item['spots_remaining']) && $item['spots_remaining'] !== null ? ' · '.$item['spots_remaining'].' left' : '' }}</span>
                </div>
                <div class="tny-card__actions">
                  @if (!empty($item['joinable']) && !empty($item['book_url']))
                    <a class="tny-btn tny-btn--primary" href="{{ auth()->check() ? $item['book_url'] : route('login').'?redirect='.urlencode($item['book_url']) }}">Book Now</a>
                  @elseif (!empty($item['share_url']))
                    <a class="tny-btn tny-btn--primary" href="{{ $item['share_url'] }}">View session</a>
                  @endif
                  @if (!empty($item['coach_token']))
                    <a class="tny-btn tny-btn--ghost" href="{{ route('coach-profile', $item['coach_token']) }}">Coach profile</a>
                  @endif
                </div>
              </article>
            @endforeach
          </div>
        @else
          <div class="tny-empty">
            No open group sessions
            @if ($searchOrigin) within {{ $radius }} miles @endif
            in the next 7 days.
            @if ($searchOrigin && $radius < 20) Try a wider radius. @endif
            @if ($searchOrigin)
              <button type="button" class="tny-reset-link tny-reset-link--block" data-tny-clear-location>
                Clear location · show all areas
              </button>
            @endif
          </div>
        @endif
      </div>

      <div>
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5">
          <div>
            <h2 class="text-[1.5rem] sm:text-[1.75rem] lg:text-[1.95rem] font-medium tracking-[-0.02em] text-white leading-tight">Open Session Requests</h2>
            <p class="mt-1.5 text-[13px] text-zinc-400 font-light">Public marketplace requests — invites to specific coaches stay private</p>
          </div>
          <p class="text-[12px] text-zinc-500 shrink-0">{{ count($openRequests) }} listed</p>
        </div>

        @if (count($openRequests))
          <div class="tny-grid">
            @foreach ($openRequests as $item)
              <article class="tny-card">
                <div class="tny-card__top">
                  <div>
                    <span class="tny-badge tny-badge--request">Open request</span>
                    @if (!empty($item['status_label']))
                      <span class="tny-badge">{{ $item['status_label'] }}</span>
                    @endif
                    <h3>{{ $item['title'] }}</h3>
                  </div>
                  @if ($item['distance_miles'] !== null)
                    <span class="tny-card__dist">{{ number_format((float) $item['distance_miles'], 1) }} mi</span>
                  @endif
                </div>
                <div class="tny-card__meta">
                  @if (!empty($item['sport']))
                    <span><strong>Sport:</strong> {{ $item['sport'] }}</span>
                  @endif
                  <span><strong>When:</strong> {{ $item['date_label'] }} · {{ $item['time_label'] }}</span>
                  <span><strong>Where:</strong> {{ $item['location_name'] }}{{ !empty($item['location_area']) ? ' · '.$item['location_area'] : '' }}</span>
                  @if (!empty($item['age_range']))
                    <span><strong>Ages:</strong> {{ $item['age_range'] }}</span>
                  @endif
                  <span><strong>Budget:</strong> {{ $item['price_label'] }}</span>
                  <span><strong>Players:</strong> {{ $item['capacity_label'] }}</span>
                </div>
                <div class="tny-card__actions">
                  <a class="tny-btn tny-btn--primary" href="{{ $item['view_url'] ?? route('request-session') }}">{{ $item['view_label'] ?? 'View request' }}</a>
                  @if (! auth()->check() || (auth()->user() && auth()->user()->isAthlete()))
                    <a class="tny-btn tny-btn--ghost" href="{{ route('request-session') }}">Request your own</a>
                  @endif
                </div>
              </article>
            @endforeach
          </div>
        @else
          <div class="tny-empty">
            No public session requests nearby right now.
            <a href="{{ route('request-session') }}">Post a Request a Session</a> to get on the board.
            @if ($searchOrigin)
              <button type="button" class="tny-reset-link tny-reset-link--block" data-tny-clear-location>
                Clear location · show all areas
              </button>
            @endif
          </div>
        @endif
      </div>

    </div>
  </section>
</main>
@endsection

@push('scripts')
<script>
(() => {
  const form = document.getElementById('tnyFilters');
  const nearBtn = document.getElementById('tnyNearMe');
  const clearBtn = document.getElementById('tnyClearLocation');
  const latEl = document.getElementById('tnyLat');
  const lngEl = document.getElementById('tnyLng');
  const locationEl = document.getElementById('tnyLocation');
  const statusEl = document.getElementById('tnyStatus');
  const STORE_KEY = 'coachnow.tny.origin';
  const SKIP_AUTO_KEY = 'coachnow.tny.skipAutoLocate';
  const MAX_AGE_MS = 12 * 60 * 60 * 1000;

  const readSaved = () => {
    try {
      const saved = JSON.parse(localStorage.getItem(STORE_KEY) || 'null');
      if (saved && Number.isFinite(saved.lat) && Number.isFinite(saved.lng) && Date.now() - saved.ts < MAX_AGE_MS) {
        return saved;
      }
    } catch {}
    return null;
  };

  const save = (lat, lng) => {
    try {
      localStorage.setItem(STORE_KEY, JSON.stringify({ lat, lng, ts: Date.now() }));
      localStorage.removeItem(SKIP_AUTO_KEY);
    } catch {}
  };

  const syncClearVisibility = () => {
    if (!clearBtn) return;
    const active = Boolean(
      (latEl?.value || '').trim()
      || (lngEl?.value || '').trim()
      || (locationEl?.value || '').trim()
    );
    clearBtn.hidden = !active;
  };

  const clearLocationFilter = () => {
    try {
      localStorage.removeItem(STORE_KEY);
      localStorage.setItem(SKIP_AUTO_KEY, '1');
    } catch {}
    if (latEl) latEl.value = '';
    if (lngEl) lngEl.value = '';
    if (locationEl) locationEl.value = '';
    syncClearVisibility();
    // Drop lat/lng/location so the page shows all areas (keep sport/age/radius).
    const url = new URL(form?.action || window.location.href, window.location.origin);
    const radius = document.getElementById('tnyRadius')?.value;
    const sport = document.getElementById('tnySport')?.value;
    const age = document.getElementById('tnyAge')?.value;
    if (radius) url.searchParams.set('radius', radius);
    if (sport) url.searchParams.set('sport', sport);
    if (age) url.searchParams.set('age', age);
    window.location.href = url.pathname + (url.searchParams.toString() ? `?${url.searchParams}` : '');
  };

  const searchFrom = (lat, lng) => {
    if (latEl) latEl.value = String(lat);
    if (lngEl) lngEl.value = String(lng);
    if (locationEl && !locationEl.value.trim()) locationEl.value = 'Near me';
    syncClearVisibility();
    form?.submit();
  };

  document.querySelectorAll('[data-tny-clear-location]').forEach((el) => {
    el.addEventListener('click', (e) => {
      e.preventDefault();
      clearLocationFilter();
    });
  });
  clearBtn?.addEventListener('click', (e) => {
    e.preventDefault();
    clearLocationFilter();
  });
  syncClearVisibility();

  const locate = ({ onFail } = {}) => {
    if (!navigator.geolocation) {
      onFail?.('Location is not available in this browser.');
      return;
    }
    navigator.geolocation.getCurrentPosition((pos) => {
      save(pos.coords.latitude, pos.coords.longitude);
      searchFrom(pos.coords.latitude, pos.coords.longitude);
    }, () => {
      onFail?.('Couldn’t access your location. Allow location access or type a city/ZIP.');
    }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 10 * 60 * 1000 });
  };

  nearBtn?.addEventListener('click', () => {
    nearBtn.disabled = true;
    nearBtn.classList.add('is-loading');
    locate({
      onFail: (msg) => {
        nearBtn.disabled = false;
        nearBtn.classList.remove('is-loading');
        if (statusEl) statusEl.textContent = msg;
      },
    });
  });

  // First visit (no search yet): show sessions near the user automatically —
  // unless they just cleared the location filter.
  const params = new URLSearchParams(window.location.search);
  const hasSearch = params.has('location') || params.has('lat') || params.has('lng');
  const skipAuto = (() => {
    try { return localStorage.getItem(SKIP_AUTO_KEY) === '1'; } catch { return false; }
  })();
  if (!hasSearch && !skipAuto) {
    const saved = readSaved();
    if (saved) {
      if (statusEl) statusEl.textContent = 'Finding sessions near you…';
      searchFrom(saved.lat, saved.lng);
    } else if (navigator.geolocation) {
      const tryLocate = () => {
        if (statusEl) statusEl.textContent = 'Finding sessions near you…';
        locate({
          onFail: () => {
            if (statusEl) statusEl.textContent = 'Showing the next 7 days. Add a location or tap “Use my location” to see sessions near you.';
          },
        });
      };
      if (navigator.permissions?.query) {
        navigator.permissions.query({ name: 'geolocation' })
          .then((status) => { if (status.state !== 'denied') tryLocate(); })
          .catch(tryLocate);
      } else {
        tryLocate();
      }
    }
  }

  locationEl?.addEventListener('input', () => {
    if (latEl) latEl.value = '';
    if (lngEl) lngEl.value = '';
    syncClearVisibility();
  });
})();
</script>
@endpush
