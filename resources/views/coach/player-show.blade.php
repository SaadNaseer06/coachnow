@extends('layouts.coach')

@section('title', $player['name'])
@section('page_title', $player['name'])
@section('page_subtitle')
  <a href="{{ route('coach.player-overview') }}" class="coach-breadcrumb">All players</a>
  <span class="coach-breadcrumb-sep">/</span>
  {{ $player['age'] }} · {{ $player['sport'] }} · {{ $player['plan'] }}
@endsection

@section('topbar_actions')
  <a href="{{ route('coach.player-overview') }}" class="admin-btn admin-btn-ghost">← Back</a>
  <a href="{{ route('coach.add-report', ['player' => $player['slug']]) }}" class="admin-btn admin-btn-primary">+ Add Report</a>
  <button type="button" class="admin-btn admin-btn-ghost">Share Video</button>
@endsection

@section('content')
<section class="coach-strip">
  <div class="coach-strip__item">
    <span class="coach-strip__icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/></svg>
    </span>
    <div>
      <p class="coach-strip__label">Current focus</p>
      <p class="coach-strip__value">{{ $player['current_focus'] }}</p>
    </div>
  </div>
  <div class="coach-strip__item">
    <span class="coach-strip__icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
    </span>
    <div>
      <p class="coach-strip__label">Next session</p>
      <p class="coach-strip__value">{{ $player['next_session'] }}</p>
    </div>
  </div>
  <div class="coach-strip__item">
    <span class="coach-strip__icon coach-strip__icon--ok">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
    </span>
    <div>
      <p class="coach-strip__label">Subscription</p>
      <p class="coach-strip__value">{{ $player['plan_status'] }} · Renews {{ $player['plan_renews'] }}</p>
    </div>
  </div>
</section>

