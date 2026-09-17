@extends('layouts.coach')

@section('title', 'Dashboard')
@section('page_title', 'Good morning, ' . ($coach->display_name ?? 'Coach'))
@section('page_subtitle', 'Your players, sessions, and reports at a glance')

@section('topbar_actions')
  <a href="{{ route('coach.add-report') }}" class="admin-btn admin-btn-primary">+ Add Report</a>
@endsection

@section('content')
@if (($coach->status ?? '') !== 'active' || ! $coach->isProfileComplete())
  <div
    class="admin-alert {{ ($coach->status ?? '') === 'active' ? 'admin-alert--success' : 'admin-alert--error' }}"
    role="status"
    style="margin-bottom:14px;"
    data-coach-status-banner
    data-status="{{ $coach->status }}"
  >
    <span class="admin-alert__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
    </span>
    <div class="admin-alert__body">
      <p class="admin-alert__label" data-coach-status-banner-label>{{ ($coach->status ?? '') === 'active' ? 'Polish your listing' : 'Finish your Find a Coach profile' }}</p>
      <p class="admin-alert__text" data-coach-status-banner-text>
        @if (($coach->status ?? '') === 'pending')
          Complete your profile, then wait for admin approval to go live.
        @elseif (($coach->status ?? '') === 'paused')
          Your listing is paused. Update your profile, then ask an admin to activate you.
        @else
          Add a photo and keep your rate/park current so athletes can find you.
        @endif
        <a href="{{ route('coach.profile') }}" class="font-semibold underline" style="color:inherit;">Open My Profile</a>
      </p>
    </div>
  </div>
@else
  <div
    class="admin-alert admin-alert--success"
    role="status"
    style="margin-bottom:14px;"
    data-coach-status-banner
    data-status="{{ $coach->status }}"
    hidden
  >
    <span class="admin-alert__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
    </span>
    <div class="admin-alert__body">
      <p class="admin-alert__label" data-coach-status-banner-label>You’re live</p>
      <p class="admin-alert__text" data-coach-status-banner-text>
        Your listing is active on Find a Coach.
        <a href="{{ route('coach.profile') }}" class="font-semibold underline" style="color:inherit;">Open My Profile</a>
      </p>
    </div>
  </div>
@endif

<section class="admin-kpi-grid">
  <article class="admin-kpi">
    <div class="admin-kpi-label">Active Players</div>
    <div class="admin-kpi-value">{{ count($players) }}</div>
    <div class="admin-kpi-trend flat">All on Development Plus</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Sessions This Week</div>
    <div class="admin-kpi-value">{{ $weekSessionCount ?? 0 }}</div>
    <div class="admin-kpi-trend up">{{ $weekHours ?? 0 }} hours on the field</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Reports Due</div>
    <div class="admin-kpi-value">2</div>
    <div class="admin-kpi-trend flat">From this week's sessions</div>
  </article>
  <article class="admin-kpi coach-kpi-requests" id="coachOpenRequestsKpi" role="button" tabindex="0" aria-label="Open session requests">
    <div class="admin-kpi-label">Open Session Requests</div>
    <div class="admin-kpi-value" id="coachOpenRequestCount">{{ collect($sessionRequests ?? [])->where('status', 'open')->count() }}</div>
    <div class="admin-kpi-trend up">Tap to review · Hosted sessions stay open to join</div>
  </article>
</section>

<section class="admin-grid-2">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2>Your Players</h2>
        <p>Development Plus athletes on your roster</p>
      </div>
      <a href="{{ route('coach.player-overview') }}" class="admin-btn admin-btn-ghost admin-btn-sm">View all</a>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Player</th>
            <th>Focus</th>
            <th>Next Session</th>
            <th>Status</th>
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
                    <span>{{ $player['age'] }} · {{ $player['sport'] }} · {{ $player['sessions'] }} sessions</span>
                  </div>
                </a>
              </td>
              <td>{{ $player['focus'] }}</td>
              <td>{{ $player['next'] }}</td>
              <td>
                @if ($player['reportDue'])
                  <span class="admin-badge admin-badge-amber">Report due</span>
                @else
                  <span class="admin-badge admin-badge-green">On track</span>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2>Today's Sessions</h2>
        <p>{{ $todayLabel ?? 'Today' }} · {{ count($today) }} sessions scheduled</p>
      </div>
      <a href="{{ route('coach.schedule') }}" class="admin-btn admin-btn-ghost admin-btn-sm">Open schedule</a>
    </div>
    <div class="admin-card-body">
      <div class="admin-list">
        @forelse ($today as $session)
          <div class="admin-list-item">
            <div>
              <strong>{{ $session['name'] }}</strong>
              <span>{{ $session['time'] }} · {{ $session['type'] }}</span>
            </div>
            <span class="admin-badge admin-badge-zinc">{{ ucfirst($session['tone']) }}</span>
          </div>
        @empty
          <div class="admin-list-item">
            <div>
              <strong>No sessions today</strong>
              <span>Accepted requests will show here</span>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</section>

@include('partials.coach.subscription-note')
@endsection
