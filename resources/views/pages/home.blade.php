@extends('layouts.app')

@section('title', 'CoachNow - Find the Right Coach. Train With Confidence.')
@section('meta_description', 'Discover and book vetted local soccer coaches tailored to your skill level, age group, and schedule.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/index.css') }}">
@endpush

@section('content')
<main>
    <!-- ==================== HERO SECTION ==================== -->
    <section id="hero"
      class="relative z-20 min-h-[740px] lg:h-screen lg:min-h-screen pt-[106px] pb-[72px] flex items-center bg-zinc-950 text-white"
      style="background-image: linear-gradient(90deg, rgba(12,13,14,0.82) 0%, rgba(12,13,14,0.58) 34%, rgba(12,13,14,0.12) 68%, rgba(12,13,14,0.02) 100%), linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.08) 43%, rgba(10,11,12,0.58) 100%), url('{{ asset("assets/hero-bg.png") }}'); background-size: cover; background-position: center bottom;">

      <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10">
        <div class="w-full">

          <!-- Badge Pill -->
          <div
            class="inline-flex items-center px-4 py-2 xl:px-5 xl:py-2.5 rounded-full bg-brand-red text-white text-xs sm:text-sm xl:text-[0.95rem] 2xl:text-base font-medium tracking-[0.01em] uppercase mb-4 xl:mb-5 shadow-[0_4px_14px_rgba(218,2,12,0.3)]">
            FIND. BOOK. PLAY.
          </div>

          <h1 class="max-w-[760px] xl:max-w-[900px] 2xl:max-w-[980px] text-4xl sm:text-5xl md:text-[3.35rem] lg:text-[3.65rem] xl:text-[3.9rem] 2xl:text-[4.25rem] font-medium tracking-[0.01em] text-white leading-none mb-4 xl:mb-5">
            <span class="block">Find the Right adasdasdas Coach.</span>
            <span class="block mt-3">Train With Confidence.</span>
          </h1>

          <!-- Subtitle (Leading 1.8) -->
         <p
  class="text-[12px] sm:text-[12.5px] md:text-[13px] lg:text-[14px] xl:text-[15px] 2xl:text-[15px]
  text-zinc-200/90 max-w-[650px] xl:max-w-[720px] 2xl:max-w-[760px]
  tracking-[0.002em] leading-[1.55] mb-[36px] xl:mb-[40px] font-light">
  Discover trusted local coaches, compare their experience, ratings, pricing, and availability, and book the
  right training session for you or your athlete—all in one simple place.
