@extends('layouts.app')

@section('title', 'CoachNow - Login')
@section('meta_description', 'Sign in to your CoachNow account to manage bookings and coach activity.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
@endpush

@section('content')
<main>
  <section id="hero" class="relative min-h-[420px] lg:min-h-[460px] pt-[106px] pb-[64px] flex items-center bg-zinc-950 text-white overflow-hidden" style="background-image: linear-gradient(90deg, rgba(12,13,14,0.82) 0%, rgba(12,13,14,0.58) 34%, rgba(12,13,14,0.12) 68%, rgba(12,13,14,0.02) 100%), linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.08) 43%, rgba(10,11,12,0.58) 100%), url('{{ asset("assets/hero-bg.png") }}'); background-size: cover; background-position: center bottom;">
    <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10">
      <div class="hero-fade-target inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-xs sm:text-sm font-medium tracking-[0.01em] uppercase mb-4 shadow-[0_4px_14px_rgba(218,2,12,0.3)]" style="--hero-delay:40ms">WELCOME BACK</div>
      <h1 class="hero-fade-target max-w-[900px] text-4xl sm:text-5xl md:text-[3.2rem] lg:text-[3.5rem] font-medium tracking-[0.01em] text-white leading-none mb-4" style="--hero-delay:110ms">Login to CoachNow</h1>
      <p class="hero-fade-target text-[13px] sm:text-[14px] lg:text-[15px] text-zinc-200/90 max-w-[680px] leading-[1.55] font-light" style="--hero-delay:180ms">Access your player dashboard, coach tools, or admin console.</p>
    </div>
  </section>

  <section class="py-20 lg:py-24 bg-[#F6F6F6] motion-section">
    <div class="max-w-[480px] mx-auto px-6">
      <div class="auth-card p-7 lg:p-8 motion-item motion-soft-up">
        <h2 class="text-[22px] font-semibold text-[#191615] mb-1">Sign in</h2>
        <p class="text-[13px] text-zinc-500 mb-6">Enter your details to continue.</p>

        @if ($errors->any())
          <div class="mb-4 rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-700">
            {{ $errors->first() }}
          </div>
        @endif

        <form action="{{ route('login.attempt') }}" method="post" class="space-y-4">
          @csrf
          <label class="block">
            <span class="block text-[12px] font-medium text-[#191615] mb-2">Email</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@email.com" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">
          </label>
          <label class="block">
            <span class="block text-[12px] font-medium text-[#191615] mb-2">Password</span>
            <input type="password" name="password" required placeholder="••••••••" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">
          </label>
          <div class="flex items-center justify-between text-[12px]">
            <label class="flex items-center gap-2 text-zinc-500">
              <input type="checkbox" name="remember" value="1" class="w-4 h-4 accent-[#DA020C]">
              Remember me
            </label>
            <span class="text-zinc-400">Demo password: password</span>
          </div>
          <button type="submit" class="w-full h-11 rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-[13px] font-semibold transition-colors">Login</button>
        </form>

        <div class="mt-5 rounded-[12px] border border-zinc-200 bg-zinc-50 p-4 text-[12px] text-zinc-600 leading-relaxed">
          <p class="font-semibold text-[#191615] mb-2">Demo accounts</p>
          <p>Player: <span class="font-medium text-[#191615]">player@coachnow.test</span></p>
          <p>Coach: <span class="font-medium text-[#191615]">coach@coachnow.test</span></p>
          <p>Admin: <span class="font-medium text-[#191615]">admin@coachnow.test</span></p>
        </div>

        <p class="mt-4 text-center text-[13px] text-zinc-500">New to CoachNow? <a href="{{ route('become-a-coach') }}" class="text-brand-red font-medium hover:text-brand-red-hover">Join as a coach</a> or <a href="{{ route('find-a-coach') }}" class="text-brand-red font-medium hover:text-brand-red-hover">find training</a>.</p>
      </div>
    </div>
  </section>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
@endpush
