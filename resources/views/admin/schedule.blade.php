@extends('layouts.admin')

@section('title', 'Schedule')
@section('page_title', 'My Schedule')
@section('page_subtitle', 'Plan your week. Manage your sessions.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/admin-schedule.css') }}">
@endpush

@section('topbar_actions')
  <button type="button" class="admin-btn admin-btn-primary">+ New Session</button>
@endsection

@section('content')
@php
  $days = [
    ['label' => 'MON', 'date' => '24'],
    ['label' => 'TUE', 'date' => '25'],
    ['label' => 'WED', 'date' => '26', 'today' => true],
    ['label' => 'THU', 'date' => '27'],
    ['label' => 'FRI', 'date' => '28'],
    ['label' => 'SAT', 'date' => '29'],
    ['label' => 'SUN', 'date' => '30'],
  ];
  $hours = range(4, 20);
  $sessionsByDay = collect($sessions)->groupBy('day');
  $nowTop = ((9 * 60 + 7) - (4 * 60)) / ((21 - 4) * 60) * 100;
@endphp

<div class="sched-toolbar">
  <div class="sched-toolbar-left">
    <button type="button" class="admin-btn admin-btn-ghost">Today</button>
    <div class="sched-date-nav">
      <button type="button" class="sched-icon-btn" aria-label="Previous week">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
      </button>
      <button type="button" class="sched-date-picker">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        {{ $weekLabel }}
      </button>
      <button type="button" class="sched-icon-btn" aria-label="Next week">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>
      </button>
    </div>
  </div>
  <div class="sched-toolbar-right">
    <label class="sched-select-wrap">
      <select class="sched-select" aria-label="Calendar view">
        <option selected>Week</option>
        <option>Day</option>
        <option>Month</option>
      </select>
    </label>
  </div>
</div>

<div class="sched-calendar-card">
  <div class="sched-calendar-scroll">
    <div class="sched-calendar">
      <div class="sched-calendar-corner"></div>

      <div class="sched-calendar-days">
        @foreach ($days as $day)
          <div class="sched-calendar-dayhead {{ !empty($day['today']) ? 'is-today' : '' }}">
            <span class="sched-calendar-daylabel">{{ $day['label'] }}</span>
            <span class="sched-calendar-daynum">{{ $day['date'] }}</span>
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

            @if (!empty($day['today']))
              <div class="sched-now-line" style="top: {{ number_format($nowTop, 2, '.', '') }}%;">
                <span class="sched-now-label">9:07 AM</span>
              </div>
            @endif

            @foreach ($sessionsByDay->get($dayIndex, collect()) as $session)
              <article
                class="sched-event sched-event--{{ $session['tone'] }} {{ !empty($session['allDay']) ? 'is-all-day' : '' }}"
                style="grid-row: {{ $session['gridStart'] }} / {{ $session['gridEnd'] }};"
              >
                <p class="sched-event-title">{{ $session['title'] }}</p>
                <p class="sched-event-type">{{ $session['type'] }}</p>
                @unless (!empty($session['allDay']))
                  <p class="sched-event-meta">{{ $session['duration'] }} min @if($session['players'] > 0)· {{ $session['players'] }} {{ $session['players'] === 1 ? 'player' : 'players' }}@endif</p>
                @endunless
              </article>
            @endforeach
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <div class="sched-calendar-legend">
    <span><i class="sched-legend-dot sched-legend-dot--green"></i> Private Session</span>
    <span><i class="sched-legend-dot sched-legend-dot--yellow"></i> Small Group (2-3)</span>
    <span><i class="sched-legend-dot sched-legend-dot--purple"></i> Group Session</span>
    <span><i class="sched-legend-dot sched-legend-dot--blue"></i> Private Session</span>
    <span><i class="sched-legend-dot sched-legend-dot--orange"></i> Assessment</span>
    <span><i class="sched-legend-dot sched-legend-dot--blocked"></i> Blocked / Unavailable</span>
  </div>
</div>
@endsection
