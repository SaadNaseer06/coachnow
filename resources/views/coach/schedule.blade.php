@extends('layouts.coach')

@section('title', 'Schedule')
@section('page_title', 'My Schedule')
@section('page_subtitle', 'Working hours, sessions, and personal time — Day, Week, or Month')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/admin-schedule.css') }}">
@endpush

@section('topbar_actions')
  @php $availabilityCount = collect($availability ?? [])->count(); @endphp
  <button type="button" class="admin-btn admin-btn-ghost sched-top-btn" data-admin-modal-open="bookPlayerModal" aria-label="Book player">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
    <span class="sched-top-btn__full">Book player</span>
  </button>
  <button type="button" class="admin-btn admin-btn-primary sched-top-btn" data-admin-modal-open="availabilityModal" aria-label="Working hours">
    <span class="sched-top-btn__full">Working hours</span>
    <span class="sched-top-btn__short">Hours</span>
    @if ($availabilityCount > 0)
      <span class="admin-badge admin-badge-zinc sched-top-btn__badge">{{ $availabilityCount }}</span>
    @endif
  </button>
@endsection

@section('content')
@php
  $viewMode = $viewMode ?? 'week';
  $sessionsByDay = collect($sessions)->groupBy('day');
  $workingHoursByDow = $workingHoursByDow ?? [];
  $calStart = (int) ($calStartHour ?? 4);
  $slotCount = count($hours ?? range(4, 20)) * 2;
  $now = now();
  $dayStart = $calStart * 60;
  $dayEnd = ($calStart + count($hours ?? range(4, 20))) * 60;
  $nowMinutes = ($now->hour * 60) + $now->minute;
  $nowInRange = $nowMinutes >= $dayStart && $nowMinutes <= $dayEnd;
  $nowTop = $nowInRange ? (($nowMinutes - $dayStart) / ($dayEnd - $dayStart)) * 100 : null;
  // Prefer scroll to booked sessions / working hours — not 4am personal blocks at the top of the grid.
  $bookingScroll = collect($bookingsOnly ?? [])->min('gridStart');
  $workScroll = null;
  foreach ($workingHoursByDow as $windows) {
    foreach ($windows as $wh) {
      [$sh, $sm] = array_map('intval', explode(':', $wh['start'] ?? '16:00'));
      $gs = max(1, (int) floor(((($sh * 60) + $sm) - ($calStart * 60)) / 30) + 1);
      $workScroll = $workScroll === null ? $gs : min($workScroll, $gs);
    }
  }
  $noonRow = max(1, (int) floor(((12 * 60) - ($calStart * 60)) / 30) + 1);
  $firstGridStart = $bookingScroll ?: $workScroll ?: $noonRow;
  $openAvailabilityModal = request()->boolean('availability')
    || (session('success') && str_contains(strtolower((string) session('success')), 'availability'))
    || (session('success') && str_contains(strtolower((string) session('success')), 'working hours'));
  $bookErrorKeys = ['player_name', 'athlete_id', 'session_date', 'session_time', 'location_id', 'session_type', 'time'];
  $openBookPlayerModal = request()->boolean('book')
    || $errors->hasAny($bookErrorKeys);
  $openPersonalModal = $errors->hasAny(['title', 'block_date', 'start_time', 'end_time'])
    && ! $errors->hasAny($bookErrorKeys);
  $isOpenSlot = function (int $dow, int $slotIndex) use ($workingHoursByDow, $calStart): bool {
    $slotStart = $calStart * 60 + $slotIndex * 30;
    $slotEnd = $slotStart + 30;
    foreach ($workingHoursByDow[$dow] ?? [] as $wh) {
      [$sh, $sm] = array_map('intval', explode(':', $wh['start']));
      [$eh, $em] = array_map('intval', explode(':', $wh['end']));
      $ws = $sh * 60 + $sm;
      $we = $eh * 60 + $em;
      if ($slotStart < $we && $slotEnd > $ws) {
        return true;
      }
    }
    return false;
  };
  $slotTimeLabel = function (int $slotIndex) use ($calStart): string {
    $mins = $calStart * 60 + $slotIndex * 30;
    $h = intdiv($mins, 60);
    $m = $mins % 60;
    return sprintf('%02d:%02d', $h, $m);
  };
  $slotTimeDisplay = function (int $slotIndex) use ($calStart): string {
    $mins = $calStart * 60 + $slotIndex * 30;
    $h24 = intdiv($mins, 60);
    $m = $mins % 60;
    $suffix = $h24 >= 12 ? 'PM' : 'AM';
    $h12 = $h24 % 12;
    if ($h12 === 0) {
      $h12 = 12;
    }

    return sprintf('%d:%02d %s', $h12, $m, $suffix);
  };
  $navQuery = fn (string $view, ?string $date = null) => array_filter([
    'view' => $view,
    'date' => $date ?? ($focusDate ?? null),
    'week' => $view === 'week' ? ($weekStart ?? null) : null,
  ]);
@endphp