<div class="coach-layout-split">
  <div>
    <div class="coach-tabs" role="tablist" data-coach-tabs="player">
      <button type="button" role="tab" class="coach-tab is-active" data-coach-tab="overview" aria-selected="true">Overview</button>
      <button type="button" role="tab" class="coach-tab" data-coach-tab="history" aria-selected="false" tabindex="-1">Session History</button>
      <button type="button" role="tab" class="coach-tab" data-coach-tab="goals" aria-selected="false" tabindex="-1">Goals &amp; Feedback</button>
      <button type="button" role="tab" class="coach-tab" data-coach-tab="videos" aria-selected="false" tabindex="-1">Videos</button>
      <button type="button" role="tab" class="coach-tab" data-coach-tab="notes" aria-selected="false" tabindex="-1">Notes</button>
    </div>

    <div role="tabpanel" data-coach-panel="overview" data-coach-group="player">
      <div class="admin-card coach-panel-gap">
        <div class="admin-card-header">
          <div>
            <h2>Development Overview</h2>
            <p>Updated {{ $player['last_session'] }}</p>
          </div>
        </div>
        <div class="admin-card-body">
          <div class="coach-skills">
            @foreach ($skills as $skill)
              <div class="coach-skill">
                <span class="coach-skill__icon">@include('partials.coach.skill-icon', ['icon' => $skill['icon']])</span>
                <p class="coach-skill__name">{{ $skill['name'] }}</p>
                <p class="coach-skill__status" data-level="{{ $skill['level'] }}">{{ $skill['status'] }}</p>
                <span class="coach-dots">
                  @for ($i = 1; $i <= 4; $i++)
                    <span class="coach-dot" @if ($i <= $skill['level']) data-tone="{{ $skill['level'] }}" @endif></span>
                  @endfor
                </span>
              </div>
            @endforeach
          </div>
          <div class="coach-legend">
            <span class="coach-legend__item"><span class="coach-dot" data-tone="1"></span> Needs work</span>
            <span class="coach-legend__item"><span class="coach-dot" data-tone="2"></span> Developing</span>
            <span class="coach-legend__item"><span class="coach-dot" data-tone="3"></span> Strong</span>
            <span class="coach-legend__item"><span class="coach-dot" data-tone="4"></span> Excellent</span>
          </div>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-header">
          <div>
            <h2>Latest Report</h2>
            <p>Coach Lee · {{ $player['last_session'] }}</p>
          </div>
          <span class="admin-badge admin-badge-green">Complete</span>
        </div>
        <div class="admin-card-body">
          <div class="coach-focus">
            <p class="coach-focus__label">Focus of the week</p>
            <p class="coach-focus__text">{{ $player['current_focus'] }}</p>
          </div>
        </div>
      </div>
    </div>

    <div role="tabpanel" data-coach-panel="history" data-coach-group="player" hidden>
      <div class="admin-card">
        <div class="admin-card-header">
          <div>
            <h2>Recent Sessions</h2>
            <p>{{ $player['total_sessions'] }} total sessions</p>
          </div>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Session</th>
                <th>Focus</th>
                <th>Rating</th>
                <th>Summary</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($sessions as $session)
                <tr>
                  <td><strong>{{ $session['date'] }}</strong></td>
                  <td>{{ $session['type'] }}</td>
                  <td>{{ $session['focus'] }}</td>
                  <td>
                    <span class="coach-dots">
                      @for ($i = 1; $i <= 4; $i++)
                        <span class="coach-dot" @if ($i <= $session['rating']) data-tone="{{ $session['rating'] }}" @endif></span>
                      @endfor
                    </span>
                  </td>
                  <td>{{ $session['summary'] }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div role="tabpanel" data-coach-panel="goals" data-coach-group="player" hidden>
      <div class="admin-card">
        <div class="admin-card-header">
          <div>
            <h2>Goals from Player</h2>
            <p>What the player wants to work on</p>
          </div>
          <a href="{{ route('coach.add-report', ['player' => $player['slug']]) }}" class="admin-btn admin-btn-ghost admin-btn-sm">Add feedback</a>
        </div>
        <div class="admin-card-body">
          @foreach ($goals as $goal)
            <div class="coach-goal">
              <span class="coach-goal__box {{ $goal['done'] ? 'is-done' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M20 6 9 17l-5-5"/></svg>
              </span>
              <span class="coach-goal__text">{{ $goal['text'] }}</span>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div role="tabpanel" data-coach-panel="videos" data-coach-group="player" hidden>
      <div class="admin-card">
        <div class="admin-card-header">
          <div>
            <h2>Shared Videos</h2>
            <p>Training videos sent to this player</p>
          </div>
          <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Share video</button>
        </div>
        <div class="admin-card-body">
          @foreach ($videos as $video)
            <div class="coach-media">
              <span class="coach-media__thumb"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
              <div>
                <p class="coach-media__title">{{ $video['title'] }}</p>
                <p class="coach-media__meta">{{ $video['meta'] }}</p>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div role="tabpanel" data-coach-panel="notes" data-coach-group="player" hidden>
      <div class="admin-card">
        <div class="admin-card-header">
          <div>
            <h2>Coach Notes</h2>
            <p>Private notes visible only to you</p>
          </div>
          <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm">Add note</button>
        </div>
        <div class="admin-card-body">
          @foreach ($notes as $coachNote)
            <div class="coach-note">
              <div class="coach-note__head">
                <p class="coach-note__author">{{ $coachNote['author'] }}</p>
                <span class="coach-note__date">{{ $coachNote['date'] }}</span>
              </div>
              <p class="coach-note__text">{{ $coachNote['text'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>

  <aside class="coach-aside-panel">
    <div class="admin-card coach-panel-gap">
      <div class="admin-card-header">
        <div><h2>Quick Actions</h2></div>
      </div>
      <div class="admin-card-body coach-action-stack">
        <a href="{{ route('coach.add-report', ['player' => $player['slug']]) }}" class="admin-btn admin-btn-primary">Add Report</a>
        <button type="button" class="admin-btn admin-btn-ghost">Share Video</button>
        <button type="button" class="admin-btn admin-btn-ghost">Add Note</button>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-header">
        <div><h2>At a Glance</h2></div>
      </div>
      <div class="admin-card-body">
        <div class="admin-list">
          <div class="admin-list-item"><div><strong>{{ $player['total_sessions'] }}</strong><span>Total sessions</span></div></div>
          <div class="admin-list-item"><div><strong>{{ $player['progress'] }}</strong><span>Average progress</span></div></div>
          <div class="admin-list-item"><div><strong>{{ $player['streak'] }}</strong><span>Training streak</span></div></div>
          <div class="admin-list-item"><div><strong>{{ $player['last_session'] }}</strong><span>Last session</span></div></div>
        </div>
      </div>
    </div>
  </aside>
</div>

@include('partials.coach.subscription-note')
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-portal.js') }}"></script>
@endpush
