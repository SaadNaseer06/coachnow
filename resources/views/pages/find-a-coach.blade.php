@extends('layouts.app')

@php
  $filters = $filters ?? [
    'location' => '',
    'sport' => '',
    'session' => '',
    'min_price' => '',
    'max_price' => '',
    'rating' => '0',
    'experience' => [],
    'age' => [],
    'lat' => '',
    'lng' => '',
  ];
  $hasMoreFilters = ($filters['session'] ?? '') !== ''
    || ($filters['min_price'] ?? '') !== ''
    || ($filters['max_price'] ?? '') !== ''
    || (string) ($filters['rating'] ?? '0') !== '0'
    || ! empty($filters['experience'])
    || ! empty($filters['age']);
@endphp

@section('title', 'CoachNow - Find a Coach Near You')
@section('meta_description', 'Search trusted local coaches near your city or ZIP, ordered by distance.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
@endpush

@section('content')
<main>

    <!-- ==================== FIND A COACH HERO ==================== -->
    <section id="hero"
      class="relative min-h-[560px] lg:min-h-[600px] pt-[106px] pb-[72px] flex items-center bg-zinc-950 text-white overflow-hidden"
      style="
        background-image:
          linear-gradient(90deg, rgba(12,13,14,0.82) 0%, rgba(12,13,14,0.58) 34%, rgba(12,13,14,0.12) 68%, rgba(12,13,14,0.02) 100%),
          linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.08) 43%, rgba(10,11,12,0.58) 100%),
          url('{{ asset("assets/hero-bg.png") }}');
        background-size: cover;
        background-position: center bottom;
      ">

      <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10">
        <div class="w-full">
          <div
            class="hero-fade-target inline-flex items-center px-4 py-2 xl:px-5 xl:py-2.5 rounded-full bg-brand-red text-white text-xs sm:text-sm xl:text-[0.95rem] 2xl:text-base font-medium tracking-[0.01em] uppercase mb-4 xl:mb-5 shadow-[0_4px_14px_rgba(218,2,12,0.3)]"
            style="--hero-delay:40ms;">
            FIND. BOOK. PLAY.
          </div>

          <h1
            class="hero-fade-target max-w-[900px] text-4xl sm:text-5xl md:text-[3.35rem] lg:text-[3.65rem] xl:text-[3.9rem] 2xl:text-[4.25rem] font-medium tracking-[0.01em] text-white leading-none mb-4 xl:mb-5"
            style="--hero-delay:120ms;">
            Find a Coach Near You
          </h1>

          <p
            class="hero-fade-target text-[12px] sm:text-[12.5px] md:text-[13px] lg:text-[14px] xl:text-[15px] 2xl:text-[15px] text-zinc-200/90 max-w-[650px] xl:max-w-[720px] tracking-[0.002em] leading-[1.55] mb-[36px] xl:mb-[40px] font-light"
            style="--hero-delay:190ms;">
            Enter a city or ZIP and we’ll show available coaches nearby — expanding the search radius when needed so you always see options.
          </p>

          <div class="w-full max-w-[860px]">
            <form id="coachSearchForm"
              method="GET"
              action="{{ route('find-a-coach') }}"
              class="hero-fade-target"
              style="--hero-delay:500ms;">

              <input type="hidden" name="lat" id="searchLat" value="{{ $filters['lat'] }}">
              <input type="hidden" name="lng" id="searchLng" value="{{ $filters['lng'] }}">

              <div class="coach-search-bar">
                <div class="coach-search-bar__field">
                  <label for="locationInput">Where</label>
                  <div class="coach-search-bar__control">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <input type="text"
                      id="locationInput"
                      name="location"
                      value="{{ $filters['location'] }}"
                      placeholder="City or ZIP"
                      autocomplete="off">
                    <button type="button" id="useLocationBtn" class="coach-search-bar__locate" title="Use my location" aria-label="Use my location">
                      <svg class="coach-search-bar__locate-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M12 2v3M12 19v3M2 12h3M19 12h3"></path>
                      </svg>
                      <svg class="coach-search-bar__locate-spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2" opacity="0.25"></circle>
                        <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                      </svg>
                    </button>
                  </div>
                </div>

                <div class="coach-search-bar__divider" aria-hidden="true"></div>

                <div class="coach-search-bar__field">
                  <label for="sportSelect">Sport</label>
                  <div class="coach-search-bar__control">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                      <path d="M2 12h20"></path>
                    </svg>
                    <select id="sportSelect" name="sport">
                      <option value="" @selected(($filters['sport'] ?? '') === '')>All sports</option>
                      @foreach (\App\Models\User::SPORTS as $sport)
                        @php $slug = \App\Models\User::sportFilterValue($sport); @endphp
                        <option value="{{ $slug }}" @selected(($filters['sport'] ?? '') === $slug)>{{ $sport }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>

                <button type="submit" id="heroSearchBtn" class="coach-search-bar__submit">
                  Find a Coach
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- ==================== RESULTS ==================== -->
    <section id="resultsSection" class="motion-section py-16 lg:py-20 bg-[#0C0D0E] text-white">
      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 relative">

        <div id="resultsLoader" class="find-results-loader" hidden aria-hidden="true">
          <div class="find-results-loader__card">
            <div class="find-results-loader__bars" aria-hidden="true">
              <span></span><span></span><span></span>
            </div>
            <p>Updating coaches…</p>
          </div>
        </div>

        <div id="resultsMount">
          @include('pages.partials.find-a-coach-results')
        </div>

        <form id="moreFiltersPanel"
          class="{{ $hasMoreFilters ? '' : 'hidden' }} mt-8 mb-2 rounded-[18px] border border-white/10 bg-[#141618] p-5 lg:p-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div>
              <label class="filter-title block font-medium text-white mb-3">Price (per session)</label>
              <div class="grid grid-cols-2 gap-3">
                <input type="number" name="min_price" id="filterMinPrice" value="{{ $filters['min_price'] }}" placeholder="$ Min"
                  class="h-10 rounded-[8px] border border-white/15 bg-[#1B1E22] px-3 text-[12px] text-white outline-none focus:border-brand-red">
                <input type="number" name="max_price" id="filterMaxPrice" value="{{ $filters['max_price'] }}" placeholder="$ Max"
                  class="h-10 rounded-[8px] border border-white/15 bg-[#1B1E22] px-3 text-[12px] text-white outline-none focus:border-brand-red">
              </div>
            </div>

            <div>
              <label class="filter-title block font-medium text-white mb-3">Rating</label>
              <select name="rating" id="filterRating" class="w-full h-10 rounded-[8px] border border-white/15 bg-[#1B1E22] px-3 text-[12px] text-white outline-none focus:border-brand-red">
                <option value="0" @selected((string) $filters['rating'] === '0')>All</option>
                <option value="1" @selected((string) $filters['rating'] === '1')>1+ ★</option>
                <option value="2" @selected((string) $filters['rating'] === '2')>2+ ★</option>
                <option value="3" @selected((string) $filters['rating'] === '3')>3+ ★</option>
                <option value="4" @selected((string) $filters['rating'] === '4')>4+ ★</option>
              </select>
            </div>

            <div>
              <label class="filter-title block font-medium text-white mb-3">Session type</label>
              <select name="session" id="filterSession" class="w-full h-10 rounded-[8px] border border-white/15 bg-[#1B1E22] px-3 text-[12px] text-white outline-none focus:border-brand-red">
                <option value="" @selected(($filters['session'] ?? '') === '')>All types</option>
                <option value="1on1" @selected(($filters['session'] ?? '') === '1on1')>1-on-1</option>
                <option value="group" @selected(($filters['session'] ?? '') === 'group')>Small group</option>
                <option value="camp" @selected(($filters['session'] ?? '') === 'camp')>Clinics &amp; camps</option>
              </select>
            </div>

            <div>
              <label class="filter-title block font-medium text-white mb-3">Experience</label>
              <div class="grid grid-cols-2 gap-2 filter-option text-zinc-400 text-[12px]">
                @foreach (['1-3' => '1–3 yrs', '4-5' => '4–5 yrs', '6-7' => '6–7 yrs', '8-10' => '8–10 yrs'] as $value => $label)
                  <label class="flex items-center gap-2">
                    <input type="checkbox" name="experience[]" value="{{ $value }}" @checked(in_array($value, $filters['experience'] ?? [], true)) class="accent-[#DA020C] filter-experience">
                    <span>{{ $label }}</span>
                  </label>
                @endforeach
              </div>
            </div>
          </div>

          <div class="mt-5">
            <label class="filter-title block font-medium text-white mb-3">Age group</label>
            <div class="flex flex-wrap gap-x-5 gap-y-2 filter-option text-zinc-400 text-[12px]">
              @foreach (['youth-5-8' => 'Youth (5–8)', 'youth-9-12' => 'Youth (9–12)', 'teen' => 'Teen (13–18)', 'adult' => 'Adult (18+)'] as $value => $label)
                <label class="flex items-center gap-2">
                  <input type="checkbox" name="age[]" value="{{ $value }}" @checked(in_array($value, $filters['age'] ?? [], true)) class="accent-[#DA020C] filter-age">
                  <span>{{ $label }}</span>
                </label>
              @endforeach
            </div>
          </div>

          <div class="mt-6 flex flex-wrap gap-3">
            <button type="submit" id="applyMoreFilters" class="h-11 px-5 rounded-[10px] bg-brand-red hover:bg-brand-red-hover text-white text-[12px] font-semibold transition-colors">
              Apply filters
            </button>
            <button type="button" id="clearExtraFilters" class="h-11 px-5 rounded-[10px] border border-white/20 text-white text-[12px] font-medium inline-flex items-center hover:border-brand-red transition-colors">
              Clear extras
            </button>
          </div>
        </form>
      </div>
    </section>

</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/search-draft.js') }}?v={{ @filemtime(public_path('assets/js/search-draft.js')) ?: time() }}"></script>
  <script src="{{ asset('assets/js/find-a-coach.js') }}?v={{ @filemtime(public_path('assets/js/find-a-coach.js')) ?: time() }}"></script>
@endpush
