@extends('layouts.admin')

@section('title', 'Athletes · Admin')
@section('page_title', 'Athletes')
@section('page_subtitle', 'Families and players booking training sessions')

@section('content')
<div class="admin-toolbar">
  <form method="GET" action="{{ route('admin.athletes') }}" class="admin-filters" data-auto-filter>
    <input class="admin-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search athletes…" autocomplete="off">
    <select class="admin-select" name="activity">
      <option value="">All activity</option>
      <option value="month" @selected(($filters['activity'] ?? '') === 'month')>Active this month</option>
      <option value="new" @selected(($filters['activity'] ?? '') === 'new')>New</option>
    </select>
  </form>
  <span class="text-[12px] text-zinc-500">{{ $athletes->total() }} athletes</span>
</div>

<div class="admin-card">
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Athlete / Family</th>
          <th>Email</th>
          <th>Sport</th>
          <th>Sessions</th>
          <th>Last Booking</th>
          <th>Preferred Park</th>
          <th>Status</th>
          <th><span class="sr-only">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($athletes as $athlete)
          @php
            $initials = collect(explode(' ', $athlete->name))->map(fn ($p) => strtoupper(substr($p, 0, 1)))->take(2)->implode('');
            $last = $athlete->last_booking;
            $lastLabel = $last?->session_date?->isToday()
              ? 'Today'
              : ($last?->session_date?->isTomorrow() ? 'Tomorrow' : ($last?->session_date?->format('M j') ?? '—'));
            $isNew = ($athlete->sessions_count ?? 0) <= 3;
          @endphp
          <tr>
            <td>
              <div class="admin-person">
                <div class="admin-person-fallback">{{ $initials }}</div>
                <div>
                  <strong>{{ $athlete->name }}</strong>
                  <span>Player</span>
                </div>
              </div>
            </td>
            <td>{{ $athlete->email }}</td>
            <td>{{ $athlete->sport ?: '—' }}</td>
            <td>{{ $athlete->sessions_count ?? 0 }}</td>
            <td>{{ $lastLabel }}</td>
            <td>{{ $athlete->preferred_park ?? '—' }}</td>
            <td>
              @if ($isNew)
                <span class="admin-badge admin-badge-zinc">New</span>
              @else
                <span class="admin-badge admin-badge-green">Active</span>
              @endif
            </td>
            <td class="text-right">
              <form method="POST" action="{{ route('admin.athletes.destroy', $athlete) }}" data-remove-athlete data-name="{{ $athlete->name }}" data-email="{{ $athlete->email }}" data-sessions="{{ $athlete->sessions_count ?? 0 }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn admin-btn-ghost admin-btn-sm admin-btn-danger" data-loading-text="Removing…">Remove</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-zinc-500 py-8">No athletes match these filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.admin.pagination', ['paginator' => $athletes])
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-remove-athlete]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      if (form.dataset.confirmed === '1') return;
      event.preventDefault();
      window.CoachNowBusy?.clearFormBusy(form);

      const sessions = Number(form.dataset.sessions || 0);
      const message = `This permanently deletes ${form.dataset.name} (${form.dataset.email})`
        + (sessions ? `, with their ${sessions} booking${sessions === 1 ? '' : 's'} and any session requests.` : ' and any session requests.')
        + ' This can’t be undone.';
      const ok = window.CoachNowDialog?.confirm
        ? await window.CoachNowDialog.confirm({ title: 'Remove this account?', message, confirmLabel: 'Remove account', cancelLabel: 'Keep it' })
        : window.confirm(message);
      if (!ok) return;

      form.dataset.confirmed = '1';
      const btn = form.querySelector('button[type="submit"]');
      window.CoachNowBusy?.setBusy(btn, { label: 'Removing…' });
      form.submit();
    });
  });
</script>
@endpush
