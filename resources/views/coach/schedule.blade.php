@extends('layouts.coach')

@section('title', 'Schedule')
@section('page_title', 'My Schedule')
@section('page_subtitle', 'See who has booked with you — set weekly open times for Book Now')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/admin-schedule.css') }}">
@endpush

@section('topbar_actions')
  @php $availabilityCount = collect($availability ?? [])->count(); @endphp
  <button type="button" class="admin-btn admin-btn-ghost" data-admin-modal-open="bookPlayerModal">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
    Book player
  </button>
  <button type="button" class="admin-btn admin-btn-primary" data-admin-modal-open="availabilityModal">
    Set availability
    @if ($availabilityCount > 0)
      <span class="admin-badge admin-badge-zinc" style="margin-left:6px">{{ $availabilityCount }}</span>
    @endif
  </button>
@endsection

@section('content')
@php
  $sessionsByDay = collect($sessions)->groupBy('day');
  $now = now();
  $dayStart = 4 * 60;
  $dayEnd = 21 * 60;
  $nowMinutes = ($now->hour * 60) + $now->minute;
  $nowInRange = $nowMinutes >= $dayStart && $nowMinutes <= $dayEnd;
  $nowTop = $nowInRange ? (($nowMinutes - $dayStart) / ($dayEnd - $dayStart)) * 100 : null;
  $firstGridStart = collect($sessions)->min('gridStart');
  $openAvailabilityModal = request()->boolean('availability')
    || (session('success') && str_contains(strtolower((string) session('success')), 'availability'));
  $bookErrorKeys = ['player_name', 'athlete_id', 'session_date', 'session_time', 'location_id', 'session_type', 'time'];
  $openBookPlayerModal = request()->boolean('book')
    || $errors->hasAny($bookErrorKeys);
@endphp

