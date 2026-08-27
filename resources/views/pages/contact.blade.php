@extends('layouts.app')

@section('title', 'CoachNow - Contact')
@section('meta_description', 'Get in touch with the CoachNow team about finding a coach or joining the platform.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
@endpush

@section('content')
<main>
    <section id="hero" class="relative min-h-[420px] lg:min-h-[460px] pt-[106px] pb-[64px] flex items-center bg-zinc-950 text-white overflow-hidden" style="background-image: linear-gradient(90deg, rgba(12,13,14,0.82) 0%, rgba(12,13,14,0.58) 34%, rgba(12,13,14,0.12) 68%, rgba(12,13,14,0.02) 100%), linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.08) 43%, rgba(10,11,12,0.58) 100%), url('{{ asset("assets/hero-bg.png") }}'); background-size: cover; background-position: center bottom;">
      <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10">
        <div class="hero-fade-target inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-xs sm:text-sm font-medium tracking-[0.01em] uppercase mb-4 shadow-[0_4px_14px_rgba(218,2,12,0.3)]" style="--hero-delay:40ms">GET IN TOUCH</div>
        <h1 class="hero-fade-target max-w-[900px] text-4xl sm:text-5xl md:text-[3.2rem] lg:text-[3.5rem] font-medium tracking-[0.01em] text-white leading-none mb-4" style="--hero-delay:110ms">Let's Connect and Get You Moving</h1>
        <p class="hero-fade-target text-[13px] sm:text-[14px] lg:text-[15px] text-zinc-200/90 max-w-[680px] leading-[1.55] font-light" style="--hero-delay:180ms">Have a question about finding a coach, joining CoachNow, or becoming a founding coach? Send us a message.</p>
      </div>
    </section>    <section class="py-20 lg:py-24 bg-white motion-section">
      <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16">
        <div class="grid grid-cols-1 lg:grid-cols-[270px_1fr] gap-5 items-stretch">
          <div class="rounded-[14px] bg-brand-red text-white p-6 flex flex-col justify-between motion-item motion-from-left">
            <div>
              <h3 class="text-[20px] font-medium leading-tight mb-2">Have A Question?</h3>
              <p class="text-[12px] text-white/85 leading-[1.55] mb-6">We're here to help you find the right next step with CoachNow.</p>
              <div class="space-y-4 text-[13px]">
                <div><div class="text-white/70 text-[11px] uppercase tracking-wide mb-1">Phone</div>(782) 444-6566</div>
                <div><div class="text-white/70 text-[11px] uppercase tracking-wide mb-1">Email</div>support@coachnow.com</div>
                <div><div class="text-white/70 text-[11px] uppercase tracking-wide mb-1">Location</div>Murrieta &amp; Temecula, CA</div>
              </div>
            </div>
            <a href="{{ route('find-a-coach') }}" class="mt-8 inline-flex self-start items-center gap-2 px-4 py-2 rounded-full bg-white text-brand-red text-[12px] font-medium">Find a Coach →</a>
          </div>
          <div class="form-card p-5 lg:p-7 motion-item motion-from-right" style="--motion-delay:100ms">
            <form onsubmit="return false;">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <label class="block"><span class="block text-[12px] font-medium text-[#191615] mb-2">Your Name</span><input type="text" placeholder="Enter your name" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10"></label>
                <label class="block"><span class="block text-[12px] font-medium text-[#191615] mb-2">Email Address</span><input type="email" placeholder="Enter your email" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10"></label>
              </div>
              <label class="block mt-4"><span class="block text-[12px] font-medium text-[#191615] mb-2">How can we help?</span><select class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red appearance-none"><option>Finding a Coach</option><option>Becoming a Coach</option><option>General Question</option></select></label>
              <label class="block mt-4"><span class="block text-[12px] font-medium text-[#191615] mb-2">Message</span><textarea rows="5" placeholder="Tell us a little more" class="w-full rounded-[10px] border border-zinc-300 px-4 py-3 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10 resize-y"></textarea></label>
              <div class="mt-5 flex flex-col sm:flex-row sm:items-center gap-4 justify-between">
                <label class="flex items-start gap-2 text-[11px] text-zinc-500 leading-[1.45]"><input type="checkbox" class="mt-0.5 w-4 h-4 rounded border-zinc-300 accent-[#DA020C]"><span>By submitting this form, you agree to our Privacy Policy and Terms &amp; Conditions.</span></label>
                <button type="submit" class="inline-flex items-center justify-center px-7 h-11 rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-[13px] font-semibold transition-colors shrink-0">Contact Us Now</button>
              </div>
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