<div class="sched-page" data-view="{{ $viewMode }}" data-cal-start="{{ $calStart }}">
  <div class="sched-stats">
    @foreach ($summary as $tile)
      <div class="sched-stat">
        <span class="sched-stat-value">{{ $tile['value'] }}</span>
        <span class="sched-stat-label">{{ $tile['label'] }}</span>
        <span class="sched-stat-note">{{ $tile['note'] }}</span>
      </div>
    @endforeach
  </div>

  @if (($upcomingTotal ?? 0) > 0 && count($bookingsOnly ?? []) === 0 && $viewMode !== 'month')
    <div class="admin-alert admin-alert--success" role="status">
      <span class="admin-alert__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
      </span>
      <div class="admin-alert__body">
        <p class="admin-alert__label">Sessions on another week</p>
        <p class="admin-alert__text">You have {{ $upcomingTotal }} upcoming session{{ $upcomingTotal === 1 ? '' : 's' }}. Use the arrows to find them — this view has none yet.</p>
      </div>
    </div>
  @endif

  @if ($availabilityCount === 0)
    <div class="admin-alert" role="status" style="margin-bottom:1rem;border:1px solid #e5e5e5;background:#fafafa">
      <div class="admin-alert__body">
        <p class="admin-alert__label">No working hours yet</p>
        <p class="admin-alert__text">
          Set recurring weekly hours so athletes can Book Now — open times show white on the calendar.
          <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-admin-modal-open="availabilityModal" style="margin-left:6px">Working hours</button>
        </p>
      </div>
    </div>
  @endif

  <div class="sched-workspace {{ $viewMode === 'month' ? 'sched-workspace--month' : '' }}">
    <div class="sched-calendar-card">
      <div class="sched-card-header">
        <div class="sched-toolbar">
          <div class="sched-toolbar-left">
            <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" onclick="window.location='{{ $todayLink }}'">Today</button>
            <div class="sched-date-nav">
              <a href="{{ $prevLink }}" class="sched-icon-btn" aria-label="Previous">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
              </a>
              <button type="button" class="sched-date-picker" tabindex="-1">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                {{ $focusLabel ?? $weekLabel }}
              </button>
              <a href="{{ $nextLink }}" class="sched-icon-btn" aria-label="Next">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
              </a>
            </div>
            <nav class="sched-view-switch" aria-label="Calendar view">
              <a href="{{ route('coach.schedule', $navQuery('day')) }}" class="sched-view-btn {{ $viewMode === 'day' ? 'is-active' : '' }}">Day</a>
              <a href="{{ route('coach.schedule', $navQuery('week')) }}" class="sched-view-btn {{ $viewMode === 'week' ? 'is-active' : '' }}">Week</a>
              <a href="{{ route('coach.schedule', $navQuery('month')) }}" class="sched-view-btn {{ $viewMode === 'month' ? 'is-active' : '' }}">Month</a>
            </nav>
            @if ($viewMode === 'week')
              <p class="sched-mobile-hint">On phones, swipe the grid sideways — or use <a href="{{ route('coach.schedule', $navQuery('day')) }}">Day</a> to tap slots easily.</p>
            @endif
            @if ($viewMode !== 'month')
              <span class="sched-session-count">{{ count($bookingsOnly ?? []) }} session{{ count($bookingsOnly ?? []) === 1 ? '' : 's' }}</span>
            @endif
          </div>
        </div>
      </div>

      @if ($viewMode === 'month')
        <div class="sched-month" role="grid" aria-label="Month overview">
          <div class="sched-month-head">
            @foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $label)
              <div class="sched-month-dow">{{ $label }}</div>
            @endforeach
          </div>
          <div class="sched-month-grid">
            @foreach ($monthCells ?? [] as $cell)
              <a
                href="{{ route('coach.schedule', ['view' => 'day', 'date' => $cell['iso']]) }}"
                class="sched-month-cell {{ empty($cell['in_month']) ? 'is-outside' : '' }} {{ !empty($cell['today']) ? 'is-today' : '' }}"
              >
                <span class="sched-month-num">{{ $cell['num'] }}</span>
                <span class="sched-month-dots" aria-hidden="true">
                  @if (($cell['booking_count'] ?? 0) > 0)
                    <i class="sched-month-dot sched-month-dot--session"></i>
                  @endif
                  @if (($cell['personal_count'] ?? 0) > 0)
                    <i class="sched-month-dot sched-month-dot--personal"></i>
                  @endif
                </span>
                @php
                  $monthBookings = (int) ($cell['booking_count'] ?? 0);
                  $monthPersonal = (int) ($cell['personal_count'] ?? 0);
                @endphp
                @if ($monthBookings + $monthPersonal > 0)
                  <span class="sched-month-counts">
                    @if ($monthBookings > 0)
                      {{ $monthBookings }} {{ $monthBookings === 1 ? 'session' : 'sessions' }}
                    @endif
                    @if ($monthBookings > 0 && $monthPersonal > 0)
                      ·
                    @endif
                    @if ($monthPersonal > 0)
                      {{ $monthPersonal }} personal
                    @endif
                  </span>
                @endif
              </a>
            @endforeach
          </div>
        </div>
      @else
        <div
          class="sched-calendar-scroll {{ $viewMode === 'day' ? 'sched-calendar-scroll--day' : '' }}"
          data-scroll-to-row="{{ $firstGridStart ?: '' }}"
          data-row-height="{{ $viewMode === 'day' ? '64' : '48' }}"
        >
          <div class="sched-calendar {{ $viewMode === 'day' ? 'sched-calendar--day' : '' }}" style="--sched-cols: {{ count($days) }}">
            <div class="sched-calendar-corner"></div>

            <div class="sched-calendar-days" style="grid-template-columns: repeat({{ count($days) }}, minmax({{ $viewMode === 'day' ? '160px' : '100px' }}, 1fr))">
              @foreach ($days as $day)
                <a
                  href="{{ route('coach.schedule', ['view' => 'day', 'date' => $day['iso'] ?? $focusDate]) }}"
                  class="sched-calendar-dayhead {{ !empty($day['today']) ? 'is-today' : '' }}"
                >
                  <span class="sched-calendar-daylabel">{{ $day['name'] }}</span>
                  <span class="sched-calendar-daynum">{{ $day['num'] }}</span>
                </a>
              @endforeach
            </div>

            <div class="sched-calendar-times">
              @foreach ($hours as $hour)
                <div class="sched-calendar-time">{{ $hour <= 12 ? $hour : ($hour - 12) }} {{ $hour < 12 ? 'AM' : 'PM' }}</div>
              @endforeach
            </div>

            <div class="sched-calendar-grid" style="grid-template-columns: repeat({{ count($days) }}, minmax({{ $viewMode === 'day' ? '160px' : '100px' }}, 1fr))">
              @foreach ($days as $dayIndex => $day)
                @php $dow = (int) ($day['dow'] ?? 0); @endphp
                <div class="sched-calendar-column {{ !empty($day['today']) ? 'is-today' : '' }}" data-date="{{ $day['iso'] ?? '' }}" data-dow="{{ $dow }}">
                  @for ($slot = 0; $slot < $slotCount; $slot++)
                    @php $open = $isOpenSlot($dow, $slot); @endphp
                    <button
                      type="button"
                      class="sched-calendar-slot {{ $open ? 'is-open-hours' : 'is-closed-hours' }} {{ $slot % 2 === 0 ? 'is-hour-start' : 'is-half' }}"
                      data-slot-click
                      data-date="{{ $day['iso'] ?? '' }}"
                      data-time="{{ $slotTimeLabel($slot) }}"
                      data-time-label="{{ $slotTimeDisplay($slot) }}"
                      data-open="{{ $open ? '1' : '0' }}"
                      title="{{ $slotTimeDisplay($slot) }} — click to add"
                      aria-label="{{ ($day['iso'] ?? '').' '.$slotTimeDisplay($slot) }}"
                    ></button>
                  @endfor

                  @if ($nowInRange && !empty($day['today']) && $nowTop !== null)
                    <div class="sched-now-line" style="top: {{ number_format($nowTop, 2, '.', '') }}%;">
                      <span class="sched-now-label">{{ now()->format('g:i A') }}</span>
                    </div>
                  @endif

                  @foreach ($sessionsByDay->get($dayIndex, collect()) as $session)
                    @php
                      $eventTip = collect([
                        $session['time_label'] ?? null,
                        $session['title'] ?? null,
                        $session['type'] ?? null,
                        (($session['duration'] ?? null) ? ($session['duration'].' min') : null),
                        $session['location'] ?? null,
                      ])->filter()->implode(' · ');
                      $tone = ($session['kind'] ?? '') === 'personal' ? 'personal' : ($session['tone'] ?? 'green');
                    @endphp
                    <article
                      class="sched-event sched-event--{{ $tone }} {{ !empty($session['allDay']) ? 'is-all-day' : '' }}"
                      style="grid-row: {{ $session['gridStart'] }} / {{ $session['gridEnd'] }};"
                      title="{{ $eventTip }}"
                      @if (($session['kind'] ?? '') === 'personal')
                        data-personal-id="{{ $session['id'] }}"
                      @endif
                    >
                      <p class="sched-event-title">
                        <span class="sched-event-time">{{ $session['time_label'] ?? '' }}</span>
                        {{ $session['title'] }}
                      </p>
                      <p class="sched-event-type">{{ $session['type'] }}</p>
                      @if (($session['kind'] ?? '') === 'personal')
                        <form method="POST" action="{{ route('coach.schedule.blocks.destroy', $session['id']) }}" class="sched-event-remove" onsubmit="return confirm('Remove this personal block?')">
                          @csrf
                          @method('DELETE')
                          <input type="hidden" name="view" value="{{ $viewMode }}">
                          <input type="hidden" name="week" value="{{ $weekStart ?? '' }}">
                          <input type="hidden" name="date" value="{{ $focusDate ?? '' }}">
                          <button type="submit" aria-label="Remove personal time">&times;</button>
                        </form>
                      @endif
                    </article>
                  @endforeach
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="sched-calendar-legend">
          <span><i class="sched-legend-dot sched-legend-dot--open"></i> Working hours</span>
          <span><i class="sched-legend-dot sched-legend-dot--closed"></i> Closed</span>
          <span><i class="sched-legend-dot sched-legend-dot--green"></i> Private</span>
          <span><i class="sched-legend-dot sched-legend-dot--yellow"></i> Small Group</span>
          <span><i class="sched-legend-dot sched-legend-dot--purple"></i> Group</span>
          <span><i class="sched-legend-dot sched-legend-dot--orange"></i> Assessment</span>
          <span><i class="sched-legend-dot sched-legend-dot--personal"></i> Personal</span>
        </div>
      @endif
    </div>

    @if ($viewMode !== 'month')
      <aside class="sched-agenda" aria-label="Agenda">
        <div class="sched-agenda-header">
          <h2 class="sched-agenda-title">{{ $viewMode === 'day' ? 'This day' : 'This week' }}</h2>
          <p class="sched-agenda-sub">
            @if (count($sessions) === 0)
              Nothing scheduled
            @else
              {{ count($sessions) }} item{{ count($sessions) === 1 ? '' : 's' }} — full details
            @endif
          </p>
        </div>

        @if (count($sessions) > 0)
          <ul class="sched-agenda-list">
            @foreach ($sessions as $session)
              @php $tone = ($session['kind'] ?? '') === 'personal' ? 'personal' : ($session['tone'] ?? 'green'); @endphp
              <li class="sched-agenda-item">
                <span class="sched-agenda-tone sched-agenda-tone--{{ $tone }}" aria-hidden="true"></span>
                <div class="sched-agenda-content">
                  <div class="sched-agenda-when">
                    <strong>{{ $session['date_label'] ?? '' }}</strong>
                    <span>{{ $session['time_label'] ?? '' }}</span>
                  </div>
                  <p class="sched-agenda-name">{{ $session['title'] }}</p>
                  <p class="sched-agenda-detail">
                    {{ $session['type'] }}
                    @if (!empty($session['duration'])) · {{ $session['duration'] }} min @endif
                    @if (! empty($session['location']))
                      · {{ $session['location'] }}
                    @endif
                  </p>
                </div>
              </li>
            @endforeach
          </ul>
        @else
          <p class="sched-agenda-empty">Click a white or gray slot to book a player, block personal time, or edit working hours.</p>
        @endif
      </aside>
    @endif
  </div>
