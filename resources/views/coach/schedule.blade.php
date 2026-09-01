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
  $nowTop = ((9 * 60 + 7) - (6 * 60)) / ((21 - 6) * 60) * 100;
  $typeBadge = fn (string $type) => match (true) {
    str_contains($type, 'Small') => 'yellow',
    str_contains($type, 'Group') => 'purple',
    str_contains($type, 'Assessment') => 'orange',
    default => 'green',
  };
@endphp

<section class="admin-kpi-grid admin-kpi-grid--3">
  @foreach ($summary as $tile)
    <article class="admin-kpi">
      <div class="admin-kpi-label">{{ $tile['label'] }}</div>
      <div class="admin-kpi-value">{{ $tile['value'] }}</div>
      <div class="admin-kpi-trend flat">{{ $tile['note'] }}</div>
    </article>
  @endforeach
</section>

<div class="sched-calendar-card">
  <div class="sched-card-header">
    <div class="sched-toolbar">
      <div class="sched-toolbar-left">
        <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Today</button>
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
    </div>
    <label class="sched-select-wrap">
      <select class="sched-select" aria-label="Calendar view">
        <option selected>Week</option>
        <option>Day</option>
        <option>Month</option>
      </select>
    </label>
  </div>

  <div class="sched-calendar-scroll">
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
            @for ($slot = 0; $slot < 30; $slot++)
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
    <span><i class="sched-legend-dot sched-legend-dot--yellow"></i> Small Group</span>
    <span><i class="sched-legend-dot sched-legend-dot--purple"></i> Group Session</span>
    <span><i class="sched-legend-dot sched-legend-dot--orange"></i> Assessment</span>
    <span><i class="sched-legend-dot sched-legend-dot--blocked"></i> Blocked / Unavailable</span>
  </div>
</div>

<section class="admin-card coach-schedule-requests">
  <div class="admin-card-header">
    <div>
      <h2>
        Booking Requests
        <span class="sched-pending-badge">{{ count($requests) }} pending</span>
      </h2>
      <p>Accept to add sessions to your calendar, or decline to notify the player</p>
    </div>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Player</th>
          <th>When</th>
          <th>Type</th>
          <th>Notes</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($requests as $request)
          @php $badge = $typeBadge($request['type']); @endphp
          <tr>
            <td>
              <div class="admin-person">
                <div class="admin-person-fallback">{{ $request['initials'] }}</div>
                <div><strong>{{ $request['name'] }}</strong></div>
              </div>
            </td>
            <td>{{ $request['when'] }}</td>
            <td><span class="sched-type-badge sched-type-badge--{{ $badge }}">{{ $request['type'] }}</span></td>
            <td class="text-zinc-500">{{ $request['note'] }}</td>
            <td class="admin-table-actions">
              <button type="button" class="admin-btn admin-btn-primary admin-btn-sm">Accept</button>
              <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Decline</button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>

@include('partials.coach.subscription-note', [
  'subscriptionNote' => 'Schedule management and booking acceptance require an active Development Plus subscription.',
])
@endsection
