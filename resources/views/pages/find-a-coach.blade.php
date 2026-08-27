@extends('layouts.app')

@section('title', 'CoachNow - Find a Coach Near You')
@section('meta_description', 'Search trusted local coaches by location, sport, date, session type, experience, rating, and availability.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
@endpush

@section('content')
<main>

    <!-- ==================== FIND A COACH HERO ==================== -->
    <section id="hero"
      class="relative min-h-[650px] lg:min-h-[680px] pt-[106px] pb-[72px] flex items-center bg-zinc-950 text-white overflow-hidden"
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

          <!-- Badge -->
          <div
            class="hero-fade-target inline-flex items-center px-4 py-2 xl:px-5 xl:py-2.5 rounded-full bg-brand-red text-white text-xs sm:text-sm xl:text-[0.95rem] 2xl:text-base font-medium tracking-[0.01em] uppercase mb-4 xl:mb-5 shadow-[0_4px_14px_rgba(218,2,12,0.3)]"
            style="--hero-delay:40ms;">
            FIND. BOOK. PLAY.
          </div>

          <!-- Heading -->
          <h1
            class="hero-fade-target max-w-[900px] text-4xl sm:text-5xl md:text-[3.35rem] lg:text-[3.65rem] xl:text-[3.9rem] 2xl:text-[4.25rem] font-medium tracking-[0.01em] text-white leading-none mb-4 xl:mb-5"
            style="--hero-delay:120ms;">
            Find a Coach Near You
          </h1>

          <!-- Intro -->
          <p
            class="hero-fade-target text-[12px] sm:text-[12.5px] md:text-[13px] lg:text-[14px] xl:text-[15px] 2xl:text-[15px] text-zinc-200/90 max-w-[650px] xl:max-w-[720px] 2xl:max-w-[760px] tracking-[0.002em] leading-[1.55] mb-[36px] xl:mb-[40px] font-light"
            style="--hero-delay:190ms;">
            Search trusted local coaches, compare their experience, ratings,
            pricing, and availability, and find training that fits your goals
            and schedule.
          </p>


          <!-- SAME SEARCH FORM AS HOME PAGE -->
          <div class="w-full max-w-[1100px]">

            <div class="hero-fade-target mb-5"
              style="--hero-delay:300ms;">

              <h2
                class="text-lg sm:text-[1.35rem] xl:text-[1.45rem] 2xl:text-[1.55rem] font-medium text-white tracking-tight leading-none">
                Find a Coach
              </h2>

              <p
                class="text-[11px] sm:text-xs xl:text-[0.8rem] 2xl:text-[0.84rem] text-zinc-300/80 mt-1.5 font-light">
                Search trusted coaches near you and find a session that works
                for your schedule.
              </p>
            </div>


            <form id="coachSearchForm"
              onsubmit="return false;"
              class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.32fr_1.06fr_1fr_1fr_0.95fr] gap-3 items-start">

              <!-- Where -->
              <div class="hero-fade-target flex flex-col"
                style="--hero-delay:500ms;">

                <label for="locationInput"
                  class="text-[11px] sm:text-xs font-semibold text-white mb-1.5 pl-1">
                  Where?
                </label>

                <div
                  class="relative flex items-center min-w-0 overflow-hidden bg-white rounded-xl h-12 sm:h-[50px] px-3 shadow-lg focus-within:ring-2 focus-within:ring-brand-red transition-all">

                  <svg class="w-4 h-4 min-w-4 min-h-4 text-zinc-500 shrink-0 mr-2"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>

                  <input type="text"
                    id="locationInput"
                    placeholder="City or ZIP Code"
                    class="min-w-0 flex-1 bg-transparent text-zinc-900 text-sm font-medium outline-none placeholder:text-zinc-400">
                </div>

                <button type="button"
                  id="useLocationBtn"
                  class="inline-flex items-center gap-1.5 text-[11px] sm:text-xs text-zinc-300 hover:text-white mt-1.5 pl-1 transition-colors">

                  <svg class="w-3 h-3"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>

                  <span>Use My Location</span>
                </button>
              </div>


              <!-- When -->
              <div class="hero-fade-target flex flex-col"
                style="--hero-delay:605ms;">

                <label for="whenSelect"
                  class="text-[11px] sm:text-xs font-semibold text-white mb-1.5 pl-1">
                  When?
                </label>

                <div
                  class="relative flex items-center min-w-0 overflow-hidden bg-white rounded-xl h-12 sm:h-[50px] px-3 shadow-lg focus-within:ring-2 focus-within:ring-brand-red transition-all">

                  <svg class="w-4 h-4 min-w-4 min-h-4 text-zinc-500 shrink-0 mr-2"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>

                  <select id="whenSelect"
                    class="min-w-0 flex-1 bg-transparent text-zinc-900 text-sm font-medium outline-none appearance-none cursor-pointer pr-7">

                    <option value="today" selected>Today</option>
                    <option value="tomorrow">Tomorrow</option>
                    <option value="this-weekend">This Weekend</option>
                    <option value="next-week">Next Week</option>
                  </select>

                  <svg class="w-3.5 h-3.5 text-zinc-500 absolute right-3 pointer-events-none"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </div>

                <button type="button"
                  id="chooseDateBtn"
                  class="text-[11px] sm:text-xs text-zinc-300 hover:text-white mt-1.5 pl-1 text-left transition-colors">
                  Choose a Date
                </button>
              </div>


              <!-- Sport -->
              <div class="hero-fade-target flex flex-col"
                style="--hero-delay:710ms;">

                <label for="sportSelect"
                  class="text-[11px] sm:text-xs font-semibold text-white mb-1.5 pl-1">
                  Sport
                </label>

                <div
                  class="relative flex items-center min-w-0 overflow-hidden bg-white rounded-xl h-12 sm:h-[50px] px-3 shadow-lg focus-within:ring-2 focus-within:ring-brand-red transition-all">

                  <svg class="w-4 h-4 min-w-4 min-h-4 text-zinc-500 shrink-0 mr-2"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path>
                    <path d="M2 12h20"></path>
                  </svg>

                  <select id="sportSelect"
                    class="min-w-0 flex-1 bg-transparent text-zinc-900 text-sm font-medium outline-none appearance-none cursor-pointer pr-7">

                    <option value="soccer" selected>Soccer</option>
                    <option value="futsal">Futsal</option>
                    <option value="fitness">Performance &amp; Speed</option>
                  </select>

                  <svg class="w-3.5 h-3.5 text-zinc-500 absolute right-3 pointer-events-none"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </div>
              </div>


              <!-- Session Type -->
              <div class="hero-fade-target flex flex-col"
                style="--hero-delay:815ms;">

                <label for="sessionTypeSelect"
                  class="text-[11px] sm:text-xs font-semibold text-white mb-1.5 pl-1">
                  Session Type
                </label>

                <div
                  class="relative flex items-center min-w-0 overflow-hidden bg-white rounded-xl h-12 sm:h-[50px] px-3 shadow-lg focus-within:ring-2 focus-within:ring-brand-red transition-all">

                  <svg class="w-4 h-4 min-w-4 min-h-4 text-zinc-500 shrink-0 mr-2"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polygon points="12 8 16 12 12 16 8 12 12 8"></polygon>
                  </svg>

                  <select id="sessionTypeSelect"
                    class="min-w-0 flex-1 bg-transparent text-zinc-900 text-sm font-medium outline-none appearance-none cursor-pointer pr-7">

                    <option value="all" selected>All Types</option>
                    <option value="1on1">1-on-1 Training</option>
                    <option value="group">Small Group</option>
                    <option value="camp">Clinics &amp; Camps</option>
                  </select>

                  <svg class="w-3.5 h-3.5 text-zinc-500 absolute right-3 pointer-events-none"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <polyline points="6 9 12 15 18 9"></polyline>
                  </svg>
                </div>
              </div>


              <!-- Search Button -->
              <div class="hero-fade-target flex flex-col sm:col-span-2 lg:col-span-1 pt-0 lg:pt-[24px]"
                style="--hero-delay:920ms;">

                <button type="submit"
                  id="heroSearchBtn"
                  class="w-full h-12 sm:h-[50px] bg-brand-red hover:bg-brand-red-hover text-white font-bold text-sm rounded-xl shadow-brand-glow hover:-translate-y-0.5 transition-all flex items-center justify-center">
                  Find a Coach
                </button>
              </div>

            </form>
          </div>

        </div>
      </div>
    </section>


    <!-- ==================== FILTERS + RESULTS ==================== -->
    <section id="resultsSection"
      class="motion-section py-20 lg:py-24 bg-white">

      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">

        <div class="grid grid-cols-1 lg:grid-cols-[320px_minmax(0,1fr)] gap-6 lg:gap-7 items-start">


          <!-- ==================== FILTER PANEL ==================== -->
          <aside id="filtersPanel"
            class="motion-item motion-from-left sticky top-[92px] rounded-[18px] bg-[#F5F5F5] border border-zinc-200/70 p-5 lg:p-6">

            <div class="flex items-center justify-between pb-4">

              <div class="flex items-center gap-2 text-[14px] lg:text-[15px] font-semibold text-[#191615]">
                <svg class="w-4 h-4"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.8"
                  stroke-linecap="round">
                  <path d="M4 5h16"></path>
                  <path d="M7 12h10"></path>
                  <path d="M10 19h4"></path>
                </svg>

                Filters
              </div>

              <button id="clearFilters"
                type="button"
                class="text-[11px] lg:text-[12px] font-semibold text-brand-red hover:text-brand-red-hover transition-colors">
                Clear all
              </button>
            </div>


            <!-- Distance -->
            <div class="border-t border-zinc-200 py-5">

              <div class="filter-title flex items-center justify-between font-medium text-[#191615] mb-5">
                <span>Distance</span>
                <span>⌃</span>
              </div>

              <div class="relative pt-7 mb-3">
                <output id="distanceValue" for="distanceRange"
                  class="absolute top-0 -translate-x-1/2 whitespace-nowrap rounded-md bg-brand-red px-2 py-1 text-[10px] font-semibold leading-none text-white shadow-sm">
                  50 mi
                </output>
                <input id="distanceRange" type="range" min="0" max="50" value="50" step="1"
                  aria-label="Maximum distance in miles"
                  aria-valuetext="50 miles"
                  class="block w-full h-2 accent-[#DA020C] cursor-pointer">
              </div>

              <div class="flex justify-between text-[10px] lg:text-[11px] text-zinc-500">
                <span>0 mi</span>
                <span>25 mi</span>
                <span>50 mi</span>
              </div>
            </div>


            <!-- Price -->
            <div class="border-t border-zinc-200 py-5">

              <div class="filter-title flex items-center justify-between font-medium text-[#191615] mb-4">
                <span>Price (per session)</span>
                <span>⌃</span>
              </div>

              <div class="grid grid-cols-2 gap-3">

                <input id="minPrice"
                  type="number"
                  placeholder="$ Min"
                  class="h-10 rounded-[8px] border border-zinc-300 bg-white px-3 text-[12px] lg:text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">

                <input id="maxPrice"
                  type="number"
                  placeholder="$ Max"
                  class="h-10 rounded-[8px] border border-zinc-300 bg-white px-3 text-[12px] lg:text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">
              </div>
            </div>


            <!-- Rating -->
            <div class="border-t border-zinc-200 py-5">

              <div class="filter-title flex items-center justify-between font-medium text-[#191615] mb-4">
                <span>Rating</span>
                <span>⌃</span>
              </div>

              <div class="flex flex-wrap gap-2">

                <button type="button"
                  class="rating-filter px-3 py-2 rounded-[7px] border border-brand-red bg-brand-red text-white text-[11px] lg:text-[12px]"
                  data-rating="0">
                  All
                </button>

                <button type="button"
                  class="rating-filter px-3 py-2 rounded-[7px] border border-zinc-300 bg-white text-zinc-600 text-[11px] lg:text-[12px] hover:border-brand-red hover:text-brand-red transition-colors"
                  data-rating="1">
                  1+ ★
                </button>

                <button type="button"
                  class="rating-filter px-3 py-2 rounded-[7px] border border-zinc-300 bg-white text-zinc-600 text-[11px] lg:text-[12px] hover:border-brand-red hover:text-brand-red transition-colors"
                  data-rating="2">
                  2+ ★
                </button>

                <button type="button"
                  class="rating-filter px-3 py-2 rounded-[7px] border border-zinc-300 bg-white text-zinc-600 text-[11px] lg:text-[12px] hover:border-brand-red hover:text-brand-red transition-colors"
                  data-rating="3">
                  3+ ★
                </button>

                <button type="button"
                  class="rating-filter px-3 py-2 rounded-[7px] border border-zinc-300 bg-white text-zinc-600 text-[11px] lg:text-[12px] hover:border-brand-red hover:text-brand-red transition-colors"
                  data-rating="4">
                  4+ ★
                </button>
              </div>
            </div>


            <!-- Experience -->
            <div class="border-t border-zinc-200 py-5">

              <div class="filter-title flex items-center justify-between font-medium text-[#191615] mb-4">
                <span>Experience</span>
                <span>⌃</span>
              </div>

              <div class="grid grid-cols-2 gap-x-3 gap-y-3 filter-option text-zinc-500">

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="experience" value="1-3">
                  <span>1–3 Years</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="experience" value="4-5">
                  <span>4–5 Years</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="experience" value="6-7">
                  <span>6–7 Years</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="experience" value="8-10">
                  <span>8–10 Years</span>
                </label>
              </div>
            </div>


            <!-- Age Group -->
            <div class="border-t border-zinc-200 py-5">

              <div class="filter-title flex items-center justify-between font-medium text-[#191615] mb-4">
                <span>Age Group</span>
                <span>⌃</span>
              </div>

              <div class="grid grid-cols-2 gap-x-3 gap-y-3 filter-option text-zinc-500">

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="age" value="youth-5-8">
                  <span>Youth (5–8)</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="age" value="youth-9-12">
                  <span>Youth (9–12)</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="age" value="teen">
                  <span>Teen (13–18)</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="age" value="adult">
                  <span>Adult (18+)</span>
                </label>
              </div>
            </div>


            <!-- Session Type -->
            <div class="border-t border-zinc-200 py-5">

              <div class="filter-title flex items-center justify-between font-medium text-[#191615] mb-4">
                <span>Session Type</span>
                <span>⌃</span>
              </div>

              <div class="grid grid-cols-2 gap-x-3 gap-y-3 filter-option text-zinc-500">

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="session" value="1on1">
                  <span>Private Training</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="session" value="group">
                  <span>Group Training</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="session" value="semi-private">
                  <span>Semi-Private</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="session" value="camp">
                  <span>Clinic / Workshop</span>
                </label>
              </div>
            </div>


            <!-- Availability -->
            <div class="border-t border-zinc-200 py-5">

              <div class="filter-title flex items-center justify-between font-medium text-[#191615] mb-4">
                <span>Availability</span>
                <span>⌃</span>
              </div>

              <div class="space-y-3 filter-option text-zinc-500">

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="availability" value="weekday-morning">
                  <span>Weekdays (Morning)</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="availability" value="weekday-afternoon">
                  <span>Weekdays (Afternoon)</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="availability" value="weekday-evening">
                  <span>Weekdays (Evening)</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="availability" value="weekend-morning">
                  <span>Weekends (Morning)</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="availability" value="weekend-afternoon">
                  <span>Weekends (Afternoon)</span>
                </label>

                <label class="flex items-center gap-2">
                  <input type="checkbox" class="filter-checkbox" data-filter="availability" value="weekend-evening">
                  <span>Weekends (Evening)</span>
                </label>
              </div>
            </div>


            <!-- Filter Buttons -->
            <div class="grid grid-cols-2 gap-3 pt-2">

              <button id="resetFilters"
                type="button"
                class="h-11 rounded-[10px] border border-zinc-500 bg-white text-[#191615] text-[12px] lg:text-[13px] font-medium hover:border-brand-red hover:text-brand-red transition-colors">
                Reset
              </button>

              <button id="applyFilters"
                type="button"
                class="h-11 rounded-[10px] bg-brand-red hover:bg-brand-red-hover text-white text-[12px] lg:text-[13px] font-semibold transition-colors">
                Apply Filters
              </button>
            </div>

          </aside>


          <!-- ==================== COACH RESULTS ==================== -->
          <div id="coachResults" class="space-y-5">


            <!-- Coach Alex -->
            <article
              class="coach-result motion-item motion-from-right grid grid-cols-1 md:grid-cols-[250px_minmax(0,1fr)_135px] gap-5 items-center rounded-[18px] bg-[#F5F5F5] border border-zinc-200/60 p-4 lg:p-5"
              style="--motion-delay:80ms;"
              data-price="45"
              data-rating="4.9"
              data-distance="2.1"
              data-sport="soccer"
              data-experience="6-7"
              data-age="youth-5-8 youth-9-12 teen"
              data-session="1on1"
              data-availability="weekday-morning weekday-evening weekend-morning"
              data-when="today tomorrow this-weekend next-week">

              <img src="{{ asset("assets/Rectangle 8-1.png") }}"
                alt="Coach Alex"
                class="w-full md:w-[250px] h-[200px] rounded-[14px] object-cover object-center">

              <div class="min-w-0">

                <h2 class="coach-name font-semibold text-[#191615]">
                  Coach Alex
                </h2>

                <p class="coach-role text-zinc-500 mt-1 mb-4">
                  Professional Soccer Coach
                </p>

                <div class="coach-meta text-[#191615] mb-2">
                  <span class="text-amber-500">★</span>
                  <span class="font-semibold">4.9</span>
                  <span class="text-zinc-500">(128 Reviews)</span>
                </div>

                <div class="coach-meta text-zinc-500 mb-2">
                  Private Training
                  <span class="mx-1">•</span>
                  Ages 8–14
                </div>

                <div class="coach-meta flex items-center gap-2 text-zinc-500 mb-3">
                  <svg class="w-4 h-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <span>2.1 miles away</span>
                </div>

                <div class="flex items-baseline gap-1.5">
                  <span class="coach-price text-brand-red font-bold">$45</span>
                  <span class="text-[11px] xl:text-[12px] text-zinc-400">
                    / Session
                  </span>
                </div>
              </div>

              <div class="coach-actions grid grid-cols-2 md:grid-cols-1 gap-3 w-full md:w-auto">

                <a href="{{ route('coach-profile') }}"
                  class="coach-button coach-button-primary h-11 px-4 rounded-[10px] bg-brand-red border border-brand-red text-white font-semibold inline-flex items-center justify-center gap-2 hover:bg-brand-red-hover shadow-sm transition-all">
                  View Profile
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>

                <a href="coach-profile.html#booking"
                   class="coach-button coach-button-secondary h-11 px-4 rounded-[10px] border border-[#191615] bg-white text-[#191615] font-medium inline-flex items-center justify-center gap-2 hover:bg-brand-red hover:border-brand-red hover:text-white transition-all">
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                  View Times
                </a>
              </div>

            </article>


            <!-- Coach Maria -->
            <article
              class="coach-result motion-item motion-from-right grid grid-cols-1 md:grid-cols-[250px_minmax(0,1fr)_135px] gap-5 items-center rounded-[18px] bg-[#F5F5F5] border border-zinc-200/60 p-4 lg:p-5"
              style="--motion-delay:180ms;"
              data-price="45"
              data-rating="4.9"
              data-distance="5.8"
              data-sport="soccer futsal"
              data-experience="8-10"
              data-age="youth-5-8 youth-9-12 teen"
              data-session="group camp"
              data-availability="weekday-afternoon weekend-morning weekend-afternoon"
              data-when="tomorrow this-weekend next-week">

              <img src="{{ asset("assets/Rectangle 8-2.png") }}"
                alt="Coach Maria"
                class="w-full md:w-[250px] h-[200px] rounded-[14px] object-cover object-center">

              <div class="min-w-0">

                <h2 class="coach-name font-semibold text-[#191615]">
                  Coach Maria
                </h2>

                <p class="coach-role text-zinc-500 mt-1 mb-4">
                  Professional Soccer Coach
                </p>

                <div class="coach-meta text-[#191615] mb-2">
                  <span class="text-amber-500">★</span>
                  <span class="font-semibold">4.9</span>
                  <span class="text-zinc-500">(128 Reviews)</span>
                </div>

                <div class="coach-meta text-zinc-500 mb-2">
                  Small Group Soccer
                  <span class="mx-1">•</span>
                  Ages 8–16
                </div>

                <div class="coach-meta flex items-center gap-2 text-zinc-500 mb-3">
                  <svg class="w-4 h-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <span>2.1 miles away</span>
                </div>

                <div class="flex items-baseline gap-1.5">
                  <span class="coach-price text-brand-red font-bold">$45</span>
                  <span class="text-[11px] xl:text-[12px] text-zinc-400">
                    / Session
                  </span>
                </div>
              </div>

              <div class="coach-actions grid grid-cols-2 md:grid-cols-1 gap-3 w-full md:w-auto">

                <a href="{{ route('coach-profile') }}"
                  class="coach-button coach-button-primary h-11 px-4 rounded-[10px] bg-brand-red border border-brand-red text-white font-semibold inline-flex items-center justify-center gap-2 hover:bg-brand-red-hover shadow-sm transition-all">
                  View Profile
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>

                <a href="coach-profile.html#booking"
                   class="coach-button coach-button-secondary h-11 px-4 rounded-[10px] border border-[#191615] bg-white text-[#191615] font-medium inline-flex items-center justify-center gap-2 hover:bg-brand-red hover:border-brand-red hover:text-white transition-all">
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                  View Times
                </a>
              </div>

            </article>


            <!-- Coach Mike -->
            <article
              class="coach-result motion-item motion-from-right grid grid-cols-1 md:grid-cols-[250px_minmax(0,1fr)_135px] gap-5 items-center rounded-[18px] bg-[#F5F5F5] border border-zinc-200/60 p-4 lg:p-5"
              style="--motion-delay:280ms;"
              data-price="45"
              data-rating="4.9"
              data-distance="12.4"
              data-sport="soccer fitness"
              data-experience="4-5"
              data-age="teen adult"
              data-session="group semi-private"
              data-availability="weekday-evening weekend-afternoon weekend-evening"
              data-when="today this-weekend next-week">

              <img src="{{ asset("assets/Rectangle 8.png") }}"
                alt="Coach Mike"
                class="w-full md:w-[250px] h-[200px] rounded-[14px] object-cover object-center">

              <div class="min-w-0">

                <h2 class="coach-name font-semibold text-[#191615]">
                  Coach Mike
                </h2>

                <p class="coach-role text-zinc-500 mt-1 mb-4">
                  Professional Soccer Coach
                </p>

                <div class="coach-meta text-[#191615] mb-2">
                  <span class="text-amber-500">★</span>
                  <span class="font-semibold">4.9</span>
                  <span class="text-zinc-500">(128 Reviews)</span>
                </div>

                <div class="coach-meta text-zinc-500 mb-2">
                  Team Training
                  <span class="mx-1">•</span>
                  Advanced Players
                </div>

                <div class="coach-meta flex items-center gap-2 text-zinc-500 mb-3">
                  <svg class="w-4 h-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <span>2.1 miles away</span>
                </div>

                <div class="flex items-baseline gap-1.5">
                  <span class="coach-price text-brand-red font-bold">$45</span>
                  <span class="text-[11px] xl:text-[12px] text-zinc-400">
                    / Session
                  </span>
                </div>
              </div>

              <div class="coach-actions grid grid-cols-2 md:grid-cols-1 gap-3 w-full md:w-auto">

                <a href="{{ route('coach-profile') }}"
                  class="coach-button coach-button-primary h-11 px-4 rounded-[10px] bg-brand-red border border-brand-red text-white font-semibold inline-flex items-center justify-center gap-2 hover:bg-brand-red-hover shadow-sm transition-all">
                  View Profile
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>

                <a href="coach-profile.html#booking"
                   class="coach-button coach-button-secondary h-11 px-4 rounded-[10px] border border-[#191615] bg-white text-[#191615] font-medium inline-flex items-center justify-center gap-2 hover:bg-brand-red hover:border-brand-red hover:text-white transition-all">
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                  View Times
                </a>
              </div>

            </article>


            <!-- Coach Alex Duplicate -->
            <article
              class="coach-result motion-item motion-from-right grid grid-cols-1 md:grid-cols-[250px_minmax(0,1fr)_135px] gap-5 items-center rounded-[18px] bg-[#F5F5F5] border border-zinc-200/60 p-4 lg:p-5"
              style="--motion-delay:380ms;"
              data-price="45"
              data-rating="4.9"
              data-distance="22"
              data-sport="soccer"
              data-experience="1-3"
              data-age="youth-5-8 youth-9-12"
              data-session="1on1 camp"
              data-availability="weekday-morning weekday-afternoon weekend-evening"
              data-when="tomorrow next-week">

              <img src="{{ asset("assets/Rectangle 8-1.png") }}"
                alt="Coach Alex"
                class="w-full md:w-[250px] h-[200px] rounded-[14px] object-cover object-center">

              <div class="min-w-0">

                <h2 class="coach-name font-semibold text-[#191615]">
                  Coach Alex
                </h2>

                <p class="coach-role text-zinc-500 mt-1 mb-4">
                  Professional Soccer Coach
                </p>

                <div class="coach-meta text-[#191615] mb-2">
                  <span class="text-amber-500">★</span>
                  <span class="font-semibold">4.9</span>
                  <span class="text-zinc-500">(128 Reviews)</span>
                </div>

                <div class="coach-meta text-zinc-500 mb-2">
                  Private Training
                  <span class="mx-1">•</span>
                  Ages 8–14
                </div>

                <div class="coach-meta flex items-center gap-2 text-zinc-500 mb-3">
                  <svg class="w-4 h-4 shrink-0"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                  </svg>
                  <span>2.1 miles away</span>
                </div>

                <div class="flex items-baseline gap-1.5">
                  <span class="coach-price text-brand-red font-bold">$45</span>
                  <span class="text-[11px] xl:text-[12px] text-zinc-400">
                    / Session
                  </span>
                </div>
              </div>

              <div class="coach-actions grid grid-cols-2 md:grid-cols-1 gap-3 w-full md:w-auto">

                <a href="{{ route('coach-profile') }}"
                  class="coach-button coach-button-primary h-11 px-4 rounded-[10px] bg-brand-red border border-brand-red text-white font-semibold inline-flex items-center justify-center gap-2 hover:bg-brand-red-hover shadow-sm transition-all">
                  View Profile
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                </a>

                <a href="coach-profile.html#booking"
                   class="coach-button coach-button-secondary h-11 px-4 rounded-[10px] border border-[#191615] bg-white text-[#191615] font-medium inline-flex items-center justify-center gap-2 hover:bg-brand-red hover:border-brand-red hover:text-white transition-all">
                  <svg class="w-3.5 h-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                  View Times
                </a>
              </div>

            </article>

            <div id="noCoachResults" class="hidden rounded-[18px] border border-zinc-200 bg-[#F5F5F5] p-10 text-center" role="status">
              <h2 class="text-lg font-semibold text-[#191615]">No coaches match these filters</h2>
              <p class="mt-2 text-sm text-zinc-500">Try increasing the distance or clearing one or more filters.</p>
              <button id="emptyResetFilters" type="button"
                class="mt-5 h-10 px-5 rounded-[10px] bg-brand-red hover:bg-brand-red-hover text-white text-sm font-semibold transition-colors">
                Clear Filters
              </button>
            </div>

          </div>

        </div>
      </div>
    </section>

  </main>


@endsection

@push('scripts')
  <script src="{{ asset('assets/js/find-a-coach.js') }}"></script>
@endpush
