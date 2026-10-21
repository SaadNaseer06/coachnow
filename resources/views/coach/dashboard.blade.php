@extends('layouts.coach')

@section('title', 'Dashboard')
@section('page_title', 'Good morning, ' . ($coach->display_name ?? 'Coach'))
@section('page_subtitle', 'Your players, sessions, and reports at a glance')

@section('topbar_actions')
  <a href="{{ route('coach.add-report') }}" class="admin-btn admin-btn-primary coach-top-action">
    <span class="coach-top-action__full">+ Add Report</span>
    <span class="coach-top-action__short">+ Report</span>
  </a>
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
    <div class="admin-kpi-value" id="coachOpenRequestCount">{{ $pendingRequestCount ?? collect($sessionRequests ?? [])->where('status', 'open')->count() }}</div>
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
              <td data-label="Player">
                <a href="{{ route('coach.players.show', $player['slug']) }}" class="admin-person coach-player-link">
                  <div class="admin-person-fallback">{{ $player['initials'] }}</div>
                  <div>
                    <strong>{{ $player['name'] }}</strong>
                    <span>{{ $player['age'] }} · {{ $player['sport'] }} · {{ $player['sessions'] }} sessions</span>
                  </div>
                </a>
              </td>
              <td data-label="Focus">{{ $player['focus'] }}</td>
              <td data-label="Next Session">{{ $player['next'] }}</td>
              <td data-label="Status">
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
        <h2>Upcoming Sessions</h2>
        <p>Next confirmed bookings on your calendar</p>
      </div>
      <a href="{{ route('coach.schedule') }}" class="admin-btn admin-btn-ghost admin-btn-sm">Open schedule</a>
    </div>
    <div class="admin-card-body">
      <div class="admin-list">
        @forelse ($upcomingSessions ?? [] as $session)
          <div class="admin-list-item">
            <div>
              <strong>{{ $session['name'] }}</strong>
              <span>{{ $session['when'] }} · {{ $session['type'] }}@if(!empty($session['location'])) · {{ $session['location'] }}@endif</span>
            </div>
            @if (!empty($session['is_group']))
              <span class="admin-badge {{ !empty($session['is_public']) ? 'admin-badge-amber' : 'admin-badge-zinc' }}">
                {{ !empty($session['is_public']) ? 'Public group' : 'Private group' }}
              </span>
            @else
              <span class="admin-badge admin-badge-green">1-on-1</span>
            @endif
          </div>
        @empty
          <div class="admin-list-item">
            <div>
              <strong>No upcoming sessions</strong>
              <span>Book a player or accept a request to fill your week</span>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</section>