</div>

{{-- Slot action sheet --}}
<div class="admin-modal" id="slotActionModal" aria-hidden="true">
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__panel sched-action-sheet" role="dialog" aria-modal="true" aria-labelledby="slotActionTitle">
    <div class="admin-modal__header">
      <div>
        <h2 id="slotActionTitle">Add to calendar</h2>
        <p id="slotActionSub">Choose what to create at this time.</p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
    </div>
    <div class="admin-modal__body sched-action-list">
      <button type="button" class="sched-action-btn" id="slotActionSession">
        <strong>New session</strong>
        <span>Book a player at this time</span>
      </button>
      <button type="button" class="sched-action-btn" id="slotActionPersonal">
        <strong>Personal task</strong>
        <span>Block time so athletes can’t Book Now</span>
      </button>
      <button type="button" class="sched-action-btn" id="slotActionHours">
        <strong>Edit working hours</strong>
        <span>Change recurring weekly hours</span>
      </button>
    </div>
  </div>
</div>

{{-- Personal task modal --}}
@php
  $openPersonalClass = $openPersonalModal ? ' is-open' : '';
  $openPersonalHidden = $openPersonalModal ? 'false' : 'true';
@endphp
<div class="admin-modal{{ $openPersonalClass }}" id="personalBlockModal" aria-hidden="{{ $openPersonalHidden }}">
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="personalBlockTitle" style="max-width:480px;width:min(480px, calc(100vw - 2rem))">
    <div class="admin-modal__header">
      <div>
        <h2 id="personalBlockTitle">Personal task</h2>
        <p>Blocks Book Now for athletes on this date and time.</p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="POST" action="{{ route('coach.schedule.blocks.store') }}" class="admin-modal__body" id="personalBlockForm">
      @csrf
      <input type="hidden" name="view" value="{{ $viewMode }}">
      <input type="hidden" name="week" value="{{ $weekStart ?? '' }}">
      <input type="hidden" name="date" value="{{ $focusDate ?? '' }}">

      @if ($openPersonalModal)
        <div class="admin-alert admin-alert--error" role="alert" style="margin-bottom:14px">
          <div class="admin-alert__body">
            <ul class="admin-alert__list">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      @endif

      <div class="admin-form-grid">
        <label class="admin-field admin-field--full">
          <span>Title</span>
          <input class="admin-input" type="text" name="title" id="personalTitle" value="{{ old('title', 'Personal time') }}" maxlength="160" placeholder="Personal time">
        </label>
        <label class="admin-field">
          <span>Date</span>
          <input class="admin-input" type="date" name="block_date" id="personalDate" value="{{ old('block_date', $focusDate ?? now()->toDateString()) }}" required>
        </label>
        <label class="admin-field">
          <span>Start</span>
          <input class="admin-input" type="time" name="start_time" id="personalStart" value="{{ old('start_time', '16:00') }}" required>
        </label>
        <label class="admin-field">
          <span>End</span>
          <input class="admin-input" type="time" name="end_time" id="personalEnd" value="{{ old('end_time', '17:00') }}" required>
        </label>
      </div>

      <div class="admin-modal__footer" style="margin-top:1rem;padding:0;border:0">
        <button type="button" class="admin-btn admin-btn-ghost" data-admin-modal-close>Cancel</button>
        <button type="submit" class="admin-btn admin-btn-primary" data-loading-text="Saving…">Save block</button>
      </div>
    </form>
  </div>