</p>

          <!-- Search Widget Container -->
          <div class="w-full max-w-[1100px]">
            <div class="mb-5">
              <h2 class="text-lg sm:text-[1.35rem] xl:text-[1.45rem] 2xl:text-[1.55rem] font-medium text-white tracking-tight leading-none">Find a Coach</h2>
              <p class="text-[11px] sm:text-xs xl:text-[0.8rem] 2xl:text-[0.84rem] text-zinc-300/80 mt-1.5 font-light">Search trusted coaches near you and find a session
                that works for your schedule.</p>
            </div>

            <form id="coachSearchForm" onsubmit="return false;"
              class="grid grid-cols-1 sm:grid-cols-[1.35fr_1.15fr_auto] gap-3 items-start">

              <!-- 1. Where? -->
              <div class="hero-field hero-field-location flex flex-col relative z-[25]">
                <label for="locationInput"
                  class="text-[11px] sm:text-xs font-semibold text-white mb-1.5 pl-1">Where?</label>
                <div class="relative z-30" data-dropdown="location">
                  <div
                    class="relative flex items-center min-w-0 bg-white rounded-xl h-12 sm:h-[50px] px-3 shadow-lg focus-within:ring-2 focus-within:ring-brand-red transition-all">
                    <svg class="w-4 h-4 min-w-4 min-h-4 text-zinc-500 shrink-0 mr-2" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                      <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                    <input type="text" id="locationInput" placeholder="Park, city, or ZIP"
                      autocomplete="off"
                      aria-expanded="false"
                      aria-controls="locationDropdown"
                      class="min-w-0 flex-1 bg-transparent text-zinc-900 text-sm font-medium outline-none placeholder:text-zinc-400">
                  </div>
                  <div id="locationDropdown" class="hero-dropdown hidden" role="listbox" aria-label="Locations near you">
                    <div class="hero-dropdown-label">Locations near you</div>
                    <button type="button" class="hero-dropdown-item" data-location="Sommers Bend" role="option">
                      <span class="hero-dropdown-item-main">
                        <span class="hero-dropdown-item-title">Sommers Bend</span>
                        <span class="hero-dropdown-item-meta">Murrieta · 3 coaches</span>
                      </span>
                      <span class="hero-dropdown-item-dist">1.2 mi</span>
                    </button>
                    <button type="button" class="hero-dropdown-item" data-location="Birdsall" role="option">
                      <span class="hero-dropdown-item-main">
                        <span class="hero-dropdown-item-title">Birdsall</span>
                        <span class="hero-dropdown-item-meta">Temecula · 2 coaches</span>
                      </span>
                      <span class="hero-dropdown-item-dist">2.4 mi</span>
                    </button>
                    <button type="button" class="hero-dropdown-item" data-location="Los Alamos" role="option">
                      <span class="hero-dropdown-item-main">
                        <span class="hero-dropdown-item-title">Los Alamos</span>
                        <span class="hero-dropdown-item-meta">Murrieta · 2 coaches</span>
                      </span>
                      <span class="hero-dropdown-item-dist">3.1 mi</span>
                    </button>
                    <button type="button" class="hero-dropdown-item" data-location="Alta Murrieta" role="option">
                      <span class="hero-dropdown-item-main">
                        <span class="hero-dropdown-item-title">Alta Murrieta</span>
                        <span class="hero-dropdown-item-meta">Murrieta · 2 coaches</span>
                      </span>
                      <span class="hero-dropdown-item-dist">4.0 mi</span>
                    </button>
                    <button type="button" class="hero-dropdown-item" data-location="Temecula Sports Park" role="option">
                      <span class="hero-dropdown-item-main">
                        <span class="hero-dropdown-item-title">Temecula Sports Park</span>
                        <span class="hero-dropdown-item-meta">Temecula · 3 coaches</span>
                      </span>
                      <span class="hero-dropdown-item-dist">5.2 mi</span>
                    </button>
                  </div>
                </div>
                <button type="button" id="useLocationBtn"
                  class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs text-zinc-300 hover:text-white mt-1.5 pl-1 transition-colors">
                  <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <span>Use My Location</span>
                </button>
              </div>

              <!-- 2. When? -->
              <div class="hero-field hero-field-when flex flex-col relative z-[25]">
                <label for="whenInput" class="text-[11px] sm:text-xs font-semibold text-white mb-1.5 pl-1">When?</label>
                <div class="relative z-30" data-dropdown="when">
                  <button type="button" id="whenTrigger"
                    class="relative flex items-center w-full min-w-0 bg-white rounded-xl h-12 sm:h-[50px] px-3 shadow-lg text-left focus:outline-none focus:ring-2 focus:ring-brand-red transition-all"
                    aria-expanded="false" aria-controls="whenCalendar">
                    <svg class="w-4 h-4 min-w-4 min-h-4 text-zinc-500 shrink-0 mr-2" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span id="whenDisplay" class="min-w-0 flex-1 text-zinc-400 text-sm font-medium">Pick a date</span>
                    <input type="hidden" id="whenInput" name="when" value="">
                    <svg class="w-3.5 h-3.5 text-zinc-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                  </button>
                  <div id="whenCalendar" class="hero-calendar hidden" role="dialog" aria-label="Choose a date">
                    <div class="hero-calendar-header">
                      <button type="button" id="calPrev" class="hero-calendar-nav" aria-label="Previous month">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                      </button>
                      <div id="calMonthLabel" class="hero-calendar-month"></div>
                      <button type="button" id="calNext" class="hero-calendar-nav" aria-label="Next month">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                      </button>
                    </div>
                    <div class="hero-calendar-weekdays" aria-hidden="true">
                      <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                    </div>
                    <div id="calDays" class="hero-calendar-days"></div>
                  </div>
                </div>
                <button type="button" id="toggleFiltersBtn"
                  class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs text-zinc-300 hover:text-white mt-1.5 pl-1 transition-colors">
                  <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="4" y1="6" x2="20" y2="6"></line>
                    <line x1="8" y1="12" x2="20" y2="12"></line>
                    <line x1="12" y1="18" x2="20" y2="18"></line>
                  </svg>
                  <span id="toggleFiltersLabel">More filters</span>
                </button>
              </div>

              <!-- 3. Submit Button -->
              <div class="hero-field-submit flex flex-col relative z-10 pt-0 sm:pt-[24px]">
                <button type="submit" id="heroSearchBtn"
                  class="w-full sm:w-auto sm:min-w-[148px] h-12 sm:h-[50px] px-6 bg-brand-red hover:bg-brand-red-hover text-white font-bold text-sm rounded-xl shadow-brand-glow hover:-translate-y-0.5 transition-all flex items-center justify-center">
                  Find a Coach
                </button>
              </div>

              <!-- Collapsed filters -->
              <div id="extraFilters" class="hidden sm:col-span-2 lg:col-span-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="flex flex-col">
                  <label for="sportSelect" class="text-[11px] sm:text-xs font-semibold text-white mb-1.5 pl-1">Sport</label>
                  <div
                    class="relative flex items-center min-w-0 overflow-hidden bg-white rounded-xl h-12 sm:h-[50px] px-3 shadow-lg focus-within:ring-2 focus-within:ring-brand-red transition-all">
                    <svg class="w-4 h-4 min-w-4 min-h-4 text-zinc-500 shrink-0 mr-2" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                      <path d="M2 12h20"></path>
                    </svg>
                    <select id="sportSelect"
                      class="min-w-0 flex-1 bg-transparent text-zinc-900 text-sm font-medium outline-none appearance-none cursor-pointer pr-7">
                      <option value="soccer" selected>Soccer</option>
                      <option value="futsal">Futsal</option>
                      <option value="fitness">Performance & Speed</option>
                    </select>
                    <svg class="w-3.5 h-3.5 text-zinc-500 absolute right-3 pointer-events-none" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                  </div>
                </div>
                <div class="flex flex-col">
                  <label for="sessionTypeSelect" class="text-[11px] sm:text-xs font-semibold text-white mb-1.5 pl-1">Session Type</label>
                  <div
                    class="relative flex items-center min-w-0 overflow-hidden bg-white rounded-xl h-12 sm:h-[50px] px-3 shadow-lg focus-within:ring-2 focus-within:ring-brand-red transition-all">
                    <svg class="w-4 h-4 min-w-4 min-h-4 text-zinc-500 shrink-0 mr-2" viewBox="0 0 24 24" fill="none"
                      stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <circle cx="12" cy="12" r="10"></circle>
                      <polygon points="12 8 16 12 12 16 8 12 12 8"></polygon>
                    </svg>
                    <select id="sessionTypeSelect"
                      class="min-w-0 flex-1 bg-transparent text-zinc-900 text-sm font-medium outline-none appearance-none cursor-pointer pr-7">
                      <option value="all" selected>All Types</option>
                      <option value="1on1">1-on-1 Training</option>
                      <option value="group">Small Group</option>
                      <option value="camp">Clinics & Camps</option>
                    </select>
                    <svg class="w-3.5 h-3.5 text-zinc-500 absolute right-3 pointer-events-none" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="6 9 12 15 18 9"></polyline>
                    </svg>
                  </div>
                </div>
                </div>
              </div>

            </form>
          </div>

        </div>
      </div>
    </section>

    <!-- ==================== AVAILABILITY STRIP ==================== -->
    <section id="availabilityBar" class="relative z-10 bg-zinc-50 border-b border-zinc-200/70">
      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 py-5 lg:py-6 w-full">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5 lg:gap-8">

          <div class="flex items-center gap-4 shrink-0">
            <div class="rounded-xl bg-brand-red text-white flex items-center justify-center shrink-0 shadow-sm p-3">
  <svg
    class="w-8 h-8"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2">
    <circle cx="12" cy="12" r="9"></circle>
    <path d="M12 7v5l3 2"></path>
  </svg>
