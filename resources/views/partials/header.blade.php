@php
  $navActive = 'block px-4 py-2 rounded-full bg-brand-red text-white font-medium shadow-[0_2px_10px_rgba(218,2,12,0.35)] transition-all';
  $navIdle = 'block px-4 py-2 rounded-full font-normal text-zinc-200 hover:text-white hover:bg-brand-red hover:shadow-[0_2px_10px_rgba(218,2,12,0.28)] transition-all duration-200';
  $mobileActive = 'block px-4 py-2 rounded-lg bg-brand-red text-white font-semibold';
  $mobileIdle = 'block px-4 py-2 rounded-lg hover:bg-white/10';
@endphp

<header id="siteHeader" class="fixed top-0 left-0 w-full z-50 transition-all duration-300 bg-gradient-to-b from-black/80 via-black/40 to-transparent py-4 md:py-5">
  <div class="max-w-[1380px] mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
    <a href="{{ route('home') }}" class="flex items-center group shrink-0">
      <img src="{{ asset('assets/logo.png') }}" alt="CoachNow Logo" class="h-9 md:h-11 w-auto object-contain transition-all duration-300">
    </a>

    <nav class="hidden lg:flex items-center bg-white/[0.08] backdrop-blur-xl border border-white/15 rounded-full p-1 shadow-lg">
      <ul class="flex items-center gap-0.5 list-none m-0 p-0 text-[11px] xl:text-xs font-normal">
        <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? $navActive : $navIdle }}">Home</a></li>
        <li><a href="{{ route('find-a-coach') }}" class="{{ request()->routeIs('find-a-coach', 'coach-profile') ? $navActive : $navIdle }}">Find a Coach</a></li>
        <li><a href="{{ route('become-a-coach') }}" class="{{ request()->routeIs('become-a-coach') ? $navActive : $navIdle }}">Become a Coach</a></li>
        <li><a href="{{ route('player-dashboard') }}" class="{{ request()->routeIs('player-dashboard') ? $navActive : $navIdle }}">Player Dashboard</a></li>
        <li><a href="{{ route('home') }}#how-it-works" class="{{ $navIdle }}">How It Works</a></li>
        <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? $navActive : $navIdle }}">About</a></li>
        <li><a href="{{ route('faq') }}" class="{{ request()->routeIs('faq') ? $navActive : $navIdle }}">FAQ</a></li>
        <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? $navActive : $navIdle }}">Contact</a></li>
      </ul>
    </nav>

    <div class="flex items-center gap-3 shrink-0">
      <a href="{{ route('login') }}" class="nav-action hidden sm:inline-flex items-center justify-center text-xs md:text-sm font-semibold text-white px-5 py-2.5 rounded-full border border-white/40 bg-black/30 backdrop-blur-md hover:bg-white/15 hover:border-white transition-all duration-300">Login</a>
      <a href="{{ route('become-a-coach') }}" class="header-join-btn nav-action inline-flex items-center justify-center text-xs md:text-sm font-semibold text-white px-5 py-3 rounded-full bg-brand-red hover:bg-brand-red-hover shadow-brand-glow hover:-translate-y-0.5 transition-all duration-300">
        <span class="sm:hidden">Join</span>
        <span class="hidden sm:inline">Join CoachNow</span>
      </a>
      <button type="button" id="mobileMenuBtn" class="lg:hidden relative z-[60] flex flex-col justify-center items-center gap-1.5 w-10 h-10 text-white bg-white/10 rounded-full border border-white/20 shrink-0" aria-label="Toggle Navigation" aria-expanded="false" aria-controls="mobileMenuDrawer">
        <span class="w-4 h-0.5 bg-white transition-transform"></span>
        <span class="w-4 h-0.5 bg-white transition-opacity"></span>
        <span class="w-4 h-0.5 bg-white transition-transform"></span>
      </button>
    </div>
  </div>

  <div id="mobileMenuDrawer" class="hidden lg:hidden px-4 pt-3 pb-5 mt-2 bg-[#191615] backdrop-blur-2xl border-b border-zinc-800 transition-all">
    <ul class="flex flex-col gap-2 text-sm font-medium text-zinc-200">
      <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? $mobileActive : $mobileIdle }}">Home</a></li>
      <li><a href="{{ route('find-a-coach') }}" class="{{ request()->routeIs('find-a-coach', 'coach-profile') ? $mobileActive : $mobileIdle }}">Find a Coach</a></li>
      <li><a href="{{ route('become-a-coach') }}" class="{{ request()->routeIs('become-a-coach') ? $mobileActive : $mobileIdle }}">Become a Coach</a></li>
      <li><a href="{{ route('player-dashboard') }}" class="{{ request()->routeIs('player-dashboard') ? $mobileActive : $mobileIdle }}">Player Dashboard</a></li>
      <li><a href="{{ route('home') }}#how-it-works" class="{{ $mobileIdle }}">How It Works</a></li>
      <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? $mobileActive : $mobileIdle }}">About</a></li>
      <li><a href="{{ route('faq') }}" class="{{ request()->routeIs('faq') ? $mobileActive : $mobileIdle }}">FAQ</a></li>
      <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? $mobileActive : $mobileIdle }}">Contact</a></li>
      <li class="pt-2 border-t border-zinc-800 flex gap-2">
        <a href="{{ route('login') }}" class="flex-1 text-center py-2 rounded-lg border border-white/30 text-white font-semibold">Login</a>
        <a href="{{ route('become-a-coach') }}" class="flex-1 text-center py-2 rounded-lg bg-brand-red text-white font-semibold">Join</a>
      </li>
    </ul>
  </div>
</header>
