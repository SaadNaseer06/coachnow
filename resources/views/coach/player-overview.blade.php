@extends('layouts.coach')

@section('title', 'Players')
@section('page_title', 'Player Overview')
@section('page_subtitle', 'Browse your roster and open a player profile for development details')

@section('topbar_actions')
  <a href="{{ route('coach.add-report') }}" class="admin-btn admin-btn-primary">+ Add Report</a>
@endsection

@section('content')
<section class="admin-kpi-grid admin-kpi-grid--3">
  <article class="admin-kpi">
    <div class="admin-kpi-label">Active Players</div>
    <div class="admin-kpi-value">{{ $totalPlayers }}</div>
    <div class="admin-kpi-trend flat">On Development Plus</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Reports Due</div>
    <div class="admin-kpi-value">{{ $reportsDueTotal }}</div>
    <div class="admin-kpi-trend {{ $reportsDueTotal ? 'up' : 'flat' }}">{{ $reportsDueTotal ? 'Need your attention' : 'All caught up' }}</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Showing</div>
    <div class="admin-kpi-value">{{ $players->count() }}</div>
    <div class="admin-kpi-trend flat">Players on this page</div>
  </article>
</section>

<div class="admin-toolbar">
  <form method="GET" action="{{ route('coach.player-overview') }}" class="admin-filters" data-auto-filter>
    <input class="admin-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search players…" aria-label="Search players" autocomplete="off">
    <select class="admin-select" name="status" aria-label="Filter by status">
      <option value="">All players</option>
      <option value="report-due" @selected(($filters['status'] ?? '') === 'report-due')>Report due</option>
      <option value="on-track" @selected(($filters['status'] ?? '') === 'on-track')>On track</option>
    </select>
  </form>
  <span class="text-[12px] text-zinc-500">{{ $players->total() }} players</span>
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
        @forelse ($players as $player)
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
        @empty
          <tr>
            <td colspan="6" class="text-center text-zinc-500 py-8">No players match these filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.admin.pagination', ['paginator' => $players])
</section>

@include('partials.coach.subscription-note')
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/admin.js') }}?v={{ @filemtime(public_path('assets/js/admin.js')) ?: time() }}"></script>
@endpush
