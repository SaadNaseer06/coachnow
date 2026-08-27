@extends('layouts.app')

@section('title', 'CoachNow - About')
@section('meta_description', 'Learn about CoachNow and how we connect athletes with trusted local coaches.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
@endpush

@section('content')
<main>
    <section id="hero" class="relative min-h-[420px] lg:min-h-[460px] pt-[106px] pb-[64px] flex items-center bg-zinc-950 text-white overflow-hidden" style="background-image: linear-gradient(90deg, rgba(12,13,14,0.82) 0%, rgba(12,13,14,0.58) 34%, rgba(12,13,14,0.12) 68%, rgba(12,13,14,0.02) 100%), linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.08) 43%, rgba(10,11,12,0.58) 100%), url('{{ asset("assets/hero-bg.png") }}'); background-size: cover; background-position: center bottom;">
      <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10">
        <div class="hero-fade-target inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-xs sm:text-sm font-medium tracking-[0.01em] uppercase mb-4 shadow-[0_4px_14px_rgba(218,2,12,0.3)]" style="--hero-delay:40ms">ABOUT COACHNOW</div>
        <h1 class="hero-fade-target max-w-[900px] text-4xl sm:text-5xl md:text-[3.2rem] lg:text-[3.5rem] font-medium tracking-[0.01em] text-white leading-none mb-4" style="--hero-delay:110ms">Your Home for the Coaching Community</h1>
        <p class="hero-fade-target text-[13px] sm:text-[14px] lg:text-[15px] text-zinc-200/90 max-w-[680px] leading-[1.55] font-light" style="--hero-delay:180ms">CoachNow brings trusted local coaches, athletes, and families together through one simple marketplace.</p>
      </div>
    </section>    <section class="py-20 lg:py-24 bg-white motion-section">
      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">
          <div class="motion-item motion-from-left rounded-2xl overflow-hidden min-h-[340px]" style="background-image:linear-gradient(180deg,rgba(0,0,0,.05) 35%,rgba(0,0,0,.55) 100%),url('{{ asset("assets/Background (1).png") }}');background-size:cover;background-position:center;"></div>
          <div class="motion-item motion-from-right" style="--motion-delay:120ms">
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] font-semibold uppercase mb-4">OUR MISSION</div>
            <h2 class="section-main-title text-[1.95rem] sm:text-[2.3rem] lg:text-[2.65rem] font-medium text-[#191615] tracking-[-0.035em] leading-[1.03] mb-5">Built to make local coaching easier to find and book</h2>
            <p class="text-[14px] text-zinc-500 leading-[1.7] mb-4 font-light">We created CoachNow so athletes and families can discover vetted coaches nearby, compare ratings and pricing, and book sessions that fit their schedule—without endless group chats or scattered recommendations.</p>
            <p class="text-[14px] text-zinc-500 leading-[1.7] font-light">Coaches get a clearer way to grow their roster, showcase experience, and manage availability in one place.</p>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-16">
          <div class="benefit-card motion-item motion-soft-up"><div class="w-11 h-11 rounded-[10px] bg-brand-red text-white grid place-items-center mb-4 text-sm font-bold">01</div><h3 class="text-[17px] font-semibold text-[#191615] mb-2">Trusted Profiles</h3><p class="text-[13px] text-zinc-500 leading-[1.65]">Coach profiles are reviewed before going live so families can book with more confidence.</p></div>
          <div class="benefit-card motion-item motion-soft-up" style="--motion-delay:100ms"><div class="w-11 h-11 rounded-[10px] bg-brand-red text-white grid place-items-center mb-4 text-sm font-bold">02</div><h3 class="text-[17px] font-semibold text-[#191615] mb-2">Local First</h3><p class="text-[13px] text-zinc-500 leading-[1.65]">Focus on Murrieta, Temecula, and nearby communities—training that fits real schedules.</p></div>
          <div class="benefit-card motion-item motion-soft-up" style="--motion-delay:200ms"><div class="w-11 h-11 rounded-[10px] bg-brand-red text-white grid place-items-center mb-4 text-sm font-bold">03</div><h3 class="text-[17px] font-semibold text-[#191615] mb-2">Simple Booking</h3><p class="text-[13px] text-zinc-500 leading-[1.65]">Search, compare, and request a session in minutes—without the usual back-and-forth.</p></div>
        </div>
        <div class="mt-14 text-center motion-item motion-soft-up">
          <a href="{{ route('find-a-coach') }}" class="inline-flex items-center gap-2.5 px-6 py-3 rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-sm font-medium shadow-brand-glow transition-all">Find a Coach</a>
        </div>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
@endpush
