@extends('layouts.admin')

@section('title', 'Coaches · Admin')
@section('page_title', 'Coaches')
@section('page_subtitle', 'Approve, edit, and assign coaches to park locations')

@section('topbar_actions')
  <button type="button" class="admin-btn admin-btn-primary" data-admin-modal-open="addCoachModal">+ Add Coach</button>
@endsection

@section('content')
@if ($errors->any())
  <div class="admin-alert admin-alert--error" role="alert">
    <span class="admin-alert__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>
    </span>
    <div class="admin-alert__body">
      <p class="admin-alert__label">Couldn’t save</p>
      <ul class="admin-alert__list">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
@endif

<div class="admin-toolbar">
  <form method="GET" action="{{ route('admin.coaches') }}" class="admin-filters" data-auto-filter>
    <input class="admin-input" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search coaches…" autocomplete="off">
    <select class="admin-select" name="status">
      <option value="">All statuses</option>
      <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
      <option value="pending" @selected(($filters['status'] ?? '') === 'pending')>Pending</option>
      <option value="paused" @selected(($filters['status'] ?? '') === 'paused')>Paused</option>
    </select>
    <select class="admin-select" name="location_id">
      <option value="">All locations</option>
      @foreach ($locations as $location)
        <option value="{{ $location->id }}" @selected((string) ($filters['location_id'] ?? '') === (string) $location->id)>{{ $location->name }}</option>
      @endforeach
      <option value="none" @selected(($filters['location_id'] ?? '') === 'none')>No location</option>
    </select>
  </form>
  <span class="text-[12px] text-zinc-500">{{ $coaches->total() }} coaches</span>
</div>

<div class="admin-card">
  <div class="admin-table-wrap">
    <table class="admin-table" id="coachesTable">
      <thead>
        <tr>
          <th>Coach</th>
          <th>Location</th>
          <th>Specialty</th>
          <th>Rate</th>
          <th>Rating</th>
          <th>Submitted</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($coaches as $coach)
          @php
            $statusClass = match ($coach->status) {
              'active' => 'admin-badge-green',
              'pending' => 'admin-badge-amber',
              default => 'admin-badge-zinc',
            };
            $locationName = $coach->location?->name ?? '';
          @endphp
          <tr>
            <td>
              <div class="admin-person">
                <img src="{{ $coach->photoUrl() }}" alt="">
                <div>
                  <strong>{{ $coach->display_name }}</strong>
                  <span>{{ $coach->user?->email ?? '—' }}</span>
                </div>
              </div>
            </td>
            <td>{{ $locationName !== '' ? $locationName : '—' }}</td>
            <td>{{ $coach->specialty ?? '—' }}</td>
            <td>{{ $coach->rate !== null ? '$'.number_format((float) $coach->rate, 0) : '—' }}</td>
            <td>{{ $coach->rating ? number_format((float) $coach->rating, 1) : '—' }}</td>
            <td class="whitespace-nowrap">
              @if ($coach->created_at)
                <strong class="block text-[12px] text-[#191615] font-medium">{{ $coach->created_at->format('M j, Y') }}</strong>
                <span class="block text-[11px] text-zinc-500">{{ $coach->created_at->format('g:i A') }}</span>
              @else
                —
              @endif
            </td>
            <td><span class="admin-badge {{ $statusClass }}">{{ ucfirst($coach->status) }}</span>
              @if ($coach->status !== 'active')
                <span class="block text-[10px] text-zinc-400 mt-1">Hidden on Find a Coach</span>
              @elseif (! $coach->isReadyForListing())
                <span class="block text-[10px] text-amber-600 mt-1">Profile incomplete</span>
              @endif
            </td>
            <td class="whitespace-nowrap">
              @if ($coach->status === 'pending')
                <form method="POST" action="{{ route('admin.coaches.status', $coach) }}" class="inline">
                  @csrf
                  @method('PATCH')
                  <input type="hidden" name="status" value="active">
                  <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm" data-loading-text="Approving…" @disabled(! $coach->isReadyForListing()) title="{{ $coach->isReadyForListing() ? 'Publish on Find a Coach' : 'Profile incomplete: '.implode(', ', $coach->missingListingFields()) }}">Approve</button>
                </form>
                <form method="POST" action="{{ route('admin.coaches.status', $coach) }}" class="inline">
                  @csrf
                  @method('PATCH')
                  <input type="hidden" name="status" value="paused">
                  <button type="submit" class="admin-btn admin-btn-ghost admin-btn-sm" data-loading-text="Declining…">Decline</button>
                </form>
              @else
                <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-admin-modal-open="editCoachModal-{{ $coach->id }}">Edit</button>
                @if ($coach->status === 'active')
                  <form method="POST" action="{{ route('admin.coaches.status', $coach) }}" class="inline" data-pause-form data-no-busy @if(($coach->upcoming_commitments_count ?? $coach->upcoming_bookings_count ?? 0) > 0) data-upcoming="{{ $coach->upcoming_commitments_count ?? $coach->upcoming_bookings_count }}" @endif>
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="paused">
                    <input type="hidden" name="force" value="0">
                    <button type="submit" class="admin-btn admin-btn-ghost admin-btn-sm" data-loading-text="Pausing…">Pause</button>
                  </form>
                @else
                  <form method="POST" action="{{ route('admin.coaches.status', $coach) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="active">
                    <button type="submit" class="admin-btn admin-btn-primary admin-btn-sm" data-loading-text="Activating…" @disabled(! $coach->isReadyForListing())>Activate</button>
                  </form>
                @endif
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" class="text-center text-zinc-500 py-8">No coaches match these filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.admin.pagination', ['paginator' => $coaches])
</div>