</div>
            <div class="leading-tight">
  <div
    class="text-xs sm:text-sm xl:text-[0.95rem] 2xl:text-base
    font-medium tracking-[0.01em] text-zinc-900 uppercase">
    Available Today
  </div>

  <div
    class="text-[11px] sm:text-xs xl:text-[0.8rem] 2xl:text-[0.84rem]
    text-zinc-500 mt-1 font-light">
    Sports open near you.
  </div>

  <div
    class="text-[11px] sm:text-xs xl:text-[0.8rem] 2xl:text-[0.84rem]
    text-zinc-500 font-light">
    Book in minutes.
  </div>
</div>
          </div>

          <div class="hidden lg:block w-px h-14 bg-zinc-200"></div>

          <div class="flex items-center gap-3 flex-wrap lg:flex-nowrap lg:flex-1 lg:justify-center">
  <button
    class="px-6 py-3 rounded-xl bg-brand-red text-white text-xs sm:text-[13px] font-semibold shadow-sm">
    4:00 PM
  </button>

  <button
    class="px-6 py-3 rounded-xl border border-zinc-400 bg-white text-zinc-900 text-xs sm:text-[13px] font-medium hover:border-brand-red hover:text-brand-red transition-colors">
    5:30 PM
  </button>

  <button
    class="px-6 py-3 rounded-xl border border-zinc-400 bg-white text-zinc-900 text-xs sm:text-[13px] font-medium hover:border-brand-red hover:text-brand-red transition-colors">
    6:15 PM
  </button>

  <button
    class="px-6 py-3 rounded-xl border border-zinc-400 bg-white text-zinc-900 text-xs sm:text-[13px] font-medium hover:border-brand-red hover:text-brand-red transition-colors">
    7:00 PM
  </button>