</div>

@php
  $openBookClass = $openBookPlayerModal ? ' is-open' : '';
  $openBookHidden = $openBookPlayerModal ? 'false' : 'true';
@endphp
<div class="admin-modal{{ $openBookClass }}" id="bookPlayerModal" aria-hidden="{{ $openBookHidden }}">
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="bookPlayerTitle" style="max-width:560px;width:min(560px, calc(100vw - 2rem))">
    <div class="admin-modal__header">
      <div>
        <h2 id="bookPlayerTitle">Book a player</h2>
        <p>Add a client to your calendar — no need to wait for them to book online.</p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="POST" action="{{ route('coach.schedule.sessions.store') }}" class="admin-modal__body" id="bookPlayerForm">
      @csrf
      <input type="hidden" name="week" value="{{ $weekStart ?? '' }}">
      <input type="hidden" name="view" value="{{ $viewMode }}">
      <input type="hidden" name="date" value="{{ $focusDate ?? '' }}">

      @if ($errors->hasAny(['player_name', 'athlete_id', 'session_date', 'session_time', 'location_id', 'session_type', 'time']))
        <div class="admin-alert admin-alert--error" role="alert" style="margin-bottom:14px">
          <div class="admin-alert__body">
            <ul class="admin-alert__list">
              @foreach ($errors->get('player_name') as $error)<li>{{ $error }}</li>@endforeach
              @foreach ($errors->get('athlete_id') as $error)<li>{{ $error }}</li>@endforeach
              @foreach ($errors->get('session_date') as $error)<li>{{ $error }}</li>@endforeach
              @foreach ($errors->get('session_time') as $error)<li>{{ $error }}</li>@endforeach
              @foreach ($errors->get('time') as $error)<li>{{ $error }}</li>@endforeach
              @foreach ($errors->get('location_id') as $error)<li>{{ $error }}</li>@endforeach
              @foreach ($errors->get('session_type') as $error)<li>{{ $error }}</li>@endforeach
            </ul>
          </div>
        </div>
      @endif

      <div class="admin-form-grid">
        <label class="admin-field admin-field--full">
          <span>Roster player (optional)</span>
          <select class="admin-select" name="athlete_id" id="bookPlayerRoster">
            <option value="">— New / walk-in client —</option>
            @foreach ($roster ?? [] as $player)
              @if (! empty($player['athlete_id']))
                <option value="{{ $player['athlete_id'] }}" data-name="{{ $player['name'] }}" @selected((string) old('athlete_id') === (string) $player['athlete_id'])>
                  {{ $player['name'] }}{{ ! empty($player['sessions']) ? ' · '.$player['sessions'].' sessions' : '' }}
                </option>
              @else
                <option value="" data-name="{{ $player['name'] }}" data-guest="1">
                  {{ $player['name'] }} (no account){{ ! empty($player['sessions']) ? ' · '.$player['sessions'].' sessions' : '' }}
                </option>
              @endif
            @endforeach
          </select>
        </label>

        <label class="admin-field admin-field--full" id="bookPlayerNameField">
          <span>Client name</span>
          <input class="admin-input" type="text" name="player_name" id="bookPlayerName" value="{{ old('player_name') }}" maxlength="120" placeholder="e.g. Jamie Underwood">
        </label>

        <label class="admin-field">
          <span>Date</span>
          <input class="admin-input" type="date" name="session_date" id="bookPlayerDate" value="{{ old('session_date', $focusDate ?? now()->toDateString()) }}" required min="{{ now()->toDateString() }}">
        </label>
        <label class="admin-field">
          <span>Time</span>
          <input class="admin-input" type="time" name="session_time" id="bookPlayerTime" value="{{ old('session_time', '16:00') }}" required>
        </label>
        <label class="admin-field">
          <span>Duration (min)</span>
          <input class="admin-input" type="number" name="duration_minutes" value="{{ old('duration_minutes', 60) }}" min="30" max="180" step="15">
        </label>
        <label class="admin-field">
          <span>Session type</span>
          <select class="admin-select" name="session_type" required>
            @foreach ($sessionTypes ?? ['Private 1-on-1'] as $type)
              <option value="{{ $type }}" @selected(old('session_type', 'Private 1-on-1') === $type)>{{ $type }}</option>
            @endforeach
          </select>
        </label>
        <label class="admin-field admin-field--full">
          <span>Field / park</span>
          <select class="admin-select" name="location_id" required>
            <option value="">Select park…</option>
            @foreach ($locations ?? [] as $location)
              <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>
                {{ $location->name }}{{ $location->area ? ' · '.$location->area : '' }}
              </option>
            @endforeach
          </select>
        </label>
      </div>

      <div class="admin-modal__footer" style="margin-top:1rem;padding:0;border:0">
        <button type="button" class="admin-btn admin-btn-ghost" data-admin-modal-close>Cancel</button>
        <button type="submit" class="admin-btn admin-btn-primary" data-loading-text="Booking…">Confirm session</button>
      </div>
    </form>
  </div>
