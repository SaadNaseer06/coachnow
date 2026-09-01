@extends('layouts.coach')

@section('title', 'Players')
@section('page_title', 'Player Overview')
@section('page_subtitle', 'Browse your roster and open a player profile for development details')

@section('topbar_actions')
  <a href="{{ route('coach.add-report') }}" class="admin-btn admin-btn-primary">+ Add Report</a>
@endsection

@section('content')
@php
  $reportsDue = collect($players)->where('reportDue', true)->count();
@endphp

<section class="admin-kpi-grid admin-kpi-grid--3">
  <article class="admin-kpi">
    <div class="admin-kpi-label">Active Players</div>
    <div class="admin-kpi-value">{{ count($players) }}</div>
    <div class="admin-kpi-trend flat">On Development Plus</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Reports Due</div>
    <div class="admin-kpi-value">{{ $reportsDue }}</div>
    <div class="admin-kpi-trend {{ $reportsDue ? 'up' : 'flat' }}">{{ $reportsDue ? 'Need your attention' : 'All caught up' }}</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Upcoming Sessions</div>
    <div class="admin-kpi-value">{{ count($players) }}</div>
    <div class="admin-kpi-trend flat">Scheduled this week</div>
  </article>
</section>

<div class="admin-toolbar">
  <div class="admin-filters">
    <input class="admin-input" type="search" placeholder="Search players…" aria-label="Search players">
    <select class="admin-select" aria-label="Filter by status">
      <option>All players</option>
      <option>Report due</option>
      <option>On track</option>
    </select>
  </div>
</div>

<section class="admin-card">
  <div class="admin-card-header">
    <div>
      <h2>Your Roster</h2>
      <p>Select a player to view skills, session history, goals, and notes</p>
    </div>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Player</th>
          <th>Focus</th>
          <th>Sessions</th>
          <th>Next Session</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach ($players as $player)
          <tr>
            <td>
              <a href="{{ route('coach.players.show', $player['slug']) }}" class="admin-person coach-player-link">
                <div class="admin-person-fallback">{{ $player['initials'] }}</div>
                <div>
                  <strong>{{ $player['name'] }}</strong>
                  <span>{{ $player['age'] }} · {{ $player['sport'] }}</span>
                </div>
              </a>
            </td>
            <td>{{ $player['focus'] }}</td>
            <td>{{ $player['sessions'] }}</td>
            <td>{{ $player['next'] }}</td>
            <td>
              @if ($player['reportDue'])
                <span class="admin-badge admin-badge-amber">Report due</span>
              @else
                <span class="admin-badge admin-badge-green">On track</span>
              @endif
            </td>
            <td class="admin-table-actions">
              <a href="{{ route('coach.players.show', $player['slug']) }}" class="admin-btn admin-btn-ghost admin-btn-sm">View profile</a>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</section>

@include('partials.coach.subscription-note')
@endsection