<div class="sched-page">
  <div class="sched-stats">
    @foreach ($summary as $tile)
      <div class="sched-stat">
        <span class="sched-stat-value">{{ $tile['value'] }}</span>
        <span class="sched-stat-label">{{ $tile['label'] }}</span>
        <span class="sched-stat-note">{{ $tile['note'] }}</span>
      </div>
    @endforeach
  </div>

  @if (($upcomingTotal ?? 0) > 0 && count($sessions) === 0)
    <div class="admin-alert admin-alert--success" role="status">
      <span class="admin-alert__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
      </span>
      <div class="admin-alert__body">
        <p class="admin-alert__label">Sessions on another week</p>
        <p class="admin-alert__text">You have {{ $upcomingTotal }} upcoming session{{ $upcomingTotal === 1 ? '' : 's' }}. Use the week arrows to find them — this week has none yet.</p>
      </div>
    </div>
  @endif

  @if ($availabilityCount === 0)
    <div class="admin-alert" role="status" style="margin-bottom:1rem;border:1px solid #e5e5e5;background:#fafafa">
      <div class="admin-alert__body">
        <p class="admin-alert__label">No Book Now times yet</p>
        <p class="admin-alert__text">
          Publish weekly open hours so athletes can book you directly.
          <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-admin-modal-open="availabilityModal" style="margin-left:6px">Set availability</button>
        </p>
      </div>
    </div>
  @endif

  <div class="sched-workspace">
    <div class="sched-calendar-card">
      <div class="sched-card-header">
        <div class="sched-toolbar">
          <div class="sched-toolbar-left">
            <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" onclick="window.location='{{ route('coach.schedule', ['week' => now()->startOfWeek()->toDateString()]) }}'">Today</button>
            <div class="sched-date-nav">
              <a href="{{ route('coach.schedule', ['week' => $prevWeek ?? '']) }}" class="sched-icon-btn" aria-label="Previous week">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
              </a>
              <button type="button" class="sched-date-picker">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                {{ $weekLabel }}
              </button>
              <a href="{{ route('coach.schedule', ['week' => $nextWeek ?? '']) }}" class="sched-icon-btn" aria-label="Next week">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
              </a>
            </div>
            <span class="sched-session-count">{{ count($sessions) }} session{{ count($sessions) === 1 ? '' : 's' }}</span>
          </div>
        </div>
      </div>

      <div
        class="sched-calendar-scroll"
        data-scroll-to-row="{{ $firstGridStart ?: '' }}"
        data-row-height="36"
      >
        <div class="sched-calendar">
          <div class="sched-calendar-corner"></div>

          <div class="sched-calendar-days">
            @foreach ($days as $day)
              <div class="sched-calendar-dayhead {{ !empty($day['today']) ? 'is-today' : '' }}">
                <span class="sched-calendar-daylabel">{{ $day['name'] }}</span>
                <span class="sched-calendar-daynum">{{ $day['num'] }}</span>
              </div>
            @endforeach
          </div>

          <div class="sched-calendar-times">
            @foreach ($hours as $hour)
              <div class="sched-calendar-time">{{ $hour <= 12 ? $hour : ($hour - 12) }} {{ $hour < 12 ? 'AM' : 'PM' }}</div>
            @endforeach
          </div>

          <div class="sched-calendar-grid">
            @foreach ($days as $dayIndex => $day)
              <div class="sched-calendar-column {{ !empty($day['today']) ? 'is-today' : '' }}">
                @for ($slot = 0; $slot < 34; $slot++)
                  <div class="sched-calendar-slot"></div>
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
                  @endphp
                  <article
                    class="sched-event sched-event--{{ $session['tone'] }} {{ !empty($session['allDay']) ? 'is-all-day' : '' }}"
                    style="grid-row: {{ $session['gridStart'] }} / {{ $session['gridEnd'] }};"
                    title="{{ $eventTip }}"
                  >
                    <p class="sched-event-title">
                      <span class="sched-event-time">{{ $session['time_label'] ?? '' }}</span>
                      {{ $session['title'] }}
                    </p>
                    <p class="sched-event-type">{{ $session['type'] }}</p>
                  </article>
                @endforeach
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <div class="sched-calendar-legend">
        <span><i class="sched-legend-dot sched-legend-dot--green"></i> Private</span>
        <span><i class="sched-legend-dot sched-legend-dot--yellow"></i> Small Group</span>
        <span><i class="sched-legend-dot sched-legend-dot--purple"></i> Group</span>
        <span><i class="sched-legend-dot sched-legend-dot--orange"></i> Assessment</span>
        <span><i class="sched-legend-dot sched-legend-dot--blocked"></i> Blocked</span>
      </div>
    </div>

    <aside class="sched-agenda" aria-label="Sessions this week">
      <div class="sched-agenda-header">
        <h2 class="sched-agenda-title">This week</h2>
        <p class="sched-agenda-sub">
          @if (count($sessions) === 0)
            No sessions booked
          @else
            {{ count($sessions) }} session{{ count($sessions) === 1 ? '' : 's' }} — full details
          @endif
        </p>
      </div>

      @if (count($sessions) > 0)
        <ul class="sched-agenda-list">
          @foreach ($sessions as $session)
            <li class="sched-agenda-item">
              <span class="sched-agenda-tone sched-agenda-tone--{{ $session['tone'] }}" aria-hidden="true"></span>
              <div class="sched-agenda-content">
                <div class="sched-agenda-when">
                  <strong>{{ $session['date_label'] ?? '' }}</strong>
                  <span>{{ $session['time_label'] ?? '' }}</span>
                </div>
                <p class="sched-agenda-name">{{ $session['title'] }}</p>
                <p class="sched-agenda-detail">
                  {{ $session['type'] }} · {{ $session['duration'] }} min
                  @if (! empty($session['location']))
                    · {{ $session['location'] }}
                  @endif
                </p>
              </div>
            </li>
          @endforeach
        </ul>
      @else
        <p class="sched-agenda-empty">Use the week controls to browse other dates, or accept a session request to fill this week.</p>
      @endif
    </aside>
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
          <input class="admin-input" type="date" name="session_date" value="{{ old('session_date', now()->toDateString()) }}" required min="{{ now()->toDateString() }}">
        </label>
        <label class="admin-field">
          <span>Time</span>
          <input class="admin-input" type="time" name="session_time" value="{{ old('session_time', '16:00') }}" required>
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
        <h2 id="availabilityModalTitle">Weekly availability</h2>
        <p>Add open hours once per day — athletes book those slots with Book Now.</p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
    </div>
    <div class="admin-modal__body">
      @if ($errors->any())
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
          <strong id="availFormTitle">Add a block</strong>
          <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" id="availCancelEdit" hidden>Cancel edit</button>
        </div>

        <div class="avail-form__grid">
          <label class="admin-field">
            <span>Day</span>
            <select class="admin-select" name="day_of_week" id="availDay" required>
              @foreach ($dayLabels as $dow => $label)
                <option value="{{ $dow }}">{{ $label }}</option>
              @endforeach
            </select>
          </label>
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
            <button type="submit" class="admin-btn admin-btn-primary" id="availSubmitBtn">Add availability</button>
          </div>
        </div>
      </form>

      <div class="avail-list-head">
        <strong>Published blocks</strong>
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
              <form method="POST" action="{{ route('coach.schedule.availability.destroy', ['slot' => $block->id, 'week' => $weekStart ?? '']) }}" onsubmit="return confirm('Remove this availability block?')">
                @csrf
                @method('DELETE')
                <input type="hidden" name="week" value="{{ $weekStart ?? '' }}">
                <button type="submit" class="admin-btn admin-btn-ghost admin-btn-sm">Remove</button>
              </form>
            </div>
          </div>
        @empty
          <p class="avail-list-empty">No blocks yet. Add your first open window above.</p>
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
    const scroller = document.querySelector('.sched-calendar-scroll[data-scroll-to-row]');
    if (scroller) {
      const row = parseInt(scroller.dataset.scrollToRow || '', 10);
      const rowHeight = parseInt(scroller.dataset.rowHeight || '36', 10);
      if (row && row >= 1) {
        const top = Math.max(0, (row - 2) * rowHeight);
        requestAnimationFrame(() => { scroller.scrollTop = top; });
      }
    }

    const form = document.getElementById('availabilityForm');
    if (!form) return;

    const methodInput = document.getElementById('availMethod');
    const titleEl = document.getElementById('availFormTitle');
    const submitBtn = document.getElementById('availSubmitBtn');
    const cancelBtn = document.getElementById('availCancelEdit');
    const dayEl = document.getElementById('availDay');
    const startEl = document.getElementById('availStart');
    const endEl = document.getElementById('availEnd');
    const durationEl = document.getElementById('availDuration');
    const locationEl = document.getElementById('availLocation');
    const storeUrl = form.dataset.storeUrl;
    const updateBase = form.dataset.updateBase;
    const week = form.dataset.week || '';

    const clearEdit = () => {
      form.action = storeUrl;
      methodInput.disabled = true;
      methodInput.value = 'POST';
      titleEl.textContent = 'Add a block';
      submitBtn.textContent = 'Add availability';
      cancelBtn.hidden = true;
      dayEl.value = '1';
      startEl.value = '16:00';
      endEl.value = '19:00';
      durationEl.value = '60';
      locationEl.value = '';
      document.querySelectorAll('.avail-row.is-editing').forEach((el) => el.classList.remove('is-editing'));
    };

    cancelBtn?.addEventListener('click', clearEdit);

    document.querySelectorAll('[data-avail-edit]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const row = btn.closest('.avail-row');
        if (!row) return;
        document.querySelectorAll('.avail-row.is-editing').forEach((el) => el.classList.remove('is-editing'));
        row.classList.add('is-editing');
        dayEl.value = row.dataset.day;
        startEl.value = row.dataset.start;
        endEl.value = row.dataset.end;
        durationEl.value = row.dataset.duration;
        locationEl.value = row.dataset.location;
        methodInput.disabled = false;
        methodInput.value = 'PATCH';
        form.action = `${updateBase}/${row.dataset.id}${week ? '?week=' + encodeURIComponent(week) : ''}`;
        titleEl.textContent = 'Edit block';
        submitBtn.textContent = 'Save changes';
        cancelBtn.hidden = false;
        form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      });
    });

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