</div>

@php
  $openAvailabilityClass = $openAvailabilityModal ? ' is-open' : '';
  $openAvailabilityHidden = $openAvailabilityModal ? 'false' : 'true';
  $dayLabels = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',0=>'Sunday'];
  $availabilitySorted = collect($availability ?? [])
    ->sortBy(function ($block) {
      $day = (int) $block->day_of_week;
      $order = $day === 0 ? 7 : $day;

      return sprintf('%d-%s', $order, (string) $block->start_time);
    })
    ->values();
@endphp
<div class="admin-modal{{ $openAvailabilityClass }}" id="availabilityModal" aria-hidden="{{ $openAvailabilityHidden }}">
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="availabilityModalTitle" style="max-width:900px;width:min(900px, calc(100vw - 2rem))">
    <div class="admin-modal__header">
      <div>
        <h2 id="availabilityModalTitle">Working hours</h2>
        <p>Set recurring weekly hours — white on the calendar means athletes can Book Now.</p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
    </div>
    <div class="admin-modal__body">
      @if ($errors->any() && ! $errors->hasAny($bookErrorKeys) && ! $openPersonalModal)
        <div class="admin-alert admin-alert--error" role="alert" style="margin-bottom:14px">
          <div class="admin-alert__body">
            <ul class="admin-alert__list">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      @endif

      <form
        method="POST"
        id="availabilityForm"
        action="{{ route('coach.schedule.availability.store', ['week' => $weekStart ?? '']) }}"
        class="avail-form"
        data-store-url="{{ route('coach.schedule.availability.store', ['week' => $weekStart ?? '']) }}"
        data-update-base="{{ url('/coach/schedule/availability') }}"
        data-week="{{ $weekStart ?? '' }}"
      >
        @csrf
        <input type="hidden" name="_method" id="availMethod" value="POST" disabled>
        <input type="hidden" name="week" value="{{ $weekStart ?? '' }}">

        <div class="avail-form__head">
          <strong id="availFormTitle">Add working hours</strong>
          <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" id="availCancelEdit" hidden>Cancel edit</button>
        </div>

        <div class="avail-days" id="availDaysWrap">
          <div class="avail-days__label">
            <span>Days</span>
            <div class="avail-days__presets" id="availDayPresets">
              <button type="button" class="avail-preset" data-days="1,2,3,4,5">Weekdays</button>
              <button type="button" class="avail-preset" data-days="0,6">Weekend</button>
              <button type="button" class="avail-preset" data-days="0,1,2,3,4,5,6">All</button>
            </div>
          </div>
          <div class="avail-days__chips" role="group" aria-label="Select days">
            @php
              $dayChips = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'];
              $oldDays = collect(old('days', [1, 2, 3, 4, 5]))->map(fn ($d) => (int) $d)->all();
            @endphp
            @foreach ($dayChips as $dow => $short)
              <label class="avail-day-chip">
                <input
                  type="checkbox"
                  name="days[]"
                  value="{{ $dow }}"
                  class="avail-day-input"
                  @checked(in_array($dow, $oldDays, true))
                >
                <span>{{ $short }}</span>
              </label>
            @endforeach
          </div>
          <input type="hidden" name="day_of_week" id="availDay" value="{{ old('day_of_week', 1) }}" disabled>
          <p class="avail-days__hint" id="availDaysHint">Select one or more days — same hours apply to each.</p>
        </div>

        <div class="avail-form__grid">
          <label class="admin-field">
            <span>Start</span>
            <input class="admin-input" type="time" name="start_time" id="availStart" value="16:00" required>
          </label>
          <label class="admin-field">
            <span>End</span>
            <input class="admin-input" type="time" name="end_time" id="availEnd" value="19:00" required>
          </label>
          <label class="admin-field">
            <span>Slot (min)</span>
            <input class="admin-input" type="number" name="duration_minutes" id="availDuration" value="60" min="30" max="180" step="15">
          </label>
          <label class="admin-field avail-form__park">
            <span>Field / park</span>
            <select class="admin-select" name="location_id" id="availLocation" required>
              <option value="">Select park…</option>
              @foreach ($locations ?? [] as $location)
                <option value="{{ $location->id }}">{{ $location->name }}{{ $location->area ? ' · '.$location->area : '' }}</option>
              @endforeach
            </select>
          </label>
          <div class="avail-form__submit">
            <button type="submit" class="admin-btn admin-btn-primary" id="availSubmitBtn">Add hours</button>
          </div>
        </div>
      </form>

      <div class="avail-list-head">
        <strong>Your weekly hours</strong>
        <span>{{ $availabilitySorted->count() }} total</span>
      </div>

      <div class="avail-list-wrap">
        @forelse ($availabilitySorted as $block)
          @php
            $startVal = \Illuminate\Support\Carbon::parse($block->start_time)->format('H:i');
            $endVal = \Illuminate\Support\Carbon::parse($block->end_time)->format('H:i');
          @endphp
          <div
            class="avail-row"
            data-id="{{ $block->id }}"
            data-day="{{ (int) $block->day_of_week }}"
            data-start="{{ $startVal }}"
            data-end="{{ $endVal }}"
            data-duration="{{ (int) $block->duration_minutes }}"
            data-location="{{ (int) $block->location_id }}"
          >
            <div class="avail-row__main">
              <strong>{{ $dayLabels[(int) $block->day_of_week] ?? $block->dayName() }}</strong>
              <span>{{ $block->startTimeLabel() }} – {{ $block->endTimeLabel() }}</span>
              <span class="avail-row__meta">{{ $block->duration_minutes }} min · {{ $block->location?->name ?? 'Field TBD' }}</span>
            </div>
            <div class="avail-row__actions">
              <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-avail-edit>Edit</button>
              <form method="POST" action="{{ route('coach.schedule.availability.destroy', ['slot' => $block->id, 'week' => $weekStart ?? '']) }}" onsubmit="return confirm('Remove these working hours?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="week" value="{{ $weekStart ?? '' }}">
                <button type="submit" class="admin-btn admin-btn-ghost admin-btn-sm">Remove</button>
              </form>
            </div>
          </div>
        @empty
          <p class="avail-list-empty">No working hours yet. Add Mon–Fri (or any days) above.</p>
        @endforelse
      </div>

      <p class="avail-note">
        Open marketplace requests need CoachNow Plus. Direct Book works on every active plan.
      </p>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  .avail-form {
    margin-bottom: 1.15rem;
    padding-bottom: 1.15rem;
    border-bottom: 1px solid #ececeb;
  }
  .avail-form__head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 10px;
  }
  .avail-form__head strong {
    font-size: 13px;
    font-weight: 700;
    color: #191615;
  }
  .avail-form__grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    align-items: end;
  }
  .avail-days {
    margin-bottom: 12px;
  }
  .avail-days__label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    margin-bottom: 8px;
    flex-wrap: wrap;
  }
  .avail-days__label > span {
    font-size: 12px;
    font-weight: 700;
    color: #3f3f46;
  }
  .avail-days__presets {
    display: flex;
    gap: 4px;
  }
  .avail-preset {
    height: 26px;
    padding: 0 10px;
    border-radius: 999px;
    border: 1px solid #e4e4e7;
    background: #fff;
    font-size: 11px;
    font-weight: 700;
    color: #52525b;
    cursor: pointer;
  }
  .avail-preset:hover {
    border-color: #da020c;
    color: #da020c;
  }
  .avail-days__chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }
  .avail-day-chip {
    position: relative;
    cursor: pointer;
    margin: 0;
  }
  .avail-day-chip input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
  }
  .avail-day-chip span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    height: 36px;
    padding: 0 10px;
    border-radius: 10px;
    border: 1px solid #e4e4e7;
    background: #fff;
    font-size: 12px;
    font-weight: 700;
    color: #3f3f46;
    transition: background 0.12s ease, border-color 0.12s ease, color 0.12s ease;
  }
  .avail-day-chip input:checked + span {
    background: #191615;
    border-color: #191615;
    color: #fff;
  }
  .avail-day-chip input:focus-visible + span {
    outline: 2px solid rgba(218, 2, 12, 0.35);
    outline-offset: 2px;
  }
  .avail-days.is-edit-mode .avail-days__presets {
    display: none;
  }
  .avail-days__hint {
    margin: 8px 0 0;
    font-size: 11px;
    color: #71717a;
  }
  .avail-form__park {
    grid-column: 1 / span 3;
  }
  .avail-form__submit {
    display: flex;
    align-items: end;
  }
  .avail-form__submit .admin-btn {
    width: 100%;
  }
  .avail-list-head {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    margin-bottom: 8px;
  }
  .avail-list-head strong {
    font-size: 13px;
    font-weight: 700;
  }
  .avail-list-head span {
    font-size: 11px;
    color: #71717a;
  }
  .avail-list-wrap {
    max-height: 280px;
    overflow: auto;
    border: 1px solid #ececeb;
    border-radius: 12px;
    background: #fafafa;
  }
  .avail-row {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px;
    padding: 12px 16px;
    border-bottom: 1px solid #ececeb;
    background: #fff;
  }
  .avail-row:last-child {
    border-bottom: 0;
  }
  .avail-row.is-editing {
    outline: 2px solid rgba(218, 2, 12, 0.25);
    outline-offset: -2px;
  }
  .avail-row__main {
    min-width: 0;
    display: grid;
    grid-template-columns: 110px minmax(160px, 220px) minmax(0, 1fr);
    gap: 12px;
    align-items: center;
  }
  .avail-row__main strong {
    font-size: 13px;
    color: #191615;
  }
  .avail-row__main span {
    font-size: 13px;
    color: #3f3f46;
  }
  .avail-row__meta {
    color: #71717a !important;
  }
  .avail-row__actions {
    display: flex;
    gap: 6px;
    flex-shrink: 0;
  }
  .avail-list-empty {
    margin: 0;
    padding: 18px 14px;
    font-size: 13px;
    color: #71717a;
    text-align: center;
  }
  .avail-note {
    margin: 12px 0 0;
    font-size: 12px;
    color: #71717a;
  }
  @media (max-width: 640px) {
    .avail-form__grid {
      grid-template-columns: 1fr 1fr;
    }
    .avail-form__park,
    .avail-form__submit {
      grid-column: 1 / -1;
    }
    .avail-row {
      grid-template-columns: 1fr;
    }
    .avail-row__main {
      grid-template-columns: 1fr;
      gap: 2px;
    }
    .avail-row__actions {
      justify-content: flex-end;
    }
  }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/admin.js') }}?v={{ @filemtime(public_path('assets/js/admin.js')) ?: time() }}"></script>
