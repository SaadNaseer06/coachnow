@extends('layouts.app')

@section('title', 'Player Dashboard | CoachNow')
@section('meta_description', 'Track development progress, review coach reports, and get personalized training guidance in your CoachNow player dashboard.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/player-dashboard.css') }}?v={{ @filemtime(public_path('assets/css/player-dashboard.css')) ?: time() }}">
  <link rel="stylesheet" href="{{ asset('assets/css/video-player-modal.css') }}?v={{ @filemtime(public_path('assets/css/video-player-modal.css')) ?: time() }}">
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
      <div class="flex justify-end mb-5 lg:mb-6">
        <div class="player-notify hero-fade-target" style="--hero-delay:40ms" data-player-notify>
          <button type="button" class="player-notify__btn" id="playerNotifyBtn" aria-label="Notifications" aria-expanded="false" aria-controls="playerNotifyPanel">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
            <span class="player-notify__badge" data-notify-badge>{{ $upcomingBookings->count() + $activeRequestCount }}</span>
          </button>

          <div class="player-notify__panel" id="playerNotifyPanel" hidden>
            <div class="player-notify__head">
              <div>
                <p class="player-notify__title">Notifications</p>
                <p class="player-notify__sub">Bookings and your session requests</p>
              </div>
              <button type="button" class="player-notify__mark" data-notify-mark>Mark all read</button>
            </div>

            <div class="player-notify__list">
              @forelse ($upcomingBookings->take(2) as $booking)
                <a href="#recent-sessions" class="player-notify__item is-unread" data-notify-item>
                  <span class="player-notify__dot"></span>
                  <span class="player-notify__body">
                    <strong>Upcoming {{ strtolower($booking->session_type ?? 'session') }}</strong>
                    <span>{{ $booking->whenLabel() }}@if($booking->location) · {{ $booking->location->name }}@endif</span>
                    <em>{{ $booking->coach?->display_name ?? 'Coach' }}</em>
                  </span>
                </a>
              @empty
                <a href="{{ route('request-session') }}" class="player-notify__item is-unread" data-notify-item>
                  <span class="player-notify__dot"></span>
                  <span class="player-notify__body">
                    <strong>No upcoming sessions yet</strong>
                    <span>Request a session or join an open group nearby</span>
                    <em>Browse now</em>
                  </span>
                </a>
              @endforelse
              @foreach ($sessionRequests->whereIn('status', ['open', 'hosted', 'awaiting_deposit', 'confirmed'])->take(3) as $req)
                <a href="#my-requests" class="player-notify__item {{ $req['status'] === 'open' ? 'is-unread' : '' }}" data-notify-item>
                  <span class="player-notify__dot {{ $req['status'] === 'open' ? '' : 'is-muted' }}"></span>
                  <span class="player-notify__body">
                    <strong>{{ $req['session_type'] }} · {{ $req['status_label'] }}</strong>
                    <span>{{ $req['when'] }} · {{ $req['location'] }}</span>
                    <em>{{ $req['coach'] ?? $req['role_label'] }}</em>
                  </span>
                </a>
              @endforeach
            </div>

            <p class="player-notify__note">Manage requests below, or browse open sessions anytime.</p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-[1fr_auto] gap-8 lg:gap-12 items-end">
        <div>
          <span class="hero-fade-target inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-[11px] font-semibold uppercase tracking-[0.08em] mb-5 shadow-[0_4px_14px_rgba(218,2,12,0.35)]" style="--hero-delay:80ms">Player Dashboard</span>
          <h1 class="hero-fade-target text-4xl sm:text-[2.75rem] lg:text-[3.25rem] font-medium text-white tracking-[-0.02em] leading-[1.05] mb-4 max-w-xl" style="--hero-delay:140ms">Train with purpose.<br>Track your progress.</h1>
          <p class="hero-fade-target text-[14px] sm:text-[15px] text-zinc-300 leading-[1.7] font-light max-w-lg" style="--hero-delay:200ms">Coach reports, skill tracking, and smart session alerts — everything you need to keep improving between sessions.</p>
        </div>

        <div class="hero-fade-target player-profile-card flex items-center gap-4 px-5 py-4 lg:min-w-[320px]" style="--hero-delay:260ms">
          <div class="relative shrink-0">
            <div class="w-[72px] h-[72px] rounded-full bg-brand-red text-white grid place-items-center text-xl font-bold ring-4 ring-white/20">{{ $initials }}</div>
            <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-2 border-[#191615]"></span>
          </div>
          <div>
            <p class="text-[17px] font-semibold text-white leading-tight">{{ $user->name }}</p>
            <p class="text-[13px] text-zinc-300 mt-1">Athlete@if($user->sport) · {{ $user->sport }}@endif</p>
            <p class="text-[12px] text-zinc-400 mt-0.5">{{ $user->email }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  {{-- Stats overlapping hero --}}
  <div class="relative max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 -mt-16 lg:-mt-20 mb-10 lg:mb-12 z-10">
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 lg:gap-5 motion-section">
      <div class="player-stat-card is-featured motion-item motion-soft-up">
        <div class="flex items-start justify-between gap-3 mb-2">
          <div class="player-stat-icon">
            <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/></svg>
          </div>
          <span class="player-status-pill player-status-pill--green">On track</span>
        </div>
        <p class="player-stat-label">Overall progress</p>
        <p class="player-stat-value player-stat-value--text text-white">Improving steadily</p>
      </div>

      <div class="player-stat-card motion-item motion-soft-up" style="--motion-delay:80ms">
        <div class="player-stat-icon mb-2">
          <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </div>
        <p class="player-stat-label">Sessions completed</p>
        <p class="player-stat-value">{{ $completedCount }}</p>
        <p class="player-stat-note">{{ $completedThisMonth }} this month</p>
      </div>

      <div class="player-stat-card motion-item motion-soft-up" style="--motion-delay:160ms">
        <div class="player-stat-icon mb-2">
          <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg>
        </div>
        <p class="player-stat-label">Current priority</p>
        <p class="player-stat-value player-stat-value--text" title="{{ $focusLabel }}">{{ $focusLabel }}</p>
        <p class="player-stat-note">{{ $nextBooking?->coach?->display_name ? 'Next with '.$nextBooking->coach->display_name : ($latestPast?->coach?->display_name ? 'From '.$latestPast->coach->display_name : 'Book a session') }}</p>
      </div>

      <div class="player-stat-card motion-item motion-soft-up" style="--motion-delay:240ms">
        <div class="player-stat-icon mb-2">
          <svg class="w-[16px] h-[16px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        </div>
        <p class="player-stat-label">Open requests</p>
        <p class="player-stat-value">{{ $openRequestCount }}</p>
        <p class="player-stat-note">{{ $activeRequestCount }} active · {{ $upcomingBookings->count() }} booked</p>
      </div>
    </div>
  </div>

  <div class="max-w-[1220px] mx-auto px-6 sm:px-8 lg:px-12 xl:px-16 pb-14 lg:pb-16">
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_340px] gap-6 lg:gap-7">
      {{-- Main column --}}
      <div class="space-y-6 lg:space-y-7">
        {{-- Latest session focus --}}
        <article class="player-panel motion-item motion-soft-up">
          <div class="player-panel-head">
            <div class="flex items-center gap-3.5">
              @php
                $focusCoach = $nextBooking?->coach ?? $latestPast?->coach;
                $focusCoachName = $focusCoach?->display_name ?? 'No session yet';
                $focusInitials = collect(preg_split('/\s+/', trim($focusCoach?->display_name ?? 'CN')) ?: [])
                  ->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('') ?: 'CN';
                $focusIsUpcoming = (bool) $nextBooking;
              @endphp
              <div class="w-12 h-12 rounded-full bg-[#191615] text-white grid place-items-center text-sm font-bold ring-2 ring-brand-red/20">{{ $focusInitials }}</div>
              <div>
                <p class="player-section-kicker">{{ $focusIsUpcoming ? 'Next focus' : 'Latest session' }}</p>
                <h2 class="player-panel-title">{{ $focusCoachName }}@if(! $focusIsUpcoming && $latestPast) · {{ $latestPast->session_date?->format('F j') }}@endif</h2>
              </div>
            </div>
            <span class="inline-flex items-center px-3 py-1.5 rounded-full {{ $focusIsUpcoming ? 'bg-emerald-50 text-emerald-700' : 'bg-zinc-100 text-zinc-600' }} text-[10px] font-bold uppercase tracking-[0.08em] shrink-0">{{ $focusIsUpcoming ? 'Upcoming' : 'Complete' }}</span>
          </div>

          <div class="player-focus-box mb-5">
            <p class="text-[11px] uppercase tracking-[0.08em] text-brand-red font-bold mb-1.5">Focus of the week</p>
            <p class="text-[13px] text-[#191615] leading-[1.55] font-medium">
              @if ($focusLabel && $focusLabel !== 'Training')
                Keep building on {{ strtolower($focusLabel) }} in your next sessions.
              @else
                Book or complete a session to unlock personalized coach focus notes.
              @endif
            </p>
          </div>

          <div class="player-feedback-grid">
            <div class="player-feedback-card is-positive">
              <p class="text-[12px] font-bold text-emerald-800 uppercase tracking-[0.06em] mb-2">Upcoming</p>
              <p class="text-[13px] text-zinc-600 leading-[1.65] font-light">
                @if ($nextBooking)
                  {{ $nextBooking->session_type }} · {{ $nextBooking->whenLabel() }}
                  @if ($nextBooking->location) at {{ $nextBooking->location->name }} @endif
                @else
                  No upcoming bookings yet. Request a session to get on the field.
                @endif
              </p>
            </div>
            <div class="player-feedback-card is-focus">
              <p class="text-[12px] font-bold text-orange-800 uppercase tracking-[0.06em] mb-2">Location</p>
              <p class="text-[13px] text-zinc-600 leading-[1.65] font-light">
                {{ ($nextBooking ?? $latestPast)?->location?->name ?? 'Choose a park when you request a session' }}
              </p>
            </div>
          </div>
        </article>

        {{-- My session requests --}}
        <article id="my-requests" class="player-panel motion-item motion-soft-up" style="--motion-delay:60ms" data-player-requests>
          <div class="player-panel-head !mb-4">
            <div>
              <p class="player-section-kicker">Your requests</p>
              <h2 class="player-panel-title">My session requests</h2>
            </div>
            <a href="{{ route('request-session') }}" class="player-req-cta">+ New request</a>
          </div>

          <div class="player-req-list" data-request-list>
            @forelse ($sessionRequests as $req)
              <div
                class="player-req-card"
                data-request-card
                data-reference="{{ $req['reference'] }}"
                data-status="{{ $req['status'] }}"
              >
                <div class="player-req-card__main">
                  <div class="player-req-card__top">
                    <span class="player-req-badge player-req-badge--{{ $req['status_tone'] }}" data-request-status>
                      {{ $req['status_label'] }}
                    </span>
                    <span class="player-req-ref">{{ $req['reference'] }}</span>
                  </div>
                  <p class="player-req-card__title">{{ $req['session_type'] }}</p>
                  <p class="player-req-card__meta">
                    {{ $req['when'] }} · {{ $req['location'] }}
                  </p>
                  <p class="player-req-card__sub">
                    {{ $req['role_label'] }}
                    @if (! empty($req['waiting_label']))
                      · {{ $req['waiting_label'] }}
                    @elseif ($req['coach'])
                      · Hosted by {{ $req['coach'] }}
                    @elseif ($req['status'] === 'open')
                      · Waiting for a coach
                    @endif
                    · {{ $req['players_count'] }} {{ $req['players_count'] === 1 ? 'player' : 'players' }}
                  </p>
                </div>
                <div class="player-req-card__actions">
                  @if ($req['can_cancel'])
                    <button
                      type="button"
                      class="player-req-cancel"
                      data-cancel-request
                      data-reference="{{ $req['reference'] }}"
                    >
                      Cancel
                    </button>
                  @endif
                  <span class="player-req-posted">{{ $req['posted'] }}</span>
                </div>
              </div>
            @empty
              <div class="player-req-empty" data-request-empty>
                <p class="text-[14px] font-semibold text-[#191615]">No session requests yet</p>
                <p class="text-[13px] text-zinc-500 mt-1">Request a private or group session — it will show up here with live status.</p>
                <a href="{{ route('request-session') }}" class="player-req-cta mt-4 inline-flex">Request a session</a>
              </div>
            @endforelse
          </div>
        </article>

        {{-- Skills --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:100ms">
          <div class="player-panel-head !mb-4 !pb-0 !border-0">
            <div>
              <p class="player-section-kicker">Development profile</p>
              <h2 class="player-panel-title">Skill progress</h2>
            </div>
            <span class="text-[12px] text-zinc-400 font-medium">Updated Aug 4</span>
          </div>

          <div class="space-y-3 pt-2">
            @foreach ([
              ['First touch', 'green', 'Strong'],
              ['Scanning', 'yellow', 'Developing'],
              ['Passing', 'green', 'Strong'],
              ['Finishing', 'yellow', 'Developing'],
              ['Confidence', 'green', 'Strong'],
            ] as [$skill, $tone, $label])
              <div class="player-skill-row">
                <span class="text-[13px] font-semibold text-zinc-700">{{ $skill }}</span>
                <div class="player-skill-status">
                  <span class="player-skill-dot player-skill-dot--{{ $tone }}" aria-hidden="true"></span>
                  <span class="player-skill-label player-skill-label--{{ $tone }}">{{ $label }}</span>
                </div>
              </div>
            @endforeach
          </div>
          <div class="player-skill-legend">
            <span><i class="player-skill-dot player-skill-dot--green"></i> Strong</span>
            <span><i class="player-skill-dot player-skill-dot--yellow"></i> Developing</span>
            <span><i class="player-skill-dot player-skill-dot--red"></i> Needs work</span>
          </div>
        </article>

        {{-- Sessions --}}
        <article id="recent-sessions" class="player-panel motion-item motion-soft-up" style="--motion-delay:180ms">
          <div class="player-panel-head !mb-3 !pb-0 !border-0">
            <div>
              <p class="player-section-kicker">Training history</p>
              <h2 class="player-panel-title">Recent sessions</h2>
            </div>
          </div>

          <div class="player-timeline mt-2">
            @forelse ($pastBookings as $booking)
              <div class="player-timeline-item flex items-start justify-between gap-4">
                <div>
                  <p class="text-[14px] font-semibold text-[#191615]">{{ $booking->session_type ?? 'Session' }}</p>
                  <p class="text-[12px] text-zinc-500 mt-1">{{ $booking->location?->name ?? 'Training' }} · {{ $booking->coach?->display_name ?? 'Coach' }}</p>
                </div>
                <span class="text-[12px] font-medium text-zinc-400 shrink-0">{{ $booking->session_date?->format('M j') }}</span>
              </div>
            @empty
              <div class="player-timeline-item flex items-start justify-between gap-4">
                <div>
                  <p class="text-[14px] font-semibold text-[#191615]">No sessions yet</p>
                  <p class="text-[12px] text-zinc-500 mt-1">Completed bookings will appear here</p>
                </div>
              </div>
            @endforelse
            @foreach ($upcomingBookings->take(3) as $booking)
              <div class="player-timeline-item flex items-start justify-between gap-4">
                <div>
                  <p class="text-[14px] font-semibold text-[#191615]">{{ $booking->session_type ?? 'Session' }} (upcoming)</p>
                  <p class="text-[12px] text-zinc-500 mt-1">{{ $booking->location?->name ?? 'Training' }} · {{ $booking->coach?->display_name ?? 'Coach' }}</p>
                </div>
                <span class="text-[12px] font-medium text-zinc-400 shrink-0">{{ $booking->session_date?->format('M j') }}</span>
              </div>
            @endforeach
          </div>
        </article>
      </div>

      {{-- Sidebar --}}
      <aside class="space-y-6 lg:space-y-7">
        {{-- Quick action --}}
        <article class="player-panel player-panel--accent motion-item motion-soft-up">
          <p class="player-section-kicker">Book next</p>
          <h2 class="player-panel-title mb-2">Need another session?</h2>
          <p class="text-[13px] text-zinc-600 leading-[1.65] font-light mb-4">
            Create a request or join an open group. Track everything under My session requests.
          </p>
          <a href="{{ route('request-session') }}" class="player-req-cta player-req-cta--block">Request a session</a>
          @if ($openRequestCount > 0)
            <a href="#my-requests" class="block text-center text-[13px] font-semibold text-brand-red mt-3 hover:underline">
              View {{ $openRequestCount }} open request{{ $openRequestCount === 1 ? '' : 's' }}
            </a>
          @endif
        </article>

        {{-- Focus of the week --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:40ms">
          <p class="player-section-kicker">This week</p>
          <h2 class="player-panel-title mb-2">Focus of the Week</h2>
          <p class="text-[13px] text-zinc-600 leading-[1.65] font-light">
            @if ($focusLabel && $focusLabel !== 'Training')
              Priority from your schedule: {{ $focusLabel }}.
            @else
              Complete a session to unlock a personalized focus of the week.
            @endif
          </p>
        </article>

        {{-- Coach notes summary --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:80ms">
          <p class="player-section-kicker">From your coach</p>
          <h2 class="player-panel-title mb-2">Coach's Notes</h2>
          <p class="text-[13px] text-zinc-600 leading-[1.65] font-light mb-4">Strong passing weight and positive attitude. Keep working on checking both shoulders earlier.</p>
          <a href="#" class="text-[13px] font-semibold text-brand-red hover:underline">View Full Report</a>
        </article>

        {{-- Videos --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:100ms" id="videos-for-you">
          <p class="player-section-kicker">Recommended</p>
          <h2 class="player-panel-title mb-3">Videos for you</h2>
          <div class="space-y-3">
            @forelse ($sharedVideos as $video)
              <button
                type="button"
                class="player-video-card"
                data-play-video
                data-video-title="{{ $video['title'] }}"
                data-video-url="{{ $video['url'] }}"
                data-video-meta="{{ $video['meta'] }}"
                data-video-source="{{ !empty($video['is_upload']) ? 'upload' : 'url' }}"
              >
                <div
                  class="player-video-thumb{{ !empty($video['thumbnail']) ? ' has-image' : '' }}"
                  @if (!empty($video['thumbnail'])) style="background-image:url('{{ $video['thumbnail'] }}')" @endif
                  aria-hidden="true"
                >
                  <span class="player-video-thumb__play">
                    <svg class="w-4 h-4 ml-0.5" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
                  </span>
                </div>
                <div class="player-video-copy">
                  <p class="text-[13px] font-semibold text-[#191615]">{{ $video['title'] }}</p>
                  <p class="text-[11px] text-zinc-500 mt-0.5">{{ $video['meta'] }}</p>
                  <span class="player-video-play-label">Play video</span>
                </div>
              </button>
            @empty
              <div class="player-video-empty">
                <p class="text-[13px] font-semibold text-[#191615]">No videos yet</p>
                <p class="text-[12px] text-zinc-500 mt-1">When your coach shares a training clip, it will show up here.</p>
              </div>
            @endforelse
          </div>
        </article>

        {{-- Milestones --}}
        <article class="player-panel motion-item motion-soft-up" style="--motion-delay:180ms">
          <p class="player-section-kicker">Achievements</p>
          <h2 class="player-panel-title mb-3">Your milestones</h2>
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

@include('partials.video-player-modal')
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
  <script src="{{ asset('assets/js/player-dashboard.js') }}?v={{ @filemtime(public_path('assets/js/player-dashboard.js')) ?: time() }}"></script>
  <script src="{{ asset('assets/js/video-player-modal.js') }}?v={{ @filemtime(public_path('assets/js/video-player-modal.js')) ?: time() }}"></script>
@endpush
