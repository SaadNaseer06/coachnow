@extends('layouts.coach')

@section('title', 'Schedule')
@section('page_title', 'My Schedule')
@section('page_subtitle', 'See who has booked with you and add sessions manually')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/admin-schedule.css') }}">
@endpush

@section('topbar_actions')
  <button type="button" class="admin-btn admin-btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
    Add Session
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

  @include('partials.coach.subscription-note', [
    'subscriptionNote' => 'Schedule management and booking acceptance require an active Development Plus subscription.',
  ])
</div>
@endsection

@push('scripts')
<script>
  (function () {
    const scroller = document.querySelector('.sched-calendar-scroll[data-scroll-to-row]');
    if (!scroller) return;
    const row = parseInt(scroller.dataset.scrollToRow || '', 10);
    const rowHeight = parseInt(scroller.dataset.rowHeight || '36', 10);
    if (!row || row < 1) return;
    const top = Math.max(0, (row - 2) * rowHeight);
    requestAnimationFrame(() => {
      scroller.scrollTop = top;
    });
  })();
</script>
@endpush
