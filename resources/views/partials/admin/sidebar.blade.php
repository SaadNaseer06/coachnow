<aside class="admin-sidebar" id="adminSidebar">
  <a href="{{ route('admin.dashboard') }}" class="admin-brand">
    <img src="{{ asset('assets/logo.png') }}" alt="CoachNow">
    <span class="admin-brand-badge">Admin</span>
  </a>

  <nav class="admin-nav" aria-label="Admin">
    <div class="admin-nav-label">Overview</div>
    <a href="{{ route('admin.dashboard') }}" class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
      Dashboard
    </a>
    <a href="{{ route('admin.schedule') }}" class="admin-nav-link {{ request()->routeIs('admin.schedule') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      Schedule
    </a>

    <div class="admin-nav-label">Manage</div>
    <a href="{{ route('admin.coaches') }}" class="admin-nav-link {{ request()->routeIs('admin.coaches') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Coaches
    </a>
    <a href="{{ route('admin.bookings') }}" class="admin-nav-link {{ request()->routeIs('admin.bookings') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
      Bookings
    </a>
    <a href="{{ route('admin.locations') }}" class="admin-nav-link {{ request()->routeIs('admin.locations') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
      Locations
    </a>
    <a href="{{ route('admin.athletes') }}" class="admin-nav-link {{ request()->routeIs('admin.athletes') ? 'is-active' : '' }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Athletes
    </a>

    <div class="admin-nav-label">System</div>
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
      <div class="admin-user-avatar">CN</div>
      <div class="admin-user-meta">
        <div class="admin-user-name">Admin User</div>
        <div class="admin-user-role">Platform Admin</div>
      </div>
    </div>
  </div>
</aside>
