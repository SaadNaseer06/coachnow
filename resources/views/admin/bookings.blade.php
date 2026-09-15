@extends('layouts.admin')

@section('title', 'Bookings · Admin')
@section('page_title', 'Bookings')
@section('page_subtitle', 'Track sessions by date, coach, and park')

@section('content')
<div class="admin-toolbar">
  <form method="GET" action="{{ route('admin.bookings') }}" class="admin-filters" data-auto-filter>
    <input class="admin-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search bookings…" autocomplete="off">
    <select class="admin-select" name="status">
      <option value="">All statuses</option>
      <option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Confirmed</option>
      <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
      <option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Cancelled</option>
    </select>
    <select class="admin-select" name="range">
      <option value="">All dates</option>
      <option value="today" @selected(($filters['range'] ?? '') === 'today')>Today</option>
      <option value="week" @selected(($filters['range'] ?? '') === 'week')>This week</option>
      <option value="month" @selected(($filters['range'] ?? '') === 'month')>This month</option>
    </select>
  </form>
  <span class="text-[12px] text-zinc-500">{{ $bookings->total() }} bookings</span>
</div>

<div class="admin-card">
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Athlete</th>
          <th>Coach</th>
          <th>Location</th>
          <th>Session</th>
          <th>When</th>
          <th>Amount</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($bookings as $booking)
          @php
            $statusClass = match ($booking->status) {
              'confirmed' => 'admin-badge-green',
              'pending' => 'admin-badge-amber',
              'cancelled' => 'admin-badge-red',
              default => 'admin-badge-zinc',
            };
            $when = $booking->session_date?->format('M j');
            if ($booking->session_time) {
              $when .= ' · '.\Illuminate\Support\Carbon::parse($booking->session_time)->format('g:i A');
            }
          @endphp
          <tr>
            <td>#{{ $booking->reference }}</td>
            <td>{{ $booking->athlete_name ?? $booking->athlete?->name ?? '—' }}</td>
            <td>{{ $booking->coach?->display_name ?? '—' }}</td>
            <td>{{ $booking->location?->name ?? '—' }}</td>
            <td>{{ $booking->session_type ?? '—' }}</td>
            <td>{{ $when }}</td>
            <td>${{ number_format((float) $booking->amount, 0) }}</td>
            <td><span class="admin-badge {{ $statusClass }}">{{ ucfirst($booking->status) }}</span></td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-zinc-500 py-8">No bookings match these filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.admin.pagination', ['paginator' => $bookings])
</div>
@endsection