@foreach ($coaches as $coach)
  <div class="admin-modal" id="editCoachModal-{{ $coach->id }}" aria-hidden="true">
    <div class="admin-modal__backdrop" data-admin-modal-close></div>
    <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="editCoachTitle-{{ $coach->id }}">
      <div class="admin-modal__header">
        <div>
          <h2 id="editCoachTitle-{{ $coach->id }}">Edit {{ $coach->display_name }}</h2>
          <p>Update listing details and publish status.</p>
        </div>
        <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
      </div>
      <form method="POST" action="{{ route('admin.coaches.update', $coach) }}" class="admin-modal__body">
        @csrf
        @method('PATCH')
        <div class="admin-form-grid">
          <label class="admin-field">
            <span>Display name</span>
            <input class="admin-input" type="text" name="display_name" value="{{ old('display_name', $coach->display_name) }}" required maxlength="120">
          </label>
          <label class="admin-field">
            <span>Location</span>
            <select class="admin-select" name="location_id" required>
              <option value="">Select park…</option>
              @foreach ($locations as $location)
                <option value="{{ $location->id }}" @selected((string) old('location_id', $coach->location_id) === (string) $location->id)>{{ $location->name }}</option>
              @endforeach
            </select>
          </label>
          <label class="admin-field">
            <span>Sport</span>
            <select class="admin-select" name="sport" required>
              <option value="">Select sport…</option>
              @foreach (\App\Models\User::SPORTS as $sport)
                <option value="{{ $sport }}" @selected(old('sport', $coach->sport) === $sport)>{{ $sport }}</option>
              @endforeach
            </select>
          </label>
          <label class="admin-field">
            <span>Specialty</span>
            <select class="admin-select" name="specialty" required>
              <option value="">Select specialty…</option>
              @foreach (\App\Models\Coach::SPECIALTIES as $specialty)
                <option value="{{ $specialty }}" @selected(old('specialty', $coach->specialty) === $specialty)>{{ $specialty }}</option>
              @endforeach
            </select>
          </label>
          <label class="admin-field">
            <span>Ages</span>
            <input class="admin-input" type="text" name="ages" value="{{ old('ages', $coach->ages) }}" maxlength="80" placeholder="Ages 8–14">
          </label>
          <label class="admin-field">
            <span>Rate ($ / session)</span>
            <input class="admin-input" type="number" name="rate" value="{{ old('rate', $coach->rate !== null ? (int) $coach->rate : '50') }}" required min="0" max="9999" step="1">
          </label>
          <label class="admin-field">
            <span>Status</span>
            <select class="admin-select" name="status" required>
              <option value="active" @selected(old('status', $coach->status) === 'active')>Active</option>
              <option value="pending" @selected(old('status', $coach->status) === 'pending')>Pending</option>
              <option value="paused" @selected(old('status', $coach->status) === 'paused')>Paused</option>
            </select>
          </label>
          <label class="admin-field admin-field--full">
            <span>Experience</span>
            <select class="admin-select" name="experience">
              <option value="">Select…</option>
              @foreach (\App\Models\Coach::EXPERIENCE_OPTIONS as $option)
                <option value="{{ $option }}" @selected(old('experience', $coach->experience) === $option)>{{ $option }}</option>
              @endforeach
            </select>
          </label>
          <label class="admin-field admin-field--full">
            <span>Bio</span>
            <textarea class="admin-input admin-textarea" name="bio" rows="3" maxlength="2000">{{ old('bio', $coach->bio) }}</textarea>
          </label>
        </div>
        <div class="admin-modal__footer">
          <button type="button" class="admin-btn admin-btn-ghost" data-admin-modal-close>Cancel</button>
          <button type="submit" class="admin-btn admin-btn-primary" data-loading-text="Saving…">Save changes</button>
        </div>
      </form>
    </div>
  </div>
@endforeach