<script>
  (function () {
    // Phones: default to Day view (easier tapping). Keep Week/Month if the user chose them.
    try {
      const params = new URLSearchParams(window.location.search);
      const narrow = window.matchMedia('(max-width: 768px)').matches;
      if (narrow && !params.has('view')) {
        params.set('view', 'day');
        if (!params.has('date') && !params.has('week')) {
          params.set('date', '{{ $focusDate ?? now()->toDateString() }}');
        }
        window.location.replace(window.location.pathname + '?' + params.toString());
        return;
      }
    } catch (e) { /* ignore */ }

    const scroller = document.querySelector('.sched-calendar-scroll[data-scroll-to-row]');
    if (scroller) {
      const row = parseInt(scroller.dataset.scrollToRow || '', 10);
      const rowHeight = parseInt(scroller.dataset.rowHeight || '36', 10);
      if (row && row >= 1) {
        const top = Math.max(0, (row - 2) * rowHeight);
        requestAnimationFrame(() => { scroller.scrollTop = top; });
      }
    }

    // Touch: show time chip while pressing a slot
    document.querySelectorAll('[data-slot-click]').forEach((btn) => {
      btn.addEventListener('touchstart', () => {
        document.querySelectorAll('[data-slot-click].is-pressing').forEach((el) => el.classList.remove('is-pressing'));
        btn.classList.add('is-pressing');
      }, { passive: true });
      btn.addEventListener('touchend', () => {
        setTimeout(() => btn.classList.remove('is-pressing'), 180);
      }, { passive: true });
      btn.addEventListener('touchcancel', () => btn.classList.remove('is-pressing'), { passive: true });
    });

    const openModal = (id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.add('is-open');
      el.setAttribute('aria-hidden', 'false');
      document.body.classList.add('admin-modal-open');
    };
    const closeModal = (id) => {
      const el = document.getElementById(id);
      if (!el) return;
      el.classList.remove('is-open');
      el.setAttribute('aria-hidden', 'true');
      if (!document.querySelector('.admin-modal.is-open')) {
        document.body.classList.remove('admin-modal-open');
      }
    };

    let pendingSlot = { date: '', time: '', timeLabel: '' };

    const formatSub = (date, time, timeLabel) => {
      if (!date || !time) return 'Choose what to create at this time.';
      const nice = timeLabel || time;
      try {
        const d = new Date(date + 'T12:00:00');
        const day = d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' });
        return day + ' · ' + nice;
      } catch (e) {
        return date + ' · ' + nice;
      }
    };

    document.querySelectorAll('[data-slot-click]').forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        pendingSlot = {
          date: btn.dataset.date || '',
          time: btn.dataset.time || '',
          timeLabel: btn.dataset.timeLabel || '',
        };
        const sub = document.getElementById('slotActionSub');
        if (sub) sub.textContent = formatSub(pendingSlot.date, pendingSlot.time, pendingSlot.timeLabel);
        openModal('slotActionModal');
      });
    });

    const addHour = (time) => {
      const [h, m] = (time || '16:00').split(':').map((n) => parseInt(n, 10) || 0);
      const endH = Math.min(23, h + 1);
      return String(endH).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    };

    document.getElementById('slotActionSession')?.addEventListener('click', () => {
      closeModal('slotActionModal');
      const dateEl = document.getElementById('bookPlayerDate');
      const timeEl = document.getElementById('bookPlayerTime');
      if (dateEl && pendingSlot.date) dateEl.value = pendingSlot.date;
      if (timeEl && pendingSlot.time) timeEl.value = pendingSlot.time;
      openModal('bookPlayerModal');
    });

    document.getElementById('slotActionPersonal')?.addEventListener('click', () => {
      closeModal('slotActionModal');
      const dateEl = document.getElementById('personalDate');
      const startEl = document.getElementById('personalStart');
      const endEl = document.getElementById('personalEnd');
      const titleEl = document.getElementById('personalTitle');
      if (dateEl && pendingSlot.date) dateEl.value = pendingSlot.date;
      if (startEl && pendingSlot.time) startEl.value = pendingSlot.time;
      if (endEl && pendingSlot.time) endEl.value = addHour(pendingSlot.time);
      if (titleEl && !titleEl.value) titleEl.value = 'Personal time';
      openModal('personalBlockModal');
    });

    document.getElementById('slotActionHours')?.addEventListener('click', () => {
      closeModal('slotActionModal');
      if (pendingSlot.date) {
        const d = new Date(pendingSlot.date + 'T12:00:00');
        const dow = d.getDay();
        if (typeof window.__setAvailDays === 'function') {
          window.__setAvailDays([dow], false);
        }
        if (pendingSlot.time) {
          const startEl = document.getElementById('availStart');
          const endEl = document.getElementById('availEnd');
          if (startEl) startEl.value = pendingSlot.time;
          if (endEl) endEl.value = addHour(pendingSlot.time);
        }
      }
      openModal('availabilityModal');
    });

    const form = document.getElementById('availabilityForm');
    if (form) {
      const methodInput = document.getElementById('availMethod');
      const titleEl = document.getElementById('availFormTitle');
      const submitBtn = document.getElementById('availSubmitBtn');
      const cancelBtn = document.getElementById('availCancelEdit');
      const dayHidden = document.getElementById('availDay');
      const daysWrap = document.getElementById('availDaysWrap');
      const daysHint = document.getElementById('availDaysHint');
      const dayInputs = () => Array.from(form.querySelectorAll('.avail-day-input'));
      const startEl = document.getElementById('availStart');
      const endEl = document.getElementById('availEnd');
      const durationEl = document.getElementById('availDuration');
      const locationEl = document.getElementById('availLocation');
      const storeUrl = form.dataset.storeUrl;
      const updateBase = form.dataset.updateBase;
      const week = form.dataset.week || '';
      let editMode = false;

      const setAvailDays = (days, single) => {
        const wanted = new Set((days || []).map((d) => String(d)));
        dayInputs().forEach((input) => {
          input.checked = wanted.has(String(input.value));
        });
        if (dayHidden && days.length) {
          dayHidden.value = String(days[0]);
        }
        // single flag reserved for callers; exclusivity handled in change listener
        void single;
      };
      window.__setAvailDays = setAvailDays;

      const syncDayFieldMode = () => {
        dayInputs().forEach((input) => {
          input.disabled = false;
          input.name = editMode ? '' : 'days[]';
        });
        if (dayHidden) {
          dayHidden.disabled = !editMode;
          dayHidden.name = editMode ? 'day_of_week' : '';
        }
        daysWrap?.classList.toggle('is-edit-mode', editMode);
        if (daysHint) {
          daysHint.textContent = editMode
            ? 'Editing one day — pick a different day to move this block.'
            : 'Select one or more days — same hours apply to each.';
        }
      };

      document.getElementById('availDayPresets')?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-days]');
        if (!btn || editMode) return;
        const days = String(btn.dataset.days || '').split(',').filter(Boolean);
        setAvailDays(days, false);
      });

      daysWrap?.addEventListener('change', (e) => {
        const input = e.target.closest('.avail-day-input');
        if (!input) return;
        if (editMode) {
          setAvailDays([input.value], true);
          if (dayHidden) dayHidden.value = input.value;
        }
      });

      form.addEventListener('submit', (e) => {
        if (editMode) return;
        const selected = dayInputs().filter((i) => i.checked);
        if (selected.length === 0) {
          e.preventDefault();
          alert('Pick at least one day.');
        }
      });

      const clearEdit = () => {
        editMode = false;
        form.action = storeUrl;
        methodInput.disabled = true;
        methodInput.value = 'POST';
        titleEl.textContent = 'Add working hours';
        submitBtn.textContent = 'Add hours';
        cancelBtn.hidden = true;
        setAvailDays([1, 2, 3, 4, 5], false);
        syncDayFieldMode();
        startEl.value = '16:00';
        endEl.value = '19:00';
        durationEl.value = '60';
        locationEl.value = '';
        document.querySelectorAll('.avail-row.is-editing').forEach((el) => el.classList.remove('is-editing'));
      };

      cancelBtn?.addEventListener('click', clearEdit);
      syncDayFieldMode();

      document.querySelectorAll('[data-avail-edit]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const row = btn.closest('.avail-row');
          if (!row) return;
          document.querySelectorAll('.avail-row.is-editing').forEach((el) => el.classList.remove('is-editing'));
          row.classList.add('is-editing');
          editMode = true;
          setAvailDays([row.dataset.day], true);
          syncDayFieldMode();
          if (dayHidden) dayHidden.value = row.dataset.day;
          startEl.value = row.dataset.start;
          endEl.value = row.dataset.end;
          durationEl.value = row.dataset.duration;
          locationEl.value = row.dataset.location;
          methodInput.disabled = false;
          methodInput.value = 'PATCH';
          form.action = `${updateBase}/${row.dataset.id}${week ? '?week=' + encodeURIComponent(week) : ''}`;
          titleEl.textContent = 'Edit working hours';
          submitBtn.textContent = 'Save changes';
          cancelBtn.hidden = false;
          form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
      });
    }

    const roster = document.getElementById('bookPlayerRoster');
    const nameInput = document.getElementById('bookPlayerName');
    const nameField = document.getElementById('bookPlayerNameField');
    const syncBookPlayer = () => {
      if (!roster || !nameInput) return;
      const opt = roster.options[roster.selectedIndex];
      const hasAccount = Boolean(roster.value);
      const guestName = opt?.dataset?.name || '';
      if (hasAccount) {
        nameInput.value = opt.dataset.name || '';
        nameInput.required = false;
        if (nameField) nameField.style.opacity = '0.55';
      } else {
        if (opt?.dataset?.guest === '1' && guestName) {
          nameInput.value = guestName;
        }
        nameInput.required = true;
        if (nameField) nameField.style.opacity = '1';
      }
    };
    roster?.addEventListener('change', syncBookPlayer);
    syncBookPlayer();
  })();
</script>
@endpush
