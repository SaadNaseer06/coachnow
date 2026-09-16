@php
  $navActive = 'block px-4 py-2 rounded-full bg-brand-red text-white font-medium shadow-[0_2px_10px_rgba(218,2,12,0.35)] transition-all';
  $navIdle = 'block px-4 py-2 rounded-full font-normal text-zinc-200 hover:text-white hover:bg-brand-red hover:shadow-[0_2px_10px_rgba(218,2,12,0.28)] transition-all duration-200';
  $mobileActive = 'block px-4 py-2 rounded-lg bg-brand-red text-white font-semibold';
  $mobileIdle = 'block px-4 py-2 rounded-lg hover:bg-white/10';
  $dashboardActive = request()->routeIs('player-dashboard', 'coach.*', 'admin.*');

  $dashboardLinks = [];
  $showRequestSession = ! auth()->check() || auth()->user()->isAthlete();
  $showBecomeCoach = ! auth()->check() || auth()->user()->isAthlete();
  if (auth()->check()) {
      $user = auth()->user();
      if ($user->isAthlete()) {
          $dashboardLinks[] = ['label' => 'Player Dashboard', 'route' => 'player-dashboard', 'active' => request()->routeIs('player-dashboard')];
      }
      if ($user->isCoach()) {
          $dashboardLinks[] = ['label' => 'Coach Dashboard', 'route' => 'coach.dashboard', 'active' => request()->routeIs('coach.*')];
      }
      if ($user->isAdmin()) {
          $dashboardLinks[] = ['label' => 'Admin Dashboard', 'route' => 'admin.dashboard', 'active' => request()->routeIs('admin.*')];
      }
  }
@endphp

