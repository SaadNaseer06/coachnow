@extends('layouts.app')

@php
  $name = $coach->display_name;
  $role = $coach->roleLabel();
  $rating = number_format((float) $coach->rating, 1);
  $reviews = (int) $coach->reviews_count;
  $photo = $coach->photoUrl();
  $locationLabel = $coach->location?->area ?: ($coach->location?->name ?: 'Local area');
  $distanceLabel = $coach->location
    ? number_format((float) $coach->location->distance_miles, 1).' miles away'
    : null;
  $parkName = $coach->location?->name;
  $bio = filled($coach->bio)
    ? $coach->bio
    : $name.' is a dedicated'.($coach->sport ? ' '.strtolower($coach->sport) : '').' coach focused on fundamentals, confidence, discipline, and long-term player development.';
  $privateRate = $coach->privateRate();
  $groupRate = $coach->groupRate();
  $teamRate = $coach->teamRate();
  $joined = optional($coach->created_at)->format('M Y') ?: 'Recently';
@endphp

@section('title', 'CoachNow - '.$name)
@section('meta_description', 'View '.$name.' — '.$role)

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/coach-profile.css') }}">
@endpush

@section('content')
<main>
  <section id="hero"
    class="home-hero-bg relative min-h-[560px] lg:min-h-[610px] pt-[106px] pb-[72px] flex items-center bg-zinc-950 text-white overflow-hidden">
    <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10">
      <div class="w-full">
        <div class="hero-fade-target inline-flex items-center px-4 py-2 xl:px-5 xl:py-2.5 rounded-full bg-brand-red text-white text-xs sm:text-sm xl:text-[0.95rem] 2xl:text-base font-medium tracking-[0.01em] uppercase mb-4 xl:mb-5 shadow-[0_4px_14px_rgba(218,2,12,0.3)]"
          style="--hero-delay:40ms">
          COACH PROFILE
        </div>

        <h1 class="hero-fade-target max-w-[900px] text-4xl sm:text-5xl md:text-[3.35rem] lg:text-[3.65rem] xl:text-[3.9rem] 2xl:text-[4.25rem] font-medium tracking-[0.01em] text-white leading-none mb-4 xl:mb-5"
          style="--hero-delay:110ms">
          {{ $name }}
        </h1>

        <p class="hero-fade-target text-[12px] sm:text-[12.5px] md:text-[13px] lg:text-[14px] xl:text-[15px] 2xl:text-[15px] text-zinc-200/90 max-w-[650px] xl:max-w-[720px] tracking-[0.002em] leading-[1.55] mb-7 font-light"
          style="--hero-delay:180ms">
          {{ $role }} focused on fundamentals, confidence, discipline, and long-term player development.
        </p>

        <a href="{{ route('find-a-coach') }}"
          class="hero-fade-target inline-flex items-center gap-2.5 px-6 py-3 rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-[13px] lg:text-sm font-medium shadow-brand-glow hover:-translate-y-0.5 transition-all"
          style="--hero-delay:300ms">
          <span>&larr;</span><span>Back to Coaches</span>
        </a>
      </div>
    </div>
  </section>

  <section class="py-20 lg:py-24 bg-white motion-section">
    <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_285px] gap-6 lg:gap-7 items-start">
        <div>
          <div class="profile-summary rounded-[18px] overflow-hidden border border-zinc-200/50 shadow-sm motion-item motion-pop">
            <div class="grid grid-cols-1 md:grid-cols-[minmax(220px,42%)_minmax(0,1fr)] min-h-[220px]">
              <div class="profile-summary-media relative min-h-[220px] bg-zinc-200">
                <img src="{{ $photo }}"
                  alt="{{ $name }}"
                  class="absolute inset-0 w-full h-full object-cover object-center">
                <div class="absolute left-4 top-4 z-10 inline-flex items-center gap-2 rounded-[8px] bg-white text-[#191615] px-3 py-2 text-[11px] lg:text-[12px] font-medium shadow-sm">
                  <span class="w-2 h-2 rounded-full bg-green-500"></span> Available Today
                </div>
              </div>

              <div class="profile-summary-copy flex flex-col justify-center px-6 lg:px-8 py-6 text-white">
                <h2 class="text-[24px] lg:text-[27px] xl:text-[29px] font-semibold leading-tight mb-1">{{ $name }}</h2>

                <p class="text-[12px] lg:text-[13px] text-white/85 mb-3">{{ $role }}</p>

                <div class="flex items-center gap-2 text-[12px] lg:text-[13px] mb-2">
                  <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.4 2.9 5.88 6.49.94-4.7 4.58 1.11 6.47L12 17.22l-5.8 3.05 1.11-6.47-4.7-4.58 6.49-.94L12 2.4Z"></path></svg>
                  <span class="font-semibold">{{ $rating }}</span>
                  <span>({{ $reviews }} Reviews)</span>
                </div>

                <div class="flex items-center gap-2 text-[12px] lg:text-[13px] mb-2 text-white/85">
                  <span>{{ $coach->specialty }}</span>
                  <span class="text-white/50">&bull;</span>
                  <span>{{ $coach->ages ?: 'All ages' }}</span>
                </div>

                <div class="flex items-center gap-2 text-[12px] lg:text-[13px] mb-3">
                  <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a8 8 0 0 0-8 8c0 5.7 8 12 8 12s8-6.3 8-12a8 8 0 0 0-8-8Zm0 11.2A3.2 3.2 0 1 1 12 6.8a3.2 3.2 0 0 1 0 6.4Z"></path></svg>
                  <span>{{ $locationLabel }}</span>
                  @if ($distanceLabel)
                    <span class="text-white/60">&bull;</span><span>{{ $distanceLabel }}@if ($parkName) &middot; {{ $parkName }}@endif</span>
                  @endif
                </div>

                <div class="flex items-baseline gap-1.5 mb-4">
                  <span class="text-[22px] font-bold text-white">${{ $privateRate }}</span>
                  <span class="text-[11px] text-white/60">/ Session</span>
                </div>

                <div class="flex gap-2 flex-wrap">
                  <span class="inline-flex items-center gap-2 px-3 py-2 rounded-[8px] border border-white/80 bg-black/10 text-[10px] lg:text-[11px]">
                    <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m7.5 12 3 3 6-6" fill="none" stroke="#3c4023" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                    Available Today
                  </span>
                  <span class="inline-flex items-center gap-2 px-3 py-2 rounded-[8px] border border-white/80 bg-black/10 text-[10px] lg:text-[11px]">
                    <svg class="w-4 h-4 fill-current shrink-0" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-8-4.9-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.1-8 11-8 11Z"></path></svg>
                    CPR Certified
                  </span>
                  <span class="inline-flex items-center gap-2 px-3 py-2 rounded-[8px] border border-white/80 bg-black/10 text-[10px] lg:text-[11px]">
                    <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 4 5v6c0 5.2 3.4 9.3 8 11 4.6-1.7 8-5.8 8-11V5l-8-3Z"></path><path d="M12 7v8M8 11h8" fill="none" stroke="#3c4023" stroke-width="2" stroke-linecap="round"></path></svg>
                    Insured
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-5 rounded-[18px] border border-zinc-200 bg-white overflow-hidden motion-item motion-soft-up" style="--motion-delay:120ms">
            <div class="flex overflow-x-auto border-b border-zinc-200 px-4 sm:px-5" role="tablist" aria-label="Coach profile information">
              <button id="tab-about" class="profile-tab active shrink-0 px-3 sm:px-4 py-4 border-b-2 border-transparent text-[12px] lg:text-[13px] text-zinc-500 font-medium" role="tab" aria-selected="true" aria-controls="panel-about" data-tab="about">About</button>
              <button id="tab-services" class="profile-tab shrink-0 px-3 sm:px-4 py-4 border-b-2 border-transparent text-[12px] lg:text-[13px] text-zinc-500 font-medium" role="tab" aria-selected="false" aria-controls="panel-services" data-tab="services">Services &amp; Pricing</button>
              <button id="tab-reviews" class="profile-tab shrink-0 px-3 sm:px-4 py-4 border-b-2 border-transparent text-[12px] lg:text-[13px] text-zinc-500 font-medium" role="tab" aria-selected="false" aria-controls="panel-reviews" data-tab="reviews">Reviews ({{ $reviews }})</button>
              <button id="tab-availability" class="profile-tab shrink-0 px-3 sm:px-4 py-4 border-b-2 border-transparent text-[12px] lg:text-[13px] text-zinc-500 font-medium" role="tab" aria-selected="false" aria-controls="panel-availability" data-tab="availability">Availability</button>
            </div>

            <div id="panel-about" class="profile-panel grid grid-cols-1 md:grid-cols-[1fr_245px] gap-6 p-5 lg:p-6" role="tabpanel" aria-labelledby="tab-about" data-tab-panel="about">
              <div>
                <h3 class="text-[16px] lg:text-[17px] font-semibold text-[#191615] mb-3">About {{ $name }}</h3>
                <p class="text-[13px] lg:text-[14px] text-zinc-500 leading-[1.7]">
                  {{ $bio }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 py-6 my-6 border-y border-zinc-200">
                  <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-full bg-brand-red grid place-items-center shrink-0"><img src="{{ asset('assets/Group 273355217.svg') }}" alt="" class="w-5 h-6 object-contain"></span>
                    <div><strong class="block text-[15px] lg:text-[16px]">{{ $coach->experienceHighlight() }}</strong><span class="text-[12px] lg:text-[13px] text-zinc-500">Years Experience</span></div>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-full bg-brand-red grid place-items-center shrink-0"><img src="{{ asset('assets/Group 273355219.svg') }}" alt="" class="w-7 h-5 object-contain"></span>
                    <div><strong class="block text-[15px] lg:text-[16px]">{{ max($reviews, 1) }}+</strong><span class="text-[12px] lg:text-[13px] text-zinc-500">Athlete Reviews</span></div>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-full bg-brand-red grid place-items-center shrink-0"><img src="{{ asset('assets/Group 273355220.svg') }}" alt="" class="w-6 h-6 object-contain"></span>
                    <div><strong class="block text-[15px] lg:text-[16px]">{{ $coach->ages ?: 'All ages' }}</strong><span class="text-[12px] lg:text-[13px] text-zinc-500">Specialized In</span></div>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="w-11 h-11 rounded-full bg-brand-red grid place-items-center shrink-0"><img src="{{ asset('assets/Group 273355221.svg') }}" alt="" class="w-5 h-6 object-contain"></span>
                    <div><strong class="block text-[15px] lg:text-[16px]">{{ $coach->sport ?: ($coach->specialty ?: '—') }}</strong><span class="text-[12px] lg:text-[13px] text-zinc-500">Primary Focus</span></div>
                  </div>
                </div>

                <h3 class="text-[16px] lg:text-[17px] font-semibold text-[#191615] mb-3">Coaching Philosophy</h3>
                <p class="text-[13px] lg:text-[14px] text-zinc-500 leading-[1.7]">
                  I believe in more than just developing better players&mdash;I aim to build better people. My sessions emphasize fundamentals, hard work, discipline, and teamwork both on and off the field.
                </p>

                <h3 class="text-[16px] lg:text-[17px] font-semibold text-[#191615] mt-6 mb-3">My Approach</h3>
                <ul class="space-y-3 text-[12px] lg:text-[13px] text-zinc-500">
                  <li class="flex gap-2.5"><span class="w-4 h-4 mt-0.5 rounded-full bg-brand-red text-white text-[9px] grid place-items-center">&#10003;</span><span>Focus on fundamentals and technique</span></li>
                  <li class="flex gap-2.5"><span class="w-4 h-4 mt-0.5 rounded-full bg-brand-red text-white text-[9px] grid place-items-center">&#10003;</span><span>Encourage confidence and positive mindset</span></li>
                  <li class="flex gap-2.5"><span class="w-4 h-4 mt-0.5 rounded-full bg-brand-red text-white text-[9px] grid place-items-center">&#10003;</span><span>Build strong work ethic and accountability</span></li>
                  <li class="flex gap-2.5"><span class="w-4 h-4 mt-0.5 rounded-full bg-brand-red text-white text-[9px] grid place-items-center">&#10003;</span><span>Create a supportive, fun, and challenging environment</span></li>
                </ul>
              </div>

              <aside class="rounded-[12px] border border-zinc-200 overflow-hidden divide-y divide-zinc-200">
                <div class="p-4">
                  <h4 class="flex items-center gap-2 text-[14px] lg:text-[15px] font-semibold mb-3"><img src="{{ asset('assets/Vector.png') }}" alt="" class="w-4 h-4 object-contain shrink-0">Sports</h4>
                  <p class="flex items-center gap-2 pl-6 text-[12px] lg:text-[13px] text-zinc-500"><img src="{{ asset('assets/Vector.png') }}" alt="" class="w-3.5 h-3.5 object-contain shrink-0" style="filter:brightness(0) saturate(100%) invert(11%) sepia(97%) saturate(6477%) hue-rotate(356deg) brightness(91%) contrast(114%)">{{ $coach->sport ?: '—' }}</p>
                </div>
                <div class="p-4">
                  <h4 class="flex items-center gap-2 text-[14px] lg:text-[15px] font-semibold mb-3"><img src="{{ asset('assets/Group 273354911.svg') }}" alt="" class="w-4 h-4 object-contain shrink-0" style="filter:brightness(0) saturate(100%) invert(11%) sepia(97%) saturate(6477%) hue-rotate(356deg) brightness(91%) contrast(114%)">Session Types</h4>
                  <p class="pl-6 text-[12px] lg:text-[13px] text-zinc-500 leading-[1.8]">Private Training<br>Group Training<br>Small Group<br>Team Training<br>Clinics / Workshop</p>
                </div>
                <div class="p-4">
                  <h4 class="flex items-center gap-2 text-[14px] lg:text-[15px] font-semibold mb-3"><img src="{{ asset('assets/Group 20.png') }}" alt="" class="w-4 h-4 object-contain shrink-0">Training Locations</h4>
                  <p class="pl-6 text-[12px] lg:text-[13px] text-zinc-500 leading-[1.8]">
                    @if ($parkName)
                      {{ $parkName }} (Primary)<br>
                    @endif
                    {{ $locationLabel }}
                  </p>
                </div>
                <div class="p-4">
                  <h4 class="flex items-center gap-2 text-[14px] lg:text-[15px] font-semibold mb-3"><img src="{{ asset('assets/Group 273355229.svg') }}" alt="" class="w-4 h-4 object-contain shrink-0">Languages</h4>
                  <p class="pl-6 text-[12px] lg:text-[13px] text-zinc-500 leading-[1.8]">English</p>
                </div>
              </aside>
            </div>

            <div id="panel-services" class="profile-panel p-5 lg:p-6" role="tabpanel" aria-labelledby="tab-services" data-tab-panel="services" hidden>
              <h3 class="text-[16px] lg:text-[17px] font-semibold text-[#191615]">Services &amp; Pricing</h3>
              <p class="mt-1 text-[12px] lg:text-[13px] text-zinc-500">Choose the training that fits your goals.</p>

              <div class="mt-5 space-y-5">
                <article class="grid grid-cols-[44px_minmax(0,1fr)] sm:grid-cols-[44px_minmax(0,1fr)_90px] gap-3 rounded-[15px] border border-zinc-200 p-4">
                  <span class="w-10 h-10 rounded-full border border-zinc-200 bg-zinc-50 grid place-items-center" aria-hidden="true">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="7" r="3"></circle><path d="M5 21v-2a7 7 0 0 1 14 0v2"></path></svg>
                  </span>
                  <div>
                    <h4 class="text-[14px] font-semibold text-[#191615]">Private Training</h4>
                    <p class="mt-1 text-[11px] lg:text-[12px] leading-[1.55] text-zinc-500">1-on-1 personalized training designed around your specific goals and skill level.</p>
                    <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-[11px] lg:text-[12px] text-zinc-500">
                      <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-[#191615]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="7" r="3"></circle><path d="M5 21v-2a7 7 0 0 1 14 0v2"></path></svg><span>1 Coach</span></span>
                      <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-[#191615]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="7" r="3"></circle><path d="M5 21v-2a7 7 0 0 1 14 0v2"></path></svg><span>1 Athlete</span></span>
                      <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-[#191615]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg><span>60 min</span></span>
                    </div>
                  </div>
                  <div class="col-start-2 sm:col-start-3 sm:row-start-1 text-left sm:text-right">
                    <strong class="block text-[21px] leading-none text-brand-red">${{ $privateRate }}</strong>
                    <span class="mt-1 block text-[11px] text-zinc-500">/ session</span>
                  </div>
                </article>

                <article class="grid grid-cols-[44px_minmax(0,1fr)] sm:grid-cols-[44px_minmax(0,1fr)_90px] gap-3 rounded-[15px] border border-zinc-200 p-4">
                  <span class="w-10 h-10 rounded-full border border-zinc-200 bg-zinc-50 grid place-items-center" aria-hidden="true">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="7" r="3"></circle><circle cx="5" cy="9" r="2"></circle><circle cx="19" cy="9" r="2"></circle><path d="M7 21v-2a5 5 0 0 1 10 0v2M2 20v-2a4 4 0 0 1 4-4M22 20v-2a4 4 0 0 0-4-4"></path></svg>
                  </span>
                  <div>
                    <h4 class="text-[14px] font-semibold text-[#191615]">Group Training</h4>
                    <p class="mt-1 text-[11px] lg:text-[12px] leading-[1.55] text-zinc-500">Small group sessions focused on skill development, teamwork, and game awareness.</p>
                    <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-[11px] lg:text-[12px] text-zinc-500">
                      <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-[#191615]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="7" r="3"></circle><path d="M5 21v-2a7 7 0 0 1 14 0v2"></path></svg><span>1 Coach</span></span>
                      <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-[#191615]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="8" cy="8" r="3"></circle><circle cx="17" cy="9" r="2.5"></circle><path d="M2 21v-2a6 6 0 0 1 12 0v2M15 15a5 5 0 0 1 7 4.5V21"></path></svg><span>2&ndash;6 Athletes</span></span>
                      <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-[#191615]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg><span>60 min</span></span>
                    </div>
                  </div>
                  <div class="col-start-2 sm:col-start-3 sm:row-start-1 text-left sm:text-right">
                    <strong class="block text-[21px] leading-none text-brand-red">${{ $groupRate }}</strong>
                    <span class="mt-1 block text-[11px] text-zinc-500">/ session</span>
                  </div>
                </article>

                <article class="grid grid-cols-[44px_minmax(0,1fr)] sm:grid-cols-[44px_minmax(0,1fr)_90px] gap-3 rounded-[15px] border border-zinc-200 p-4">
                  <span class="w-10 h-10 rounded-full border border-zinc-200 bg-zinc-50 grid place-items-center" aria-hidden="true">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="9" cy="7" r="3"></circle><circle cx="17" cy="9" r="2.5"></circle><path d="M2 21v-2a7 7 0 0 1 14 0v2M15 14a6 6 0 0 1 7 6v1"></path></svg>
                  </span>
                  <div>
                    <h4 class="text-[14px] font-semibold text-[#191615]">Team Training</h4>
                    <p class="mt-1 text-[11px] lg:text-[12px] leading-[1.55] text-zinc-500">Comprehensive training for teams to improve tactics, cohesion, and performance.</p>
                    <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-[11px] lg:text-[12px] text-zinc-500">
                      <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-[#191615]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="7" r="3"></circle><path d="M5 21v-2a7 7 0 0 1 14 0v2"></path></svg><span>1 Coach</span></span>
                      <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-[#191615]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="8" cy="8" r="3"></circle><circle cx="17" cy="9" r="2.5"></circle><path d="M2 21v-2a6 6 0 0 1 12 0v2M15 15a5 5 0 0 1 7 4.5V21"></path></svg><span>7&ndash;12 Athletes</span></span>
                      <span class="inline-flex items-center gap-1.5"><svg class="w-4 h-4 text-[#191615]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg><span>90 min</span></span>
                    </div>
                  </div>
                  <div class="col-start-2 sm:col-start-3 sm:row-start-1 text-left sm:text-right">
                    <strong class="block text-[21px] leading-none text-brand-red">${{ $teamRate }}</strong>
                    <span class="mt-1 block text-[11px] text-zinc-500">/ session</span>
                  </div>
                </article>
              </div>
            </div>

            <div id="panel-reviews" class="profile-panel p-5 lg:p-6 space-y-5" role="tabpanel" aria-labelledby="tab-reviews" data-tab-panel="reviews" hidden>
              <p class="text-[13px] text-zinc-500">Player reviews coming soon. {{ $name }} currently shows a {{ $rating }} average from {{ $reviews }} reviews.</p>
            </div>

            <div id="panel-availability" class="profile-panel p-5 lg:p-6" role="tabpanel" aria-labelledby="tab-availability" data-tab-panel="availability" hidden>
              <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                  <h3 class="text-[16px] lg:text-[17px] font-semibold text-[#191615]">Coach Availability</h3>
                  <p class="mt-1 text-[11px] lg:text-[12px] text-zinc-500">Pick a date and time in the booking panel to request a session.</p>
                </div>
              </div>
              <p class="mt-5 text-[13px] text-zinc-500">Live calendar slots will appear here once coaching schedules are connected. Use <strong>Book a Session</strong> to send a request for now.</p>
            </div>
          </div>
        </div>

        <aside id="booking" class="space-y-5 motion-item motion-from-right" style="--motion-delay:160ms">
          <div class="rounded-[14px] border border-zinc-200 bg-white p-5">
            <h3 class="text-[16px] lg:text-[17px] font-semibold text-[#191615] mb-1">Book a Session</h3>
            <p class="text-[11px] lg:text-[12px] text-zinc-500">Starting at</p>
            <div class="mt-1 mb-5"><span class="text-[21px] xl:text-[23px] text-brand-red font-bold">${{ $privateRate }}</span><span class="text-[11px] xl:text-[12px] text-zinc-400"> /session</span></div>

            <label class="block text-[12px] lg:text-[13px] text-zinc-600 font-medium mb-2">Select Session Type</label>
            <select class="w-full h-11 rounded-[10px] border border-zinc-300 bg-white px-3 text-[12px] lg:text-[13px] text-zinc-600 outline-none focus:border-brand-red mb-4">
              <option>Private Training (${{ $privateRate }})</option>
              <option>Small Group (${{ $groupRate }})</option>
              <option>Team Training (${{ $teamRate }})</option>
            </select>

            <label class="block text-[12px] lg:text-[13px] text-zinc-600 font-medium mb-2" for="bookingDateTrigger">Select Date</label>
            <div class="booking-date-field relative z-20 mb-4">
              <button type="button" id="bookingDateTrigger"
                class="w-full h-11 rounded-[10px] border border-zinc-300 bg-white px-3 text-[12px] lg:text-[13px] text-left outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/20 transition-all inline-flex items-center gap-2"
                aria-expanded="false" aria-controls="bookingCalendar">
                <svg class="w-4 h-4 text-zinc-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span id="bookingDateDisplay" class="flex-1 text-zinc-400">Pick a date</span>
                <input type="hidden" id="bookingDateInput" name="bookingDate" value="">
                <svg class="w-3.5 h-3.5 text-zinc-500 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
              </button>
              <div id="bookingCalendar" class="booking-calendar hidden" role="dialog" aria-label="Choose a date">
                <div class="booking-calendar-header">
                  <button type="button" id="bookingCalPrev" class="booking-calendar-nav" aria-label="Previous month">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                  </button>
                  <div id="bookingCalMonthLabel" class="booking-calendar-month"></div>
                  <button type="button" id="bookingCalNext" class="booking-calendar-nav" aria-label="Next month">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                  </button>
                </div>
                <div class="booking-calendar-weekdays" aria-hidden="true">
                  <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                </div>
                <div id="bookingCalDays" class="booking-calendar-days"></div>
              </div>
            </div>
            <label class="block text-[12px] lg:text-[13px] text-zinc-600 font-medium mb-2">Select Time</label>
            <select class="w-full h-11 rounded-[10px] border border-zinc-300 bg-white px-3 text-[12px] lg:text-[13px] text-zinc-600 outline-none focus:border-brand-red">
              <option>Choose a time</option><option>4:00 PM</option><option>5:30 PM</option><option>6:15 PM</option>
            </select>

            @if (! auth()->check() || auth()->user()->isAthlete())
            <a href="{{ route('request-session', ['coach' => $coach->id]) }}"
              class="js-search-carry w-full h-11 rounded-[10px] mt-4 bg-brand-red hover:bg-brand-red-hover text-white text-[12px] lg:text-[13px] font-semibold transition-colors inline-flex items-center justify-center gap-2"><img src="{{ asset('assets/Group 273355246-1.svg') }}" alt="" class="w-4 h-4 object-contain" style="filter:brightness(0) invert(1)">Book Session</a>
            @elseif (auth()->user()->isCoach() && (int) auth()->user()->coach?->id === (int) $coach->id)
            <a href="{{ route('coach.profile') }}"
              class="w-full h-11 rounded-[10px] mt-4 bg-brand-red hover:bg-brand-red-hover text-white text-[12px] lg:text-[13px] font-semibold transition-colors inline-flex items-center justify-center">Edit my listing</a>
            @endif
            <a href="{{ route('contact') }}"
              class="w-full h-11 rounded-[10px] mt-3 border border-[#191615] bg-white text-[#191615] text-[12px] lg:text-[13px] font-medium hover:bg-[#191615] hover:text-white transition-colors inline-flex items-center justify-center gap-2"><svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path><path d="M8 9h8M8 13h5"></path></svg>Send Message</a>

            <p class="text-[11px] lg:text-[12px] text-zinc-500 leading-[1.6] mt-4">&#9830; You won't be charged yet.<br>&nbsp;&nbsp;&nbsp;Confirmation from your coach.</p>
          </div>

          <div class="rounded-[14px] border border-zinc-200 bg-white overflow-hidden">
            <h3 class="text-[15px] lg:text-[16px] font-semibold px-5 py-4">Quick Info</h3>
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-zinc-200 text-[12px] lg:text-[13px] text-zinc-500"><span class="inline-flex items-center gap-2"><img src="{{ asset('assets/Group 273355246.svg') }}" alt="" class="w-4 h-4 object-contain shrink-0">Response Time</span><span>In Hours</span></div>
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-zinc-200 text-[12px] lg:text-[13px] text-zinc-500"><span class="inline-flex items-center gap-2"><img src="{{ asset('assets/Group 273355246-1.svg') }}" alt="" class="w-4 h-4 object-contain shrink-0">Joined Date</span><span>{{ $joined }}</span></div>
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-zinc-200 text-[12px] lg:text-[13px] text-zinc-500"><span class="inline-flex items-center gap-2"><img src="{{ asset('assets/Group 273354911.svg') }}" alt="" class="w-4 h-4 object-contain shrink-0">Specialty</span><span class="text-right">{{ $coach->specialty ?: ($coach->sport ?: '—') }}</span></div>
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-zinc-200 text-[12px] lg:text-[13px] text-zinc-500"><span class="inline-flex items-center gap-2"><img src="{{ asset('assets/Group 273354911-1.svg') }}" alt="" class="w-4 h-4 object-contain shrink-0">Ages</span><span>{{ $coach->ages ?: 'All ages' }}</span></div>
            <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-zinc-200 text-[12px] lg:text-[13px] text-zinc-500"><span class="inline-flex items-center gap-2"><img src="{{ asset('assets/Group 273354911-2.svg') }}" alt="" class="w-4 h-4 object-contain shrink-0">Rating</span><span>{{ $rating }} / 5</span></div>
          </div>
        </aside>
      </div>
    </div>
  </section>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/search-draft.js') }}?v={{ @filemtime(public_path('assets/js/search-draft.js')) ?: time() }}"></script>
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
@endpush
