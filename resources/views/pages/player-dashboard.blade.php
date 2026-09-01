@extends('layouts.app')

@section('title', 'Player Dashboard | CoachNow')
@section('meta_description', 'Track development progress, review coach reports, and get personalized training guidance in your CoachNow player dashboard.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/player-dashboard.css') }}">
@endpush

@section('content')
<main class="player-dash">
  {{-- Hero --}}
  <section id="hero"
    class="player-dash-hero relative pt-[106px] pb-28 lg:pb-32 min-h-[500px] lg:min-h-[560px] flex items-end bg-zinc-950 text-white overflow-hidden"
    style="background-image:
      linear-gradient(90deg, rgba(12,13,14,0.88) 0%, rgba(12,13,14,0.62) 38%, rgba(12,13,14,0.18) 72%, rgba(12,13,14,0.04) 100%),
      linear-gradient(180deg, rgba(12,13,14,0.08) 0%, rgba(12,13,14,0.12) 55%, rgba(244,244,245,0.95) 100%),
      url('{{ asset('assets/hero-bg.png') }}');
      background-size: cover;
      background-position: center bottom;">

    <div class="relative z-10 max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 pt-8 lg:pt-10 w-full">
      <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-8 lg:gap-12 items-end">
        <div>
          <span class="hero-fade-target inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] font-semibold uppercase tracking-[0.08em] mb-5 shadow-[0_4px_14px_rgba(218,2,12,0.35)]" style="--hero-delay:40ms">Player Dashboard</span>
          <h1 class="hero-fade-target text-4xl sm:text-[2.75rem] lg:text-[3.25rem] font-medium text-white tracking-[-0.02em] leading-[1.05] mb-4 max-w-xl" style="--hero-delay:110ms">Train with purpose.<br>Track your progress.</h1>
          <p class="hero-fade-target text-[14px] sm:text-[15px] text-zinc-300 leading-[1.7] font-light max-w-lg" style="--hero-delay:180ms">Coach reports, skill tracking, and a guided training assistant — everything you need to keep improving between sessions.</p>
        </div>

        <div class="hero-fade-target player-profile-card flex items-center gap-4 px-5 py-4 lg:min-w-[320px]" style="--hero-delay:250ms">
          <div class="relative shrink-0">
            <div class="w-[72px] h-[72px] rounded-full bg-brand-red text-white grid place-items-center text-xl font-bold ring-4 ring-white/20">JU</div>
            <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-2 border-[#191615]"></span>
          </div>
          <div>
            <p class="text-[17px] font-semibold text-white leading-tight">Jamie Underwood</p>
            <p class="text-[13px] text-zinc-300 mt-1">Age 12 · Midfielder · Soccer</p>
            <p class="text-[12px] text-zinc-400 mt-0.5">Right-footed · Murrieta, CA</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Stats overlapping hero --}}
  <div class="relative max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 -mt-16 lg:-mt-20 mb-10 lg:mb-12 z-10">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-5 motion-section">
      <div class="player-stat-card is-featured motion-item motion-soft-up">
        <div class="flex items-start justify-between gap-3 mb-3">
          <div class="player-stat-icon">
            <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
          </div>
          <span class="text-[11px] font-semibold text-emerald-400 bg-emerald-400/10 px-2.5 py-1 rounded-full">+6 this month</span>
        </div>
        <p class="text-[11px] uppercase tracking-[0.1em] text-zinc-400 font-semibold mb-1">Development score</p>
        <p class="text-[2.35rem] font-semibold leading-none text-white">78</p>
      </div>

      <div class="player-stat-card motion-item motion-soft-up" style="--motion-delay:80ms">
        <div class="player-stat-icon mb-3">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </div>
        <p class="text-[11px] uppercase tracking-[0.1em] text-zinc-400 font-semibold mb-1">Sessions completed</p>
        <p class="text-[2.1rem] font-semibold leading-none text-[#191615]">14</p>
        <p class="text-[12px] text-zinc-500 mt-2">3 this month</p>
      </div>

      <div class="player-stat-card motion-item motion-soft-up" style="--motion-delay:160ms">
        <div class="player-stat-icon mb-3">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <p class="text-[11px] uppercase tracking-[0.1em] text-zinc-400 font-semibold mb-1">Current priority</p>
        <p class="text-[2.1rem] font-semibold leading-none text-[#191615]">Scanning</p>
        <p class="text-[12px] text-zinc-500 mt-2">Set by Coach Lee</p>
      </div>

      <div class="player-stat-card motion-item motion-soft-up" style="--motion-delay:240ms">
        <div class="player-stat-icon mb-3">
          <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
        <p class="text-[11px] uppercase tracking-[0.1em] text-zinc-400 font-semibold mb-1">Training streak</p>
        <p class="text-[2.1rem] font-semibold leading-none text-[#191615]">4 weeks</p>
        <p class="text-[12px] text-zinc-500 mt-2">Personal best</p>
      </div>
    </div>
  </div>

  <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 pb-14 lg:pb-16">
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_340px] gap-6 lg:gap-7">
      {{-- Main column --}}
      <div class="space-y-6 lg:space-y-7">
        {{-- Coach report --}}
        <article class="player-panel motion-item motion-soft-up">
          <div class="player-panel-head">
            <div class="flex items-center gap-3.5">
              <div class="w-12 h-12 rounded-full bg-[#191615] text-white grid place-items-center text-sm font-bold ring-2 ring-brand-red/20">CL</div>
              <div>
                <p class="player-section-kicker">Latest report</p>
                <h2 class="text-[1.2rem] font-semibold text-[#191615] leading-tight">Coach Lee · August 4</h2>
              </div>
            </div>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-bold uppercase tracking-[0.08em] shrink-0">Complete</span>
          </div>

          <div class="player-focus-box mb-5">
            <p class="text-[11px] uppercase tracking-[0.08em] text-brand-red font-bold mb-1.5">Focus of the week</p>
            <p class="text-[15px] text-[#191615] leading-[1.6] font-medium">Scan before receiving the ball and open your body to play forward with your first touch.</p>
          </div>

          <div class="player-feedback-grid">
            <div class="player-feedback-card is-positive">
              <p class="text-[12px] font-bold text-emerald-800 uppercase tracking-[0.06em] mb-2">What went well</p>
              <p class="text-[13px] text-zinc-600 leading-[1.65] font-light">Strong passing weight, positive attitude, and better movement after releasing the ball.</p>
            </div>
            <div class="player-feedback-card is-focus">
              <p class="text-[12px] font-bold text-orange-800 uppercase tracking-[0.06em] mb-2">Needs work</p>
              <p class="text-[13px] text-zinc-600 leading-[1.65] font-light">Check both shoulders earlier and receive on the back foot when space is available.</p>
            </div>
          </div>
        </article>

        {{-- Skills --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:100ms">
          <div class="player-panel-head !mb-4 !pb-0 !border-0">
            <div>
              <p class="player-section-kicker">Development profile</p>
              <h2 class="text-[1.2rem] font-semibold text-[#191615]">Skill progress</h2>
            </div>
            <span class="text-[12px] text-zinc-400 font-medium">Updated Aug 4</span>
          </div>

          <div class="space-y-4 pt-2">
            @foreach ([['First touch', 82], ['Scanning', 61], ['Passing', 79], ['Finishing', 70], ['Confidence', 76]] as [$skill, $score])
              <div class="player-skill-row">
                <span class="text-[13px] font-semibold text-zinc-700">{{ $skill }}</span>
                <div class="player-skill-track"><div class="player-skill-fill" style="width: {{ $score }}%"></div></div>
                <span class="text-[13px] font-bold text-[#191615] text-right">{{ $score }}</span>
              </div>
            @endforeach
          </div>
        </article>

        {{-- Sessions --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:180ms">
          <div class="player-panel-head !mb-3 !pb-0 !border-0">
            <div>
              <p class="player-section-kicker">Training history</p>
              <h2 class="text-[1.2rem] font-semibold text-[#191615]">Recent sessions</h2>
            </div>
          </div>

          <div class="player-timeline mt-2">
            <div class="player-timeline-item flex items-start justify-between gap-4">
              <div>
                <p class="text-[14px] font-semibold text-[#191615]">First touch and scanning</p>
                <p class="text-[12px] text-zinc-500 mt-1">Private session · Coach Lee</p>
              </div>
              <span class="text-[12px] font-medium text-zinc-400 shrink-0">Aug 4</span>
            </div>
            <div class="player-timeline-item flex items-start justify-between gap-4">
              <div>
                <p class="text-[14px] font-semibold text-[#191615]">Passing under pressure</p>
                <p class="text-[12px] text-zinc-500 mt-1">Small group · Coach Maria</p>
              </div>
              <span class="text-[12px] font-medium text-zinc-400 shrink-0">Jul 28</span>
            </div>
            <div class="player-timeline-item flex items-start justify-between gap-4">
              <div>
                <p class="text-[14px] font-semibold text-[#191615]">Finishing and movement</p>
                <p class="text-[12px] text-zinc-500 mt-1">Private session · Coach Lee</p>
              </div>
              <span class="text-[12px] font-medium text-zinc-400 shrink-0">Jul 19</span>
            </div>
          </div>
        </article>
      </div>

      {{-- Sidebar --}}
      <aside class="space-y-6 lg:space-y-7">
        {{-- Focus of the week --}}
        <article class="player-panel motion-item motion-soft-up">
          <p class="player-section-kicker">This week</p>
          <h2 class="text-[1.1rem] font-semibold text-[#191615] mb-3">Focus of the Week</h2>
          <p class="text-[13px] text-zinc-600 leading-[1.65] font-light">Scan before receiving the ball and open your body to play forward with your first touch.</p>
        </article>

        {{-- Coach notes summary --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:80ms">
          <p class="player-section-kicker">From your coach</p>
          <h2 class="text-[1.1rem] font-semibold text-[#191615] mb-3">Coach's Notes</h2>
          <p class="text-[13px] text-zinc-600 leading-[1.65] font-light mb-4">Strong passing weight and positive attitude. Keep working on checking both shoulders earlier.</p>
          <a href="#" class="text-[13px] font-semibold text-brand-red hover:underline">View Full Report</a>
        </article>

        {{-- Videos --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:100ms">
          <p class="player-section-kicker">Recommended</p>
          <h2 class="text-[1.1rem] font-semibold text-[#191615] mb-4">Videos for you</h2>
          <div class="space-y-3">
            <div class="player-video-card">
              <div class="player-video-thumb">
                <svg class="w-4 h-4 ml-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              </div>
              <div>
                <p class="text-[14px] font-semibold text-[#191615]">Scan before receiving</p>
                <p class="text-[12px] text-zinc-500 mt-0.5">3-min technique guide</p>
              </div>
            </div>
            <div class="player-video-card">
              <div class="player-video-thumb">
                <svg class="w-4 h-4 ml-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              </div>
              <div>
                <p class="text-[14px] font-semibold text-[#191615]">Back-foot first touch</p>
                <p class="text-[12px] text-zinc-500 mt-0.5">Wall drill progression</p>
              </div>
            </div>
          </div>
        </article>

        {{-- Milestones --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:180ms">
          <p class="player-section-kicker">Achievements</p>
          <h2 class="text-[1.1rem] font-semibold text-[#191615] mb-4">Your milestones</h2>
          <div class="flex flex-wrap gap-2">
            <span class="player-milestone is-earned">
              <svg class="w-3.5 h-3.5 text-brand-red" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
              4-week streak
            </span>
            <span class="player-milestone is-earned">
              <svg class="w-3.5 h-3.5 text-brand-red" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
              10 sessions
            </span>
            <span class="player-milestone is-locked">
              <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              20 sessions
            </span>
          </div>
        </article>
      </aside>
    </div>

    {{-- Upgrade --}}
    <section class="player-upgrade-banner mt-8 lg:mt-10 px-6 py-7 sm:px-8 sm:py-8 flex flex-col md:flex-row md:items-center md:justify-between gap-5 text-white motion-item motion-soft-up">
      <div>
        <p class="text-[11px] uppercase tracking-[0.1em] text-brand-red font-bold mb-2">Development Plus</p>
        <h2 class="text-[1.35rem] sm:text-[1.5rem] font-semibold mb-2">Unlock deeper progress tracking</h2>
        <p class="text-[13px] sm:text-[14px] text-zinc-400 leading-[1.65] font-light max-w-xl">Detailed coach reports and personalized development tracking for every eligible session.</p>
      </div>
      <a href="{{ route('contact') }}" class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-sm font-semibold shadow-brand-glow transition-all shrink-0">Explore subscription</a>
    </section>
  </div>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
@endpush
