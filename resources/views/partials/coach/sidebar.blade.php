<aside class="admin-sidebar" id="coachSidebar">
  <a href="{{ route('coach.dashboard') }}" class="admin-brand">
    <img src="{{ asset('assets/logo.png') }}" alt="CoachNow">
    <span class="admin-brand-label">Coach portal</span>
  </a>

  <nav class="admin-nav" aria-label="Coach dashboard">
    <div class="admin-nav-label">Overview</div>
    <a href="{{ route('coach.dashboard') }}" class="admin-nav-link {{ request()->routeIs('coach.dashboard') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
      Dashboard
    </a>
    <a href="{{ route('coach.schedule') }}" class="admin-nav-link {{ request()->routeIs('coach.schedule') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      My Schedule
    </a>

    <div class="admin-nav-label">Players</div>
    <a href="{{ route('coach.player-overview') }}" class="admin-nav-link {{ request()->routeIs('coach.player-overview', 'coach.players.show') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
      Player Overview
    </a>
    <a href="{{ route('coach.add-report') }}" class="admin-nav-link {{ request()->routeIs('coach.add-report') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15h6"/></svg>
      Add Report
    </a>

    <div class="admin-nav-label">Account</div>
    <a href="{{ route('home') }}" class="admin-nav-link">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      View Website
    </a>
    <a href="{{ route('login') }}" class="admin-nav-link">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Sign Out
    </a>
  </nav>

  <div class="admin-sidebar-footer">
    <div class="admin-user">
      <div class="admin-user-avatar">CL</div>
      <div class="admin-user-meta">
        <div class="admin-user-name">Coach Lee</div>
        <div class="admin-user-role">Development Plus</div>
      </div>
    </div>
  </div>
</aside>