</div>

          <a href="{{ route('find-a-coach') }}" class="inline-flex items-center gap-2 text-xs sm:text-[13px] font-medium text-zinc-800 hover:text-brand-red transition-colors group shrink-0">
            <span class="border-b border-zinc-400 pb-0.5">See Available Coaches</span>
            <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </a>

        </div>
      </div>
    </section>

    <!-- ==================== LOCAL PARK LOCATIONS ==================== -->
    <section id="coaches" class="py-16 lg:py-20 bg-[#f7f7f7]">
      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">

        <div class="text-center max-w-2xl mx-auto mb-8 lg:mb-10">
          <div class="coaches-badge inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] lg:text-xs font-semibold uppercase mb-4">
            EXPLORE LOCAL PARKS
          </div>
          <h2 class="coaches-main-title section-main-title text-[1.95rem] sm:text-[2.3rem] lg:text-[2.65rem] font-medium text-[#191615] tracking-[-0.035em] leading-[1.03]">
            Find Coaches at Parks<br>Near You
          </h2>
          <p class="mt-3 text-[14px] text-zinc-500 font-light max-w-md mx-auto">
            Pick a park close by, then browse the coaches who train there.
          </p>
        </div>

        <!-- Compact distance filter -->
        <div class="mb-8 max-w-md mx-auto">
          <div class="flex items-center justify-between gap-3 mb-2">
            <label for="homeDistanceRange" class="text-[13px] font-medium text-[#191615]">Distance</label>
            <output id="homeDistanceValue" for="homeDistanceRange" class="text-[13px] font-semibold text-brand-red">Within 50 miles</output>
          </div>
          <input id="homeDistanceRange" type="range" min="1" max="50" value="50" step="1"
            aria-label="Maximum distance in miles"
            class="home-distance-range w-full">
        </div>

        <!-- Location cards (default view) -->
        <div id="locationsView">
          <div id="locationsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6 w-full">
            <!-- Filled by JS -->
          </div>
        </div>

        <!-- Coaches at selected location -->
        <div id="locationCoachesView" class="hidden">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
              <button type="button" id="backToLocations"
                class="inline-flex items-center gap-2 text-[13px] font-medium text-zinc-600 hover:text-brand-red transition-colors mb-2">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                All locations
              </button>
              <h3 id="selectedLocationTitle" class="text-[1.5rem] sm:text-[1.75rem] font-semibold text-[#191615] tracking-tight"></h3>
              <p id="selectedLocationMeta" class="text-[13px] text-zinc-500 mt-1"></p>
            </div>
          </div>
          <div id="locationCoachesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 lg:gap-6 w-full">
            <!-- Filled by JS -->
          </div>
        </div>

      </div>
    </section>

    <!-- ==================== HOW IT WORKS SECTION ==================== -->
    <section id="how-it-works" class="py-20 lg:py-24 bg-[#F6F6F6]">
      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">

        <div class="text-center mb-14 lg:mb-16">
          <div class="inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] lg:text-xs font-semibold uppercase mb-4">HOW IT WORKS</div>
          <h2 class="section-main-title text-[1.95rem] sm:text-[2.3rem] lg:text-[2.65rem] font-normal text-[#191615] tracking-[-0.045em] leading-[1.08]">Find Your Coach in Four<br>Simple Steps</h2>
        </div>

        <!-- Desktop -->
        <div class="hidden lg:grid items-start grid-cols-[1fr_90px_1fr_90px_1fr_90px_1fr]">

          <!-- STEP 1 -->
          <div class="text-center">
            <div class="w-[82px] h-[82px] mx-auto rounded-[17px] border border-zinc-300 bg-zinc-100 flex items-center justify-center text-brand-red mb-4">
              <img src="{{ asset("assets/Group 273354750.svg") }}" alt="Search for a Coach" class="w-9 h-9 object-contain">
            </div>
            <div class="text-[12px] text-brand-red font-medium mb-2">STEP 1</div>
            <h3 class="text-[18px] font-medium text-[#191615] mb-2">Search for a Coach</h3>
            <p class="text-[14px] text-zinc-500 leading-[1.55] max-w-[210px] mx-auto">Choose your sport, date, and location to find trusted coaches.</p>
          </div>

          <!-- ARROW 1 -->
          <div class="flex justify-center pt-[27px]">
            <svg class="w-[92px] h-[38px] text-[#77716F]" viewBox="0 0 92 38" fill="none">
              <circle cx="7" cy="25" r="4.3" fill="currentColor"></circle>
              <path d="M11 24 C30 7,55 7,78 20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-dasharray="3.2 5"></path>
              <path d="M72.5 13.5 L79.5 20.5 L70.5 23" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          </div>

          <!-- STEP 2 -->
          <div class="text-center">
            <div class="w-[82px] h-[82px] mx-auto rounded-[17px] border border-zinc-300 bg-zinc-100 flex items-center justify-center text-brand-red mb-4">
              <img src="{{ asset("assets/Group 273354751.svg") }}" alt="Compare Your Options" class="w-9 h-9 object-contain">
            </div>
            <div class="text-[12px] text-brand-red font-medium mb-2">STEP 2</div>
            <h3 class="text-[18px] font-medium text-[#191615] mb-2">Compare Your Options</h3>
            <p class="text-[14px] text-zinc-500 leading-[1.55] max-w-[210px] mx-auto">Explore coach profiles, ratings, experience, pricing, and sessions.</p>
          </div>

          <!-- ARROW 2 -->
          <div class="flex justify-center pt-[27px]">
            <svg class="w-[92px] h-[38px] text-[#77716F]" viewBox="0 0 92 38" fill="none">
              <circle cx="7" cy="25" r="4.3" fill="currentColor"></circle>
              <path d="M11 24 C30 7,55 7,78 20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-dasharray="3.2 5"></path>
              <path d="M72.5 13.5 L79.5 20.5 L70.5 23" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          </div>

          <!-- STEP 3 -->
          <div class="text-center">
            <div class="w-[82px] h-[82px] mx-auto rounded-[17px] border border-zinc-300 bg-zinc-100 flex items-center justify-center text-brand-red mb-4">
              <img src="{{ asset("assets/Group 273354752.svg") }}" alt="Choose and Book" class="w-9 h-9 object-contain">
            </div>
            <div class="text-[12px] text-brand-red font-medium mb-2">STEP 3</div>
            <h3 class="text-[18px] font-medium text-[#191615] mb-2">Choose &amp; Book</h3>
            <p class="text-[14px] text-zinc-500 leading-[1.55] max-w-[210px] mx-auto">Pick a session, choose a time, and secure your booking.</p>
          </div>

          <!-- ARROW 3 -->
          <div class="flex justify-center pt-[27px]">
            <svg class="w-[92px] h-[38px] text-[#77716F]" viewBox="0 0 92 38" fill="none">
              <circle cx="7" cy="25" r="4.3" fill="currentColor"></circle>
              <path d="M11 24 C30 7,55 7,78 20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-dasharray="3.2 5"></path>
              <path d="M72.5 13.5 L79.5 20.5 L70.5 23" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          </div>

          <!-- STEP 4 -->
          <div class="text-center">
            <div class="w-[82px] h-[82px] mx-auto rounded-[17px] border border-zinc-300 bg-zinc-100 flex items-center justify-center text-brand-red mb-4">
              <img src="{{ asset("assets/Group 273354753.svg") }}" alt="Train and Grow" class="w-9 h-9 object-contain">
            </div>
            <div class="text-[12px] text-brand-red font-medium mb-2">STEP 4</div>
            <h3 class="text-[18px] font-medium text-[#191615] mb-2">Train &amp; Grow</h3>
            <p class="text-[14px] text-zinc-500 leading-[1.55] max-w-[210px] mx-auto">Train with your coach and take the next step toward your goals.</p>
          </div>
        </div>

        <!-- Mobile / Tablet -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-12 lg:hidden">
          <div class="text-center">
            <div class="w-[82px] h-[82px] mx-auto rounded-[17px] border border-zinc-300 bg-zinc-100 flex items-center justify-center text-brand-red mb-4">
              <img src="{{ asset("assets/Group 273354750.svg") }}" alt="Search for a Coach" class="w-9 h-9 object-contain">
            </div>
            <div class="text-[12px] text-brand-red font-medium mb-2">STEP 1</div>
            <h3 class="text-[18px] font-medium text-[#191615] mb-2">Search for a Coach</h3>
            <p class="text-[14px] text-zinc-500 leading-[1.55] max-w-[210px] mx-auto">Choose your sport, date, and location to find trusted coaches.</p>
          </div>

          <div class="text-center">
            <div class="w-[82px] h-[82px] mx-auto rounded-[17px] border border-zinc-300 bg-zinc-100 flex items-center justify-center text-brand-red mb-4">
              <img src="{{ asset("assets/Group 273354751.svg") }}" alt="Compare Your Options" class="w-9 h-9 object-contain">
            </div>
            <div class="text-[12px] text-brand-red font-medium mb-2">STEP 2</div>
            <h3 class="text-[18px] font-medium text-[#191615] mb-2">Compare Your Options</h3>
            <p class="text-[14px] text-zinc-500 leading-[1.55] max-w-[210px] mx-auto">Explore coach profiles, ratings, experience, pricing, and sessions.</p>
          </div>

          <div class="text-center">
            <div class="w-[82px] h-[82px] mx-auto rounded-[17px] border border-zinc-300 bg-zinc-100 flex items-center justify-center text-brand-red mb-4">
              <img src="{{ asset("assets/Group 273354752.svg") }}" alt="Choose and Book" class="w-9 h-9 object-contain">
            </div>
            <div class="text-[12px] text-brand-red font-medium mb-2">STEP 3</div>
            <h3 class="text-[18px] font-medium text-[#191615] mb-2">Choose &amp; Book</h3>
            <p class="text-[14px] text-zinc-500 leading-[1.55] max-w-[210px] mx-auto">Pick a session, choose a time, and secure your booking.</p>
          </div>

          <div class="text-center">
            <div class="w-[82px] h-[82px] mx-auto rounded-[17px] border border-zinc-300 bg-zinc-100 flex items-center justify-center text-brand-red mb-4">
              <img src="{{ asset("assets/Group 273354753.svg") }}" alt="Train and Grow" class="w-9 h-9 object-contain">
            </div>
            <div class="text-[12px] text-brand-red font-medium mb-2">STEP 4</div>
            <h3 class="text-[18px] font-medium text-[#191615] mb-2">Train &amp; Grow</h3>
            <p class="text-[14px] text-zinc-500 leading-[1.55] max-w-[210px] mx-auto">Train with your coach and take the next step toward your goals.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ==================== COACH RECRUITMENT CTA ==================== -->
    <section class="relative min-h-[430px] flex items-center text-white overflow-hidden bg-[#191615]"
      style="background-image:linear-gradient(90deg,rgba(20,18,18,.82),rgba(20,18,18,.76)),url('{{ asset("assets/hero-bg.png") }}');background-size:cover;background-position:center 58%;">
      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 py-20 text-center relative z-10">
        <div class="inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] lg:text-xs font-semibold uppercase mb-4">READY TO GET STARTED?</div>
        <h2 class="section-main-title text-[1.95rem] sm:text-[2.3rem] lg:text-[2.65rem] font-normal tracking-[-0.045em] leading-[1.08] max-w-[980px] mx-auto">
          Join The Coaches Shaping The<br class="hidden md:block"> Future Of Local Coaching.
        </h2>
        <p class="mt-5 text-[14px] md:text-[15px] text-zinc-300 leading-[1.75] max-w-[900px] mx-auto">
          CoachNow is creating a simpler way for athletes and families to discover trusted coaches in their community. By joining early, you can become part of the founding coaching community and help shape how local athletes connect with coaches, discover new training opportunities, and build lasting relationships. Get involved from the beginning and grow alongside a platform designed to make coaching more accessible, organized, and connected.
        </p>
        <div class="mt-7 flex flex-col sm:flex-row items-center justify-center gap-3">
          <a href="{{ route('find-a-coach') }}" class="inline-flex items-center gap-3 px-6 py-3 rounded-full bg-brand-red hover:bg-brand-red-hover text-[14px] font-medium text-white transition-colors">Find a Coach
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
          </a>
          <a href="{{ route('become-a-coach') }}" class="inline-flex items-center gap-3 px-6 py-3 rounded-full border border-white text-[14px] font-medium text-white hover:bg-white hover:text-[#191615] transition-colors">Become a Coach
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
          </a>
        </div>
      </div>
    </section>

    <!-- ==================== CONTACT + TESTIMONIALS ==================== -->
    <section id="contact" class="py-20 lg:py-24 bg-white">
      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">

        <div class="grid grid-cols-1 lg:grid-cols-[1.15fr_.85fr] gap-6 items-stretch mb-12">
          <div class="min-h-[210px] rounded-[18px] overflow-hidden" style="background-image:url('{{ asset("assets/Background.png") }}');background-size:cover;background-position:center 72%;"></div>
          <div class="flex flex-col justify-center py-2">
            <div class="inline-flex self-start items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] lg:text-xs font-semibold uppercase mb-4">GET IN TOUCH</div>
            <h2 class="section-main-title text-[1.95rem] sm:text-[2.3rem] lg:text-[2.65rem] font-normal tracking-[-0.04em] text-[#191615] leading-[1.03] mb-3">Let’s Connect And<br>Get You Moving.</h2>
            <p class="text-[13px] text-zinc-500 leading-[1.6] max-w-[430px]">Have a question about finding a coach, joining CoachNow, or becoming a founding coach? Send us a message and our team will be happy to help.</p>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[270px_1fr] gap-5 items-stretch mb-16">
          <div class="rounded-[14px] bg-brand-red text-white p-5 flex flex-col justify-between">
            <div><h3 class="text-[20px] font-medium leading-tight mb-1">Have A Question?</h3><p class="text-[12px] text-white/85 leading-[1.55]">We’re here to help you find the right next step with CoachNow.</p></div>
            <a href="{{ route('find-a-coach') }}" class="mt-5 inline-flex self-start items-center gap-2 px-4 py-2 rounded-full bg-white text-brand-red text-[12px] font-medium">Find a Coach
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>
            </a>
          </div>

          <div class="rounded-[18px] bg-[#F3F3F3] p-3 lg:p-4">
            <div class="rounded-[15px] bg-white px-4 py-4 lg:px-5 lg:py-4">
              <form id="contactForm" onsubmit="return false;">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <label class="block">
                    <span class="block text-[12px] lg:text-[13px] font-medium text-[#191615] mb-2">Your Name</span>
                    <input type="text" placeholder="Enter your name" class="w-full h-[40px] rounded-[10px] border border-zinc-300 bg-white px-4 text-[12px] lg:text-[13px] text-zinc-800 placeholder:text-zinc-400 outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">
                  </label>
                  <label class="block">
                    <span class="block text-[12px] lg:text-[13px] font-medium text-[#191615] mb-2">Email Address</span>
                    <input type="email" placeholder="Enter your email address" class="w-full h-[40px] rounded-[10px] border border-zinc-300 bg-white px-4 text-[12px] lg:text-[13px] text-zinc-800 placeholder:text-zinc-400 outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">
                  </label>
                  <label class="block">
                    <span class="block text-[12px] lg:text-[13px] font-medium text-[#191615] mb-2">How can we help?</span>
                    <div class="relative">
                      <select class="w-full h-[40px] rounded-[10px] border border-zinc-300 bg-white px-4 pr-9 text-[12px] lg:text-[13px] text-zinc-600 outline-none appearance-none focus:border-brand-red">
                        <option>Finding a Coach</option>
                        <option>Becoming a Coach</option>
                        <option>General Question</option>
                      </select>
                      <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-3 h-3 text-zinc-500 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                  </label>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-[1fr_auto] gap-4 items-center">
                  <label class="flex items-center gap-2 text-[11px] lg:text-[12px] text-zinc-500 leading-[1.45]">
                    <input type="checkbox" class="w-4 h-4 shrink-0 rounded border-zinc-300">
                    <span>By submitting this form, you agree to our Privacy Policy and Terms &amp; Conditions.</span>
                  </label>
                  <button type="submit" class="inline-flex items-center justify-center px-7 h-[40px] rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-[12px] lg:text-[13px] font-semibold transition-colors">Contact Us Now</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[0.88fr_1.52fr] gap-12 lg:gap-16 items-center">
          <!-- Testimonial Intro -->
          <div>
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] lg:text-xs font-semibold uppercase mb-5">ATHLETE &amp; FAMILY STORIES</div>
            <h2 class="section-main-title text-[1.95rem] sm:text-[2.3rem] lg:text-[2.65rem] font-normal tracking-[-0.04em] text-[#191615] leading-[1.22] mb-5">Designed Around<br>Better Coaching<br>And Development.</h2>
            <p class="text-[13px] text-zinc-500 leading-[1.7] max-w-[430px]">CoachNow connects athletes and families with trusted local coaches, making it easier to discover the right training, book with confidence, and keep moving forward.</p>
            <div class="mt-6 flex gap-2.5">
              <button id="testimonialPrev" type="button" aria-label="Previous testimonial" class="w-11 h-11 rounded-[10px] border border-[#191615] bg-white text-[#191615] hover:bg-brand-red hover:border-brand-red hover:text-white transition-all duration-200 flex items-center justify-center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
              </button>
              <button id="testimonialNext" type="button" aria-label="Next testimonial" class="w-11 h-11 rounded-[10px] border border-[#191615] bg-white text-[#191615] hover:bg-brand-red hover:border-brand-red hover:text-white transition-all duration-200 flex items-center justify-center">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
              </button>
            </div>
          </div>

          <!-- Infinite testimonial belt -->
          <div id="testimonialsViewport" class="w-full min-w-0">
            <div id="testimonialsTrack">
              <article class="testimonial-card rounded-[16px] bg-[#F4F4F4] p-5 lg:p-5 min-h-[330px] flex flex-col">
                <div class="flex items-start justify-between mb-6">
                  <img src="https://i.pravatar.cc/120?img=47" alt="Witri Widya Ning" class="w-14 h-14 rounded-full object-cover">
                  <div class="w-10 h-10 rounded-[8px] bg-brand-red flex items-center justify-center">
                    <img src="{{ asset("assets/SVG.svg") }}" alt="CoachNow rating icon" class="w-5 h-5 object-contain">
                  </div>
                </div>
                <div class="text-brand-red tracking-[.08em] text-[18px] mb-3">★★★★★</div>
                <p class="text-[14px] text-zinc-500 leading-[1.72] mb-4 flex-1">Finding the right coach used to mean searching through endless recommendations. CoachNow makes it much easier to compare options and find training that fits our schedule.</p>
                <div class="text-[15px] font-semibold text-[#191615]">Witri Widya Ning</div>
                <div class="text-[12px] text-brand-red mt-1">Client</div>
              </article>

              <article class="testimonial-card rounded-[16px] bg-[#F4F4F4] p-5 lg:p-5 min-h-[330px] flex flex-col">
                <div class="flex items-start justify-between mb-6">
                  <img src="https://i.pravatar.cc/120?img=12" alt="Jack Harry" class="w-14 h-14 rounded-full object-cover">
                  <div class="w-10 h-10 rounded-[8px] bg-brand-red flex items-center justify-center">
                    <img src="{{ asset("assets/SVG.svg") }}" alt="CoachNow rating icon" class="w-5 h-5 object-contain">
                  </div>
                </div>
                <div class="text-brand-red tracking-[.08em] text-[18px] mb-3">★★★★★</div>
                <p class="text-[14px] text-zinc-500 leading-[1.72] mb-4 flex-1">Finding the right coach used to mean searching through endless recommendations. CoachNow makes it much easier to compare options and find training that fits our schedule.</p>
                <div class="text-[15px] font-semibold text-[#191615]">Jack Harry</div>
                <div class="text-[12px] text-brand-red mt-1">Client</div>
              </article>

              <article class="testimonial-card rounded-[16px] bg-[#F4F4F4] p-5 lg:p-5 min-h-[330px] flex flex-col">
                <div class="flex items-start justify-between mb-6">
                  <img src="https://i.pravatar.cc/120?img=32" alt="Maya Carter" class="w-14 h-14 rounded-full object-cover">
                  <div class="w-10 h-10 rounded-[8px] bg-brand-red flex items-center justify-center">
                    <img src="{{ asset("assets/SVG.svg") }}" alt="CoachNow rating icon" class="w-5 h-5 object-contain">
                  </div>
                </div>
                <div class="text-brand-red tracking-[.08em] text-[18px] mb-3">★★★★★</div>
                <p class="text-[14px] text-zinc-500 leading-[1.72] mb-4 flex-1">The booking process feels simple and organized. We found a coach nearby, compared the options, and chose a session that worked for our family.</p>
                <div class="text-[15px] font-semibold text-[#191615]">Maya Carter</div>
                <div class="text-[12px] text-brand-red mt-1">Client</div>
              </article>

              <article class="testimonial-card rounded-[16px] bg-[#F4F4F4] p-5 lg:p-5 min-h-[330px] flex flex-col">
                <div class="flex items-start justify-between mb-6">
                  <img src="https://i.pravatar.cc/120?img=68" alt="Daniel Reed" class="w-14 h-14 rounded-full object-cover">
                  <div class="w-10 h-10 rounded-[8px] bg-brand-red flex items-center justify-center">
                    <img src="{{ asset("assets/SVG.svg") }}" alt="CoachNow rating icon" class="w-5 h-5 object-contain">
                  </div>
                </div>
                <div class="text-brand-red tracking-[.08em] text-[18px] mb-3">★★★★★</div>
                <p class="text-[14px] text-zinc-500 leading-[1.72] mb-4 flex-1">CoachNow gave us a much clearer way to find the right training. The profiles and session details made it easy to choose with confidence.</p>
                <div class="text-[15px] font-semibold text-[#191615]">Daniel Reed</div>
                <div class="text-[12px] text-brand-red mt-1">Client</div>
              </article>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/index.js') }}"></script>
@endpush
