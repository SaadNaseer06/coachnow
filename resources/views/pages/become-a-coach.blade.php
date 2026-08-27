@extends('layouts.app')

@section('title', 'CoachNow - Become a Coach')
@section('meta_description', 'Join CoachNow as a founding coach and grow your local coaching business.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
@endpush

@section('content')
<main>
    <section id="hero" class="relative min-h-[420px] lg:min-h-[460px] pt-[106px] pb-[64px] flex items-center bg-zinc-950 text-white overflow-hidden" style="background-image: linear-gradient(90deg, rgba(12,13,14,0.82) 0%, rgba(12,13,14,0.58) 34%, rgba(12,13,14,0.12) 68%, rgba(12,13,14,0.02) 100%), linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.08) 43%, rgba(10,11,12,0.58) 100%), url('{{ asset("assets/hero-bg.png") }}'); background-size: cover; background-position: center bottom;">
      <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10">
        <div class="hero-fade-target inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-xs sm:text-sm font-medium tracking-[0.01em] uppercase mb-4 shadow-[0_4px_14px_rgba(218,2,12,0.3)]" style="--hero-delay:40ms">BECOME A COACH</div>
        <h1 class="hero-fade-target max-w-[900px] text-4xl sm:text-5xl md:text-[3.2rem] lg:text-[3.5rem] font-medium tracking-[0.01em] text-white leading-none mb-4" style="--hero-delay:110ms">Join the Coaches Shaping Local Training</h1>
        <p class="hero-fade-target text-[13px] sm:text-[14px] lg:text-[15px] text-zinc-200/90 max-w-[680px] leading-[1.55] font-light" style="--hero-delay:180ms">Share your experience, set your availability, and connect with athletes and families in your community.</p>
      </div>
    </section>    <section class="py-20 lg:py-24 bg-white motion-section">
      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_1.05fr] gap-10 lg:gap-14 items-start">
          <div class="motion-item motion-from-left">
            <div class="inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] font-semibold uppercase mb-4">FOUNDING COACHES</div>
            <h2 class="section-main-title text-[1.95rem] sm:text-[2.3rem] lg:text-[2.65rem] font-medium text-[#191615] tracking-[-0.035em] leading-[1.03] mb-5">Grow with a platform built for coaches</h2>
            <p class="text-[14px] text-zinc-500 leading-[1.7] mb-8 font-light">Early coaches help shape how local athletes discover training. Join now to build visibility, organize bookings, and grow lasting relationships.</p>
            <div class="space-y-4">
              <div class="flex gap-3 items-start"><span class="w-8 h-8 rounded-full bg-brand-red text-white grid place-items-center text-xs shrink-0">✓</span><div><h3 class="text-[15px] font-semibold text-[#191615]">Get discovered locally</h3><p class="text-[13px] text-zinc-500 mt-1">Show up for athletes searching near Murrieta, Temecula, and nearby areas.</p></div></div>
              <div class="flex gap-3 items-start"><span class="w-8 h-8 rounded-full bg-brand-red text-white grid place-items-center text-xs shrink-0">✓</span><div><h3 class="text-[15px] font-semibold text-[#191615]">Showcase your expertise</h3><p class="text-[13px] text-zinc-500 mt-1">Highlight ratings, session types, pricing, and availability in one profile.</p></div></div>
              <div class="flex gap-3 items-start"><span class="w-8 h-8 rounded-full bg-brand-red text-white grid place-items-center text-xs shrink-0">✓</span><div><h3 class="text-[15px] font-semibold text-[#191615]">Book with less friction</h3><p class="text-[13px] text-zinc-500 mt-1">Reduce back-and-forth messaging and keep training requests organized.</p></div></div>
            </div>
          </div>
          <div class="form-card p-6 lg:p-8 motion-item motion-from-right" style="--motion-delay:120ms">
            <h3 class="text-[20px] font-semibold text-[#191615] mb-1">Apply to Join</h3>
            <p class="text-[13px] text-zinc-500 mb-6">Tell us a bit about yourself and we will follow up.</p>
            <form onsubmit="return false;" class="space-y-4">
              <label class="block"><span class="block text-[12px] font-medium text-[#191615] mb-2">Full Name</span><input type="text" placeholder="Your name" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10"></label>
              <label class="block"><span class="block text-[12px] font-medium text-[#191615] mb-2">Email</span><input type="email" placeholder="you@email.com" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10"></label>
              <label class="block"><span class="block text-[12px] font-medium text-[#191615] mb-2">Primary Sport</span><select class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red appearance-none"><option>Soccer</option><option>Futsal</option><option>Performance &amp; Speed</option></select></label>
              <label class="block"><span class="block text-[12px] font-medium text-[#191615] mb-2">Years of Experience</span><select class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red appearance-none"><option>1–3 years</option><option>4–5 years</option><option>6–7 years</option><option>8+ years</option></select></label>
              <label class="block"><span class="block text-[12px] font-medium text-[#191615] mb-2">Short Bio</span><textarea rows="4" placeholder="Share your coaching style and who you train" class="w-full rounded-[10px] border border-zinc-300 px-4 py-3 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10 resize-y"></textarea></label>
              <button type="submit" class="w-full h-11 rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-[13px] font-semibold transition-colors">Submit Application</button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
@endpush