<div class="admin-modal{{ $errors->any() && ! old('_method') ? ' is-open' : '' }}" id="addCoachModal" aria-hidden="{{ $errors->any() && ! old('_method') ? 'false' : 'true' }}">
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="addCoachTitle">
    <div class="admin-modal__header">
      <div>
        <h2 id="addCoachTitle">Add Coach</h2>
        <p>Create a login and assign the coach to a park.</p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="POST" action="{{ route('admin.coaches.store') }}" class="admin-modal__body">
      @csrf
      <div class="admin-form-grid">
        <label class="admin-field">
          <span>Full name</span>
          <input class="admin-input" type="text" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="e.g. Alex Rivera">
        </label>
        <label class="admin-field">
          <span>Email</span>
          <input class="admin-input" type="email" name="email" value="{{ old('email') }}" required maxlength="255" placeholder="coach@email.com">
        </label>
        <label class="admin-field">
          <span>Password</span>
          <input class="admin-input" type="password" name="password" required minlength="8" placeholder="Min. 8 characters">
        </label>
        <label class="admin-field">
          <span>Location</span>
          <select class="admin-select" name="location_id" required>
            <option value="">Select park…</option>
            @foreach ($locations as $location)
              <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>{{ $location->name }}</option>
            @endforeach
          </select>
        </label>
        <label class="admin-field">
          <span>Sport</span>
          <select class="admin-select" name="sport" required>
            <option value="">Select sport…</option>
            @foreach (\App\Models\User::SPORTS as $sport)
              <option value="{{ $sport }}" @selected(old('sport') === $sport)>{{ $sport }}</option>
            @endforeach
          </select>
        </label>
        <label class="admin-field">
          <span>Specialty</span>
          <select class="admin-select" name="specialty" required>
            <option value="">Select specialty…</option>
            @foreach (\App\Models\Coach::SPECIALTIES as $specialty)
              <option value="{{ $specialty }}" @selected(old('specialty') === $specialty)>{{ $specialty }}</option>
            @endforeach
          </select>
        </label>
        <label class="admin-field">
          <span>Ages</span>
          <input class="admin-input" type="text" name="ages" value="{{ old('ages') }}" maxlength="80" placeholder="Ages 8–14">
        </label>
        <label class="admin-field">
          <span>Rate ($ / session)</span>
          <input class="admin-input" type="number" name="rate" value="{{ old('rate') }}" required min="0" max="9999" step="1" placeholder="50">
        </label>
        <label class="admin-field">
          <span>Status</span>
          <select class="admin-select" name="status" required>
            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
            <option value="pending" @selected(old('status') === 'pending')>Pending</option>
            <option value="paused" @selected(old('status') === 'paused')>Paused</option>
          </select>
        </label>
        <label class="admin-field admin-field--full">
          <span>Experience</span>
          <select class="admin-select" name="experience">
            <option value="">Select…</option>
            @foreach (\App\Models\Coach::EXPERIENCE_OPTIONS as $option)
              <option value="{{ $option }}" @selected(old('experience') === $option)>{{ $option }}</option>
            @endforeach
          </select>
        </label>
        <label class="admin-field admin-field--full">
          <span>Bio</span>
          <textarea class="admin-input admin-textarea" name="bio" rows="3" maxlength="2000" placeholder="Short intro for the public profile">{{ old('bio') }}</textarea>
        </label>
      </div>
      <div class="admin-modal__footer">
        <button type="button" class="admin-btn admin-btn-ghost" data-admin-modal-close>Cancel</button>
        <button type="submit" class="admin-btn admin-btn-primary" data-loading-text="Saving…">Save Coach</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('[data-pause-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
      if (form.dataset.pauseConfirmed === '1') return;
      event.preventDefault();
      window.CoachNowBusy?.clearFormBusy(form);

      const upcoming = Number(form.dataset.upcoming || 0);
      const forceInput = form.querySelector('input[name="force"]');
      let ok = true;

      if (upcoming > 0) {
        if (!window.CoachNowDialog?.confirm) {
          return;
        }

        ok = await window.CoachNowDialog.confirm({
          title: 'Pause this coach?',
          message: `This coach has ${upcoming} upcoming session${upcoming === 1 ? '' : 's'}. Pausing hides them from Find a Coach and suspends those sessions.`,
          confirmLabel: 'Pause and suspend',
          cancelLabel: 'Keep active',
        });
      }

      if (!ok) {
        window.CoachNowBusy?.clearFormBusy(form);
        return;
      }

      if (forceInput) forceInput.value = '1';
      form.dataset.pauseConfirmed = '1';
      const pauseBtn = event.submitter || form.querySelector('button[type="submit"]');
      window.CoachNowBusy?.setBusy(pauseBtn, { label: pauseBtn?.getAttribute('data-loading-text') || 'Pausing…' });
      form.submit();
    });
  });
</script>
@endpush