<section class="admin-grid-2" style="margin-top:16px">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2>Pending Session Requests</h2>
        <p>Open marketplace / invited requests waiting for a host</p>
      </div>
      <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-open-session-requests>
        Review ({{ $pendingRequestCount ?? 0 }})
      </button>
    </div>
    <div class="admin-card-body">
      <div class="admin-list">
        <div class="admin-list-item">
          <div>
            <strong>{{ $pendingRequestCount ?? 0 }} open request{{ ($pendingRequestCount ?? 0) === 1 ? '' : 's' }} in your inbox</strong>
            <span>Invited + marketplace requests waiting for a host — use the bell or KPI to accept</span>
          </div>
          <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-open-session-requests>Review</button>
        </div>

        <div class="admin-list-item" style="align-items:flex-start">
          <div style="flex:1;min-width:0">
            <strong>Open Session Opportunities</strong>
            <span>Public marketplace requests near your park</span>
            @forelse ($openOpportunities ?? [] as $opp)
              <div style="margin-top:10px;padding-top:10px;border-top:1px solid #e4e4e7">
                <div style="font-size:13px;font-weight:600;color:#18181b">{{ $opp['title'] }}</div>
                <div style="font-size:12px;color:#71717a;margin-top:2px;line-height:1.45">
                  {{ $opp['date_label'] ?? '' }}@if(!empty($opp['time_label'])) · {{ $opp['time_label'] }}@endif
                  · {{ $opp['location_name'] ?? 'Park TBD' }}
                  @if (($opp['distance_miles'] ?? null) !== null)
                    · {{ number_format((float) $opp['distance_miles'], 1) }} mi
                  @endif
                  @if (!empty($opp['sport'])) · {{ $opp['sport'] }}@endif
                  @if (!empty($opp['age_range'])) · {{ $opp['age_range'] }}@endif
                </div>
              </div>
            @empty
              <div style="margin-top:8px;font-size:12px;color:#a1a1aa">
                No open marketplace requests right now.
                <a href="{{ route('training-near-you') }}" style="color:inherit;text-decoration:underline">Browse Training Near You</a>
              </div>
            @endforelse
          </div>
          @if (!empty($openOpportunities))
            <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-open-session-requests>Accept</button>
          @endif
        </div>

        @if (($pendingJoins ?? collect())->isNotEmpty())
          @foreach ($pendingJoins as $join)
            <div class="admin-list-item">
              <div>
                <strong>{{ $join->displayName() }} wants to join</strong>
                <span>
                  {{ $join->groupSession?->session_type }} ·
                  {{ $join->groupSession?->session_date?->format('D, M j') }}
                  {{ $join->groupSession?->timeLabel() }}
                </span>
              </div>
              <a href="{{ route('coach.schedule', ['date' => optional($join->groupSession?->session_date)->toDateString()]) }}" class="admin-btn admin-btn-ghost admin-btn-sm">Manage</a>
            </div>
          @endforeach
        @endif
      </div>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2>Group Session Management</h2>
        <p>Private vs public groups and remaining spots</p>
      </div>
      <a href="{{ route('coach.schedule') }}" class="admin-btn admin-btn-ghost admin-btn-sm">Schedule</a>
    </div>
    <div class="admin-card-body">
      <div class="admin-list">
        @forelse ($openGroups ?? [] as $group)
          <div class="admin-list-item">
            <div>
              <strong>{{ $group->session_type }}</strong>
              <span>
                {{ $group->session_date?->format('D, M j') }} · {{ $group->timeLabel() }} ·
                {{ $group->capacityLabel() }}
                @if($group->age_range) · {{ $group->age_range }}@endif
                @if($group->sportLabel()) · {{ $group->sportLabel() }}@endif
              </span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:flex-end">
              <span class="admin-badge {{ $group->is_public ? 'admin-badge-amber' : 'admin-badge-zinc' }}">
                {{ $group->is_public ? 'Public · '.$group->spotsRemaining().' left' : 'Private' }}
              </span>
              @if ($group->is_public && $group->shareUrl())
                <button
                  type="button"
                  class="admin-btn admin-btn-ghost admin-btn-sm"
                  data-share-group
                  data-share-url="{{ $group->shareUrl() }}"
                  data-share-title="{{ $group->session_type }} with {{ $coach->display_name }}"
                  data-share-text="{{ $group->spotsRemaining() }} spot{{ $group->spotsRemaining() === 1 ? '' : 's' }} left · {{ $group->session_date?->format('D, M j') }} {{ $group->timeLabel() }}@if($group->age_range) · {{ $group->age_range }}@endif"
                >Share</button>
              @endif
              @if ($group->status !== 'full' || $group->is_public)
                <form method="POST" action="{{ route('coach.group-sessions.visibility', $group) }}" style="margin:0">
                  @csrf
                  @method('PATCH')
                  <input type="hidden" name="is_public" value="{{ $group->is_public ? 0 : 1 }}">
                  @if (! $group->is_public && blank($group->age_range))
                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:#71717a">
                      Ages
                      <input type="number" name="age_min" class="admin-input" required min="{{ \App\Models\CoachGroupSession::MIN_AGE }}" max="{{ \App\Models\CoachGroupSession::MAX_AGE }}" placeholder="11" aria-label="Minimum age" style="height:32px;width:56px;padding:0 6px;font-size:12px">
                      –
                      <input type="number" name="age_max" class="admin-input" required min="{{ \App\Models\CoachGroupSession::MIN_AGE }}" max="{{ \App\Models\CoachGroupSession::MAX_AGE }}" placeholder="13" aria-label="Maximum age" style="height:32px;width:56px;padding:0 6px;font-size:12px">
                    </span>
                  @endif
                  <button type="submit" class="admin-btn admin-btn-sm {{ $group->is_public ? 'admin-btn-ghost' : 'admin-btn-primary' }}">
                    {{ $group->is_public ? 'Close' : 'Open to public' }}
                  </button>
                </form>
              @endif
            </div>
          </div>
        @empty
          <div class="admin-list-item">
            <div>
              <strong>No upcoming group sessions</strong>
              <span>Book a group type on your schedule, then open spots to the public when ready</span>
            </div>
          </div>
        @endforelse
      </div>
    </div>
  </div>
</section>

@include('partials.coach.subscription-note')
@endsection
