@extends('layouts.coach')

@section('title', 'My Profile')
@section('page_title', 'My Profile')
@section('page_subtitle', 'Set up the details that appear on Find a Coach')

@section('topbar_actions')
  <a href="{{ route('find-a-coach') }}" class="admin-btn admin-btn-ghost" target="_blank" rel="noopener">Preview listing</a>
@endsection

@section('content')
@php
  $statusClass = match ($coach->status) {
    'active' => 'admin-badge-green',
    'pending' => 'admin-badge-amber',
    default => 'admin-badge-zinc',
  };
@endphp

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

<section class="admin-card coach-profile-banner">
  <div class="coach-profile-banner__copy">
    <div class="flex items-center gap-2 flex-wrap">
      <h2>Listing status</h2>
      <span class="admin-badge {{ $statusClass }}">{{ ucfirst($coach->status) }}</span>
    </div>
    @if ($coach->status === 'active')
      <p>You’re live on Find a Coach. Keep your photo, rate, and park up to date.</p>
    @elseif ($isComplete)
      <p>Your profile looks ready. Waiting on admin approval before you appear in search.</p>
    @else
      <p>Complete the fields below so an admin can approve your listing. Still needed: {{ implode(', ', $missingFields) }}.</p>
    @endif
  </div>
  <div class="coach-profile-preview">
    <img id="coachPhotoPreview" src="{{ $coach->photoUrl() }}" alt="{{ $coach->display_name }}">
    <div>
      <strong>{{ $coach->display_name }}</strong>
      <span>{{ $coach->roleLabel() }}</span>
      <span>${{ $coach->rate !== null ? number_format((float) $coach->rate, 0) : '—' }} / session</span>
    </div>
  </div>
</section>

<form method="POST" action="{{ route('coach.profile.update') }}" enctype="multipart/form-data" class="admin-card">
  @csrf
  @method('PUT')

  <div class="admin-card-header">
    <div>
      <h2>Public profile</h2>
      <p>This is what athletes see on your Find a Coach card</p>
    </div>
  </div>

  <div class="admin-form-grid coach-profile-form">
    <div class="admin-field admin-field--full">
      <span>Profile photo</span>
      <div class="coach-photo-upload">
        <label class="coach-photo-upload__btn">
          <input id="coachPhotoInput" type="file" name="photo" accept="image/jpeg,image/png,image/webp" hidden>
          Upload photo
        </label>
        <p class="coach-photo-upload__hint">JPG, PNG, or WebP · max 4MB · square crop works best</p>
        @if ($coach->photo_path && ! str_starts_with((string) $coach->photo_path, 'assets/Rectangle'))
          <label class="coach-photo-upload__remove">
            <input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo'))>
            Remove current photo
          </label>
        @endif
      </div>
    </div>

    <label class="admin-field">
      <span>Display name</span>
      <input class="admin-input" type="text" name="display_name" value="{{ old('display_name', $coach->display_name) }}" required maxlength="120" placeholder="Coach Alex">
    </label>

    <label class="admin-field">
      <span>Specialty</span>
      <select class="admin-select" name="specialty" required>
        <option value="">Select specialty…</option>
        @foreach ($specialties as $specialty)
          <option value="{{ $specialty }}" @selected(old('specialty', $coach->specialty) === $specialty)>{{ $specialty }}</option>
        @endforeach
      </select>
    </label>

    <label class="admin-field">
      <span>Experience</span>
      <select class="admin-select" name="experience" required>
        <option value="">Select experience…</option>
        @foreach ($experienceOptions as $option)
          <option value="{{ $option }}" @selected(old('experience', $coach->experience) === $option)>{{ $option }}</option>
        @endforeach
      </select>
    </label>

    <label class="admin-field">
      <span>Ages you train</span>
      <select class="admin-select" name="ages" required>
        <option value="">Select ages…</option>
        @foreach ($ageOptions as $option)
          <option value="{{ $option }}" @selected(old('ages', $coach->ages) === $option)>{{ $option }}</option>
        @endforeach
      </select>
    </label>

    <label class="admin-field">
      <span>Primary park</span>
      <select class="admin-select" name="location_id" required>
        <option value="">Select park…</option>
        @foreach ($locations as $location)
          <option value="{{ $location->id }}" @selected((string) old('location_id', $coach->location_id) === (string) $location->id)>
            {{ $location->name }}{{ $location->area ? ' · '.$location->area : '' }}
          </option>
        @endforeach
      </select>
    </label>

    <label class="admin-field">
      <span>Rate ($ / session)</span>
      <input class="admin-input" type="number" name="rate" value="{{ old('rate', $coach->rate !== null ? (int) $coach->rate : '') }}" required min="0" max="9999" step="1" placeholder="60">
    </label>

    <label class="admin-field admin-field--full">
      <span>Bio</span>
      <textarea class="admin-input admin-textarea" name="bio" rows="4" maxlength="2000" placeholder="Short intro for athletes and parents">{{ old('bio', $coach->bio) }}</textarea>
    </label>
  </div>

  <div class="admin-modal__footer coach-profile-actions">
    <p class="text-[12px] text-zinc-500">
      @if ($coach->status === 'pending')
        Saving does not publish you. An admin still needs to approve.
      @else
        Changes appear on Find a Coach after you save.
      @endif
    </p>
    <button type="submit" class="admin-btn admin-btn-primary" data-loading-text="Saving…">Save profile</button>
  </div>
</form>
@endsection

@push('scripts')
<script>
  (function () {
    const input = document.getElementById('coachPhotoInput');
    const preview = document.getElementById('coachPhotoPreview');
    if (!input || !preview) return;

    input.addEventListener('change', function () {
      const file = input.files && input.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      preview.src = url;
    });
  })();
</script>
@endpush
