@extends('layouts.admin')

@section('title', 'Locations · Admin')
@section('page_title', 'Locations')
@section('page_subtitle', 'Park locations shown on the homepage and search')

@section('topbar_actions')
  <button type="button" class="admin-btn admin-btn-primary" data-admin-modal-open="addLocationModal">+ Add Location</button>
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

<div class="admin-kpi-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
  <article class="admin-kpi">
    <div class="admin-kpi-label">Total Parks</div>
    <div class="admin-kpi-value">{{ $totalParks }}</div>
    <div class="admin-kpi-trend flat">Murrieta &amp; Temecula</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Assigned Coaches</div>
    <div class="admin-kpi-value">{{ $assignedCoaches }}</div>
    <div class="admin-kpi-trend flat">Across all parks</div>
  </article>
  <article class="admin-kpi">
    <div class="admin-kpi-label">Avg Distance</div>
    <div class="admin-kpi-value">{{ $avgDistance }}</div>
    <div class="admin-kpi-trend flat">miles from users</div>
  </article>
</div>

<div class="admin-card">
  <div class="admin-table-wrap">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Park</th>
          <th>Area</th>
          <th>Coords</th>
          <th>Coaches</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($locations as $location)
          <tr>
            <td><strong>{{ $location->name }}</strong></td>
            <td>{{ $location->area }}</td>
            <td>
              @if ($location->hasCoordinates())
                <span class="text-xs text-zinc-500">{{ number_format((float) $location->latitude, 4) }}, {{ number_format((float) $location->longitude, 4) }}</span>
              @else
                <span class="admin-badge admin-badge-amber">Missing</span>
              @endif
            </td>
            <td>
              @if ($location->coaches->isEmpty())
                —
              @else
                {{ $location->coaches->pluck('display_name')->map(fn ($n) => str_replace('Coach ', '', $n))->implode(' · ') }}
              @endif
            </td>
            <td>
              @if ($location->status === 'live')
                <span class="admin-badge admin-badge-green">Live</span>
              @else
                <span class="admin-badge admin-badge-amber">{{ ucfirst($location->status) }}</span>
              @endif
            </td>
            <td class="whitespace-nowrap">
              <div class="admin-row-actions">
                <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" disabled title="Coming soon">Edit</button>
                <button
                  type="button"
                  class="admin-btn admin-btn-ghost admin-btn-sm admin-btn-danger"
                  data-admin-modal-open="deleteLocationModal"
                  data-delete-url="{{ route('admin.locations.destroy', $location) }}"
                  data-delete-name="{{ $location->name }}"
                >Delete</button>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center text-zinc-500 py-8">No locations yet.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @include('partials.admin.pagination', ['paginator' => $locations])
</div>

<div class="admin-modal{{ $errors->any() ? ' is-open' : '' }}" id="addLocationModal" aria-hidden="{{ $errors->any() ? 'false' : 'true' }}">
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="addLocationTitle">
    <div class="admin-modal__header">
      <div>
        <h2 id="addLocationTitle">Add Location</h2>
        <p>Create a park shown on the homepage and Find a Coach.</p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="POST" action="{{ route('admin.locations.store') }}" class="admin-modal__body">
      @csrf
      <div class="admin-form-grid">
        <label class="admin-field">
          <span>Park name</span>
          <input class="admin-input" type="text" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="e.g. Maple Springs Park">
        </label>
        <label class="admin-field">
          <span>Area</span>
          <input class="admin-input" type="text" name="area" value="{{ old('area') }}" required maxlength="120" placeholder="e.g. Murrieta, CA">
        </label>
        <label class="admin-field">
          <span>Distance (miles)</span>
          <input class="admin-input" type="number" name="distance_miles" value="{{ old('distance_miles', '1.0') }}" required min="0" max="999" step="0.1">
        </label>
        <label class="admin-field">
          <span>Status</span>
          <select class="admin-select" name="status" required>
            <option value="live" @selected(old('status', 'live') === 'live')>Live</option>
            <option value="draft" @selected(old('status') === 'draft')>Draft</option>
          </select>
        </label>
      </div>
      <div class="admin-modal__footer">
        <button type="button" class="admin-btn admin-btn-ghost" data-admin-modal-close>Cancel</button>
        <button type="submit" class="admin-btn admin-btn-primary" data-loading-text="Saving…">Save Location</button>
      </div>
    </form>
  </div>
</div>

<div class="admin-modal" id="deleteLocationModal" aria-hidden="true">
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__panel admin-modal__panel--sm" role="dialog" aria-modal="true" aria-labelledby="deleteLocationTitle">
    <div class="admin-modal__header">
      <div>
        <h2 id="deleteLocationTitle">Delete location</h2>
        <p>This removes the park from the homepage and search.</p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
    </div>
    <form method="POST" action="" id="deleteLocationForm" class="admin-modal__body">
      @csrf
      @method('DELETE')
      <div class="admin-confirm">
        <span class="admin-confirm__icon" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>
        </span>
        <p class="admin-confirm__text">
          Delete <strong id="deleteLocationName">this location</strong>? Coaches assigned here will be unlinked from the park.
        </p>
      </div>
      <div class="admin-modal__footer">
        <button type="button" class="admin-btn admin-btn-ghost" data-admin-modal-close>Cancel</button>
        <button type="submit" class="admin-btn admin-btn-primary admin-btn-danger-solid" data-loading-text="Deleting…">Delete location</button>
      </div>
    </form>
  </div>
</div>
@endsection
