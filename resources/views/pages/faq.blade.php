@extends('layouts.app')

@section('title', 'CoachNow - FAQ')
@section('meta_description', 'Answers to common questions about finding coaches, booking sessions, and joining CoachNow.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
@endpush

@section('content')
<main>
    <section id="hero" class="relative min-h-[420px] lg:min-h-[460px] pt-[106px] pb-[64px] flex items-center bg-zinc-950 text-white overflow-hidden" style="background-image: linear-gradient(90deg, rgba(12,13,14,0.82) 0%, rgba(12,13,14,0.58) 34%, rgba(12,13,14,0.12) 68%, rgba(12,13,14,0.02) 100%), linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.08) 43%, rgba(10,11,12,0.58) 100%), url('{{ asset("assets/hero-bg.png") }}'); background-size: cover; background-position: center bottom;">
      <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10">
        <div class="hero-fade-target inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-xs sm:text-sm font-medium tracking-[0.01em] uppercase mb-4 shadow-[0_4px_14px_rgba(218,2,12,0.3)]" style="--hero-delay:40ms">FAQ</div>
        <h1 class="hero-fade-target max-w-[900px] text-4xl sm:text-5xl md:text-[3.2rem] lg:text-[3.5rem] font-medium tracking-[0.01em] text-white leading-none mb-4" style="--hero-delay:110ms">Questions, Answered Clearly</h1>
        <p class="hero-fade-target text-[13px] sm:text-[14px] lg:text-[15px] text-zinc-200/90 max-w-[680px] leading-[1.55] font-light" style="--hero-delay:180ms">Everything you need to know about finding coaches, booking sessions, and joining the CoachNow community.</p>
      </div>
    </section>    <section id="faq" class="py-20 lg:py-24 bg-[#F6F6F6] motion-section">
      <div class="max-w-[900px] mx-auto px-6 sm:px-8 lg:px-12">
        <div class="space-y-3 motion-item motion-soft-up">
            <div class="faq-item">
              <button type="button" aria-expanded="false"><span>How does CoachNow work?</span><span class="faq-icon" aria-hidden="true">+</span></button>
              <div class="faq-body"><p>Search by location, sport, and session type, compare coach profiles and ratings, then book a time that fits your schedule.</p></div>
            </div>
            <div class="faq-item">
              <button type="button" aria-expanded="false"><span>Are coaches verified?</span><span class="faq-icon" aria-hidden="true">+</span></button>
              <div class="faq-body"><p>Yes. Coach profiles are reviewed before going live so athletes and families can browse with more confidence.</p></div>
            </div>
            <div class="faq-item">
              <button type="button" aria-expanded="false"><span>How do I book a session?</span><span class="faq-icon" aria-hidden="true">+</span></button>
              <div class="faq-body"><p>Open a coach profile, choose a session type, select a date and time, then submit your booking request for confirmation.</p></div>
            </div>
            <div class="faq-item">
              <button type="button" aria-expanded="false"><span>What sports are available?</span><span class="faq-icon" aria-hidden="true">+</span></button>
              <div class="faq-body"><p>CoachNow currently focuses on soccer, futsal, and performance training, with more options planned as the community grows.</p></div>
            </div>
            <div class="faq-item">
              <button type="button" aria-expanded="false"><span>How do payments work?</span><span class="faq-icon" aria-hidden="true">+</span></button>
              <div class="faq-body"><p>Payments are processed securely through the platform after your coach confirms the session. You will not be charged before confirmation.</p></div>
            </div>
            <div class="faq-item">
              <button type="button" aria-expanded="false"><span>Can I become a coach on CoachNow?</span><span class="faq-icon" aria-hidden="true">+</span></button>
              <div class="faq-body"><p>Absolutely. Apply through Become a Coach, share your experience and availability, and join the founding coaching community.</p></div>
            </div>
        </div>
        <div class="mt-12 text-center motion-item motion-soft-up" style="--motion-delay:120ms">
          <p class="text-[14px] text-zinc-500 mb-4">Still have a question?</p>
          <a href="{{ route('contact') }}" class="inline-flex items-center gap-2.5 px-6 py-3 rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-sm font-medium transition-all">Contact Us</a>
        </div>
      </div>
    </section>
  </main>
  <script>
    document.querySelectorAll('.faq-item button').forEach((btn) => {
      btn.addEventListener('click', () => {
        const item = btn.closest('.faq-item');
        const open = item.classList.contains('is-open');
        document.querySelectorAll('.faq-item').forEach((el) => {
          el.classList.remove('is-open');
          el.querySelector('button').setAttribute('aria-expanded', 'false');
        });
        if (!open) {
          item.classList.add('is-open');
          btn.setAttribute('aria-expanded', 'true');
        }
      });
    });
  </script>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
@endpush