<header id="siteHeader" class="fixed top-0 left-0 w-full z-50 transition-all duration-300 bg-gradient-to-b from-black/80 via-black/40 to-transparent py-4 md:py-5">
  <div class="max-w-[1380px] mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-between gap-4">
    <a href="{{ route('home') }}" class="flex items-center group shrink-0">
      <img src="{{ asset('assets/logo.png') }}" alt="CoachNow Logo" class="h-9 md:h-11 w-auto object-contain transition-all duration-300">
    </a>

    <nav class="hidden lg:flex items-center bg-white/[0.08] backdrop-blur-xl border border-white/15 rounded-full p-1 shadow-lg">
      <ul class="flex items-center gap-0.5 list-none m-0 p-0 text-xs font-normal">
        <li><a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? $navActive : $navIdle }}">Home</a></li>
        <li><a href="{{ route('find-a-coach') }}" class="{{ request()->routeIs('find-a-coach', 'coach-profile') ? $navActive : $navIdle }}">Find a Coach</a></li>
        @if ($showRequestSession)
          <li><a href="{{ route('request-session') }}" class="{{ request()->routeIs('request-session') ? $navActive : $navIdle }}">Request Session</a></li>
        @endif
        @if ($showBecomeCoach)
          <li><a href="{{ route('become-a-coach') }}" class="{{ request()->routeIs('become-a-coach') ? $navActive : $navIdle }}">Become a Coach</a></li>
        @endif
        @auth
          @if (count($dashboardLinks) === 1)
            <li><a href="{{ route($dashboardLinks[0]['route']) }}" class="{{ $dashboardActive ? $navActive : $navIdle }}">Dashboard</a></li>
          @elseif (count($dashboardLinks) > 1)
            <li class="nav-dropdown">
              <button type="button" class="nav-dropdown__trigger {{ $dashboardActive ? $navActive : $navIdle }}" aria-haspopup="true" aria-expanded="false">
                Dashboards
                <svg class="nav-dropdown__caret" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
              </button>
              <div class="nav-dropdown__menu" role="menu">
                @foreach ($dashboardLinks as $link)
                  <a href="{{ route($link['route']) }}" class="nav-dropdown__item {{ $link['active'] ? 'is-active' : '' }}" role="menuitem">{{ $link['label'] }}</a>
                @endforeach
              </div>
            </li>
          @endif
        @else
          <li><a href="{{ route('login') }}" class="{{ $navIdle }}">Dashboard</a></li>
        @endauth
        <li><a href="{{ route('home') }}#how-it-works" class="{{ $navIdle }}">How It Works</a></li>
        <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? $navActive : $navIdle }}">About</a></li>
        <li><a href="{{ route('faq') }}" class="{{ request()->routeIs('faq') ? $navActive : $navIdle }}">FAQ</a></li>
        <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? $navActive : $navIdle }}">Contact</a></li>
      </ul>
    </nav>

    <div class="flex items-center gap-3 shrink-0">
      @auth
        <a href="{{ auth()->user()->dashboardPath() }}" class="nav-action hidden sm:inline-flex items-center justify-center text-xs md:text-sm font-semibold text-white px-5 py-2.5 rounded-full border border-white/40 bg-black/30 backdrop-blur-md hover:bg-white/15 hover:border-white transition-all duration-300">My Dashboard</a>
        <form action="{{ route('logout') }}" method="post" class="hidden sm:block">
          @csrf
          <button type="submit" class="nav-action inline-flex items-center justify-center text-xs md:text-sm font-semibold text-white px-5 py-2.5 rounded-full border border-white/40 bg-black/30 backdrop-blur-md hover:bg-white/15 hover:border-white transition-all duration-300">Sign Out</button>
        </form>
      @else
        <a href="{{ route('login') }}" class="nav-action hidden sm:inline-flex items-center justify-center text-xs md:text-sm font-semibold text-white px-5 py-2.5 rounded-full border border-white/40 bg-black/30 backdrop-blur-md hover:bg-white/15 hover:border-white transition-all duration-300">Login</a>
        <a href="{{ route('register') }}" class="header-join-btn nav-action inline-flex items-center justify-center text-xs md:text-sm font-semibold text-white px-5 py-3 rounded-full bg-brand-red hover:bg-brand-red-hover shadow-brand-glow hover:-translate-y-0.5 transition-all duration-300">
          <span class="sm:hidden">Sign up</span>
          <span class="hidden sm:inline">Sign up</span>
        </a>
      @endauth
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
      @if ($showRequestSession)
        <li><a href="{{ route('request-session') }}" class="{{ request()->routeIs('request-session') ? $mobileActive : $mobileIdle }}">Request Session</a></li>
      @endif
      @if ($showBecomeCoach)
        <li><a href="{{ route('become-a-coach') }}" class="{{ request()->routeIs('become-a-coach') ? $mobileActive : $mobileIdle }}">Become a Coach</a></li>
      @endif
      @auth
        @if (count($dashboardLinks) === 1)
          <li><a href="{{ route($dashboardLinks[0]['route']) }}" class="{{ $dashboardActive ? $mobileActive : $mobileIdle }}">Dashboard</a></li>
        @elseif (count($dashboardLinks) > 1)
          <li class="px-4 pt-3 pb-1 text-[10px] font-bold uppercase tracking-[0.12em] text-zinc-500">Dashboards</li>
          @foreach ($dashboardLinks as $link)
            <li><a href="{{ route($link['route']) }}" class="{{ $link['active'] ? $mobileActive : $mobileIdle }}">{{ $link['label'] }}</a></li>
          @endforeach
        @endif
      @else
        <li><a href="{{ route('login') }}" class="{{ $mobileIdle }}">Dashboard</a></li>
      @endauth
      <li><a href="{{ route('home') }}#how-it-works" class="{{ $mobileIdle }}">How It Works</a></li>
      <li><a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? $mobileActive : $mobileIdle }}">About</a></li>
      <li><a href="{{ route('faq') }}" class="{{ request()->routeIs('faq') ? $mobileActive : $mobileIdle }}">FAQ</a></li>
      <li><a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? $mobileActive : $mobileIdle }}">Contact</a></li>
      <li class="pt-2 border-t border-zinc-800 flex gap-2">
        @auth
          <a href="{{ auth()->user()->dashboardPath() }}" class="flex-1 text-center py-2 rounded-lg border border-white/30 text-white font-semibold">Dashboard</a>
          <form action="{{ route('logout') }}" method="post" class="flex-1">
            @csrf
            <button type="submit" class="w-full text-center py-2 rounded-lg bg-brand-red text-white font-semibold">Sign Out</button>
          </form>
        @else
          <a href="{{ route('login') }}" class="flex-1 text-center py-2 rounded-lg border border-white/30 text-white font-semibold">Login</a>
          <a href="{{ route('register') }}" class="flex-1 text-center py-2 rounded-lg bg-brand-red text-white font-semibold">Sign up</a>
        @endauth
      </li>
    </ul>
  </div>
</header>
