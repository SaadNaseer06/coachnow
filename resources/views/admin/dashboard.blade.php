@extends('layouts.admin')

@section('title', 'Admin Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Overview of coaches, bookings, and park activity')

@section('topbar_actions')
  <label class="admin-search">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" placeholder="Search coaches, bookings…">
  </label>
  <a href="{{ route('admin.coaches') }}" class="admin-btn admin-btn-primary">+ Add Coach</a>
@endsection

@section('content')
<section class="admin-kpi-grid">
  <article class="admin-kpi">
    <div class="admin-kpi-label">Active Coaches</div>
    <div class="admin-kpi-value">{{ $activeCoaches }}</div>
    <div class="admin-kpi-trend flat">Live on the platform</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Bookings Today</div>
    <div class="admin-kpi-value">{{ $bookingsToday }}</div>
    <div class="admin-kpi-trend flat">Scheduled for today</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Park Locations</div>
    <div class="admin-kpi-value">{{ $parkCount }}</div>
    <div class="admin-kpi-trend flat">
      {{ $topLocations->take(2)->pluck('name')->implode(' · ') }}{{ $parkCount > 2 ? ' · more' : '' }}
    </div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Revenue (MTD)</div>
    <div class="admin-kpi-value">${{ $revenueMtd >= 1000 ? number_format($revenueMtd / 1000, 1).'k' : number_format($revenueMtd, 0) }}</div>
    <div class="admin-kpi-trend flat">Non-cancelled bookings</div>
  </article>
</section>

<section class="admin-grid-2">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2>Recent Bookings</h2>
        <p>Latest sessions across Murrieta &amp; Temecula</p>
      </div>
      <a href="{{ route('admin.bookings') }}" class="admin-btn admin-btn-ghost admin-btn-sm">View all</a>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Athlete</th>
            <th>Coach</th>
            <th>Location</th>
            <th>When</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($recentBookings as $booking)
            @php
              $name = $booking->athlete_name ?? $booking->athlete?->name ?? 'Athlete';
              $initials = collect(explode(' ', $name))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
              $statusClass = match ($booking->status) {
                'confirmed' => 'admin-badge-green',
                'pending' => 'admin-badge-amber',
                'cancelled' => 'admin-badge-red',
                default => 'admin-badge-zinc',
              };
              $when = $booking->session_date?->isToday()
                ? 'Today'
                : ($booking->session_date?->isTomorrow() ? 'Tomorrow' : $booking->session_date?->format('D'));
              if ($booking->session_time) {
                $when .= ' · '.\Illuminate\Support\Carbon::parse($booking->session_time)->format('g:i A');
              }
            @endphp
            <tr>
              <td>
                <div class="admin-person">
                  <div class="admin-person-fallback">{{ $initials }}</div>
                  <div>
                    <strong>{{ $name }}</strong>
                    <span>{{ $booking->session_type }} · ${{ number_format((float) $booking->amount, 0) }}</span>
                  </div>
                </div>
              </td>
              <td>{{ $booking->coach?->display_name ?? '—' }}</td>
              <td>{{ $booking->location?->name ?? '—' }}</td>
              <td>{{ $when }}</td>
              <td><span class="admin-badge {{ $statusClass }}">{{ ucfirst($booking->status) }}</span></td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-zinc-500 py-6">No bookings yet.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2>Top Locations</h2>
        <p>Parks with the most coach activity</p>
      </div>
      <a href="{{ route('admin.locations') }}" class="admin-btn admin-btn-ghost admin-btn-sm">Manage</a>
    </div>
    <div class="admin-card-body">
      <div class="admin-list">
        @forelse ($topLocations as $location)
          @php
            $badge = $location->coaches_count >= 3 ? 'admin-badge-green' : ($location->coaches_count >= 2 ? 'admin-badge-zinc' : 'admin-badge-amber');
            $label = $location->coaches_count >= 3 ? 'Busy' : ($location->coaches_count >= 2 ? 'Steady' : 'Growing');
          @endphp
          <div class="admin-list-item">
            <div>
              <strong>{{ $location->name }}</strong>
              <span>{{ $location->coaches_count }} coach{{ $location->coaches_count === 1 ? '' : 'es' }} · {{ number_format((float) $location->distance_miles, 1) }} mi</span>
            </div>
            <span class="admin-badge {{ $badge }}">{{ $label }}</span>
          </div>
        @empty
          <p class="text-[13px] text-zinc-500">No locations yet.</p>
        @endforelse
      </div>
    </div>
  </div>
</section>

<section class="admin-card">
  <div class="admin-card-header">
    <div>
      <h2>Coach Approvals</h2>
      <p>New applications waiting for review</p>
    </div>
    <a href="{{ route('admin.coaches') }}" class="admin-btn admin-btn-ghost admin-btn-sm">Open queue</a>
  </div>
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Coach</th>
          <th>Specialty</th>
          <th>Preferred Park</th>
          <th>Submitted</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($pendingCoaches as $coach)
          @php $photo = $coach->photo_path ?: 'assets/Rectangle 8.png'; @endphp
          <tr>
            <td>
              <div class="admin-person">
                <img src="{{ asset($photo) }}" alt="">
                <div>
                  <strong>{{ $coach->display_name }}</strong>
                  <span>{{ $coach->user?->email ?? '—' }}</span>
                </div>
              </div>
            </td>
            <td>{{ $coach->specialty ?? '—' }}</td>
            <td>{{ $coach->location?->name ?? '—' }}</td>
            <td class="whitespace-nowrap">
              @if ($coach->created_at)
                <strong class="block text-[12px] text-[#191615] font-medium">{{ $coach->created_at->format('M j, Y') }}</strong>
                <span class="block text-[11px] text-zinc-500">{{ $coach->created_at->format('g:i A') }}</span>
              @else
                —
              @endif
            </td>
            <td class="whitespace-nowrap">
              <form method="POST" action="{{ route('admin.coaches.status', $coach) }}" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="active">
                <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm" data-loading-text="Approving…" @disabled(! $coach->isReadyForListing()) title="{{ $coach->isReadyForListing() ? 'Publish on Find a Coach' : 'Profile incomplete' }}">Approve</button>
              </form>
              <form method="POST" action="{{ route('admin.coaches.status', $coach) }}" class="inline">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="paused">
                <button type="submit" class="admin-btn admin-btn-ghost admin-btn-sm" data-loading-text="Declining…">Decline</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-zinc-500 py-6">No pending coach applications.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection
