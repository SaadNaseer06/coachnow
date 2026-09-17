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
  <button type="button" class="admin-btn admin-btn-ghost" data-admin-modal-open="shareVideoModal">Share Video</button>
@endsection

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/video-player-modal.css') }}?v={{ @filemtime(public_path('assets/css/video-player-modal.css')) ?: time() }}">
@endpush

@section('content')
@if (session('status'))
  <div class="admin-alert admin-alert--success mb-4" role="status">
    <span class="admin-alert__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
    </span>
    <div class="admin-alert__body">
      <p class="admin-alert__label">Saved</p>
      <p class="admin-alert__text">{{ session('status') }}</p>
    </div>
  </div>
@endif

@if ($errors->any())
  <div class="admin-alert admin-alert--error mb-4" role="alert">
    <span class="admin-alert__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
    </span>
    <div class="admin-alert__body">
      <p class="admin-alert__label">Couldn’t share video</p>
      <ul class="admin-alert__list">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
@endif

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
      <button type="button" role="tab" class="coach-tab {{ empty($openVideosTab) ? 'is-active' : '' }}" data-coach-tab="overview" aria-selected="{{ empty($openVideosTab) ? 'true' : 'false' }}" @if(!empty($openVideosTab)) tabindex="-1" @endif>Overview</button>
      <button type="button" role="tab" class="coach-tab" data-coach-tab="history" aria-selected="false" tabindex="-1">Session History</button>
      <button type="button" role="tab" class="coach-tab" data-coach-tab="goals" aria-selected="false" tabindex="-1">Goals &amp; Feedback</button>
      <button type="button" role="tab" class="coach-tab {{ !empty($openVideosTab) ? 'is-active' : '' }}" data-coach-tab="videos" aria-selected="{{ !empty($openVideosTab) ? 'true' : 'false' }}" @if(empty($openVideosTab)) tabindex="-1" @endif>Videos</button>
      <button type="button" role="tab" class="coach-tab" data-coach-tab="notes" aria-selected="false" tabindex="-1">Notes</button>
    </div>

    <div role="tabpanel" data-coach-panel="overview" data-coach-group="player" @if(!empty($openVideosTab)) hidden @endif>
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

    <div role="tabpanel" data-coach-panel="videos" data-coach-group="player" @if(empty($openVideosTab)) hidden @endif>
      <div class="admin-card">
        <div class="admin-card-header">
          <div>
            <h2>Shared Videos</h2>
            <p>Training videos sent to {{ $player['name'] }}</p>
          </div>
          <button type="button" class="admin-btn admin-btn-ghost admin-btn-sm" data-admin-modal-open="shareVideoModal">Share video</button>
        </div>
        <div class="admin-card-body">
          @forelse ($videos as $video)
            <div class="coach-media">
              <button
                type="button"
                class="coach-media__thumb coach-media__thumb--btn"
                data-play-video
                data-video-title="{{ $video['title'] }}"
                data-video-url="{{ $video['url'] }}"
                data-video-meta="{{ $video['meta'] }}"
                data-video-source="{{ !empty($video['is_upload']) ? 'upload' : 'url' }}"
                aria-label="Play {{ $video['title'] }}"
                @if (!empty($video['thumbnail'])) style="background-image:url('{{ $video['thumbnail'] }}')" @endif
              >
                <span class="coach-media__play"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
              </button>
              <div class="coach-media__body">
                <button
                  type="button"
                  class="coach-media__title coach-media__title--btn"
                  data-play-video
                  data-video-title="{{ $video['title'] }}"
                  data-video-url="{{ $video['url'] }}"
                  data-video-meta="{{ $video['meta'] }}"
                  data-video-source="{{ !empty($video['is_upload']) ? 'upload' : 'url' }}"
                >{{ $video['title'] }}</button>
                <p class="coach-media__meta">
                  {{ $video['meta'] }}
                  @if (!empty($video['is_upload']))
                    · Uploaded
                  @endif
                  @if (!empty($video['is_compressed']))
                    · Compressed
                  @endif
                  @if (!empty($video['shared_at']))
                    · {{ $video['shared_at'] }}
                  @endif
                </p>
              </div>
              <form method="POST" action="{{ route('coach.players.videos.destroy', ['player' => $player['slug'], 'video' => $video['id']]) }}" data-confirm-remove>
                @csrf
                @method('DELETE')
                <button type="submit" class="admin-btn admin-btn-ghost admin-btn-sm" data-loading-text="Removing…">Remove</button>
              </form>
            </div>
          @empty
            <div class="coach-media-empty">
              <p>No videos shared yet.</p>
              <button type="button" class="admin-btn admin-btn-primary admin-btn-sm" data-admin-modal-open="shareVideoModal">Share first video</button>
            </div>
          @endforelse
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
        <button type="button" class="admin-btn admin-btn-ghost" data-admin-modal-open="shareVideoModal">Share Video</button>
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

@include('partials.video-player-modal')

<div class="admin-modal{{ $errors->any() ? ' is-open' : '' }}" id="shareVideoModal" aria-hidden="{{ $errors->any() ? 'false' : 'true' }}">
  <div class="admin-modal__backdrop" data-admin-modal-close></div>
  <div class="admin-modal__panel" role="dialog" aria-modal="true" aria-labelledby="shareVideoTitle">
    <div class="admin-modal__header">
      <div>
        <h2 id="shareVideoTitle">Share video</h2>
        <p>Upload a file (compressed on the server) or paste a YouTube/Vimeo link for {{ $player['name'] }}</p>
      </div>
      <button type="button" class="admin-modal__close" data-admin-modal-close aria-label="Close">&times;</button>
    </div>
    <form
      method="POST"
      action="{{ route('coach.players.videos.store', $player['slug']) }}"
      class="admin-modal__body"
      enctype="multipart/form-data"
      data-share-video-form
      data-no-busy
    >
      @csrf
      <label class="admin-field">
        <span>Title</span>
        <input class="admin-input" type="text" name="title" value="{{ old('title') }}" required maxlength="120" placeholder="Scan before receiving">
      </label>

      <div class="admin-field admin-field--full">
        <span>Source</span>
        <div class="coach-video-source-toggle">
          <label class="coach-video-source-option">
            <input type="radio" name="source_type" value="upload" {{ old('source_type', 'upload') === 'upload' ? 'checked' : '' }} data-video-source>
            <span>Upload file</span>
          </label>
          <label class="coach-video-source-option">
            <input type="radio" name="source_type" value="url" {{ old('source_type') === 'url' ? 'checked' : '' }} data-video-source>
            <span>External link</span>
          </label>
        </div>
      </div>

      <label class="admin-field admin-field--full" data-video-panel="upload">
        <span>Video file (MP4, MOV, WebM — up to {{ number_format(config('coachnow.max_video_upload_kb', 102400) / 1024, 0) }} MB)</span>
        <input class="admin-input" type="file" name="video" accept="video/mp4,video/quicktime,video/webm,video/x-msvideo,video/x-matroska,.mp4,.mov,.webm,.avi,.mkv">
        <span class="coach-video-hint">Files are compressed to H.264 MP4 on the server before saving.</span>
      </label>

      <label class="admin-field admin-field--full" data-video-panel="url" hidden>
        <span>Video URL</span>
        <input class="admin-input" type="url" name="url" value="{{ old('url') }}" maxlength="500" placeholder="https://www.youtube.com/watch?v=…">
      </label>

      <label class="admin-field">
        <span>Short label (optional)</span>
        <input class="admin-input" type="text" name="duration_label" value="{{ old('duration_label') }}" maxlength="40" placeholder="3-min technique guide">
      </label>
      <label class="admin-field">
        <span>Skill tag (optional)</span>
        <input class="admin-input" type="text" name="skill_tag" value="{{ old('skill_tag') }}" maxlength="80" placeholder="First touch">
      </label>
      <label class="admin-field admin-field--full">
        <span>Notes (optional)</span>
        <textarea class="admin-input admin-textarea" name="description" rows="3" maxlength="255" placeholder="Why this video helps">{{ old('description') }}</textarea>
      </label>

      <div class="coach-upload-progress" data-upload-progress hidden>
        <div class="coach-upload-progress__head">
          <span data-upload-label>Uploading 0%</span>
          <span data-upload-pct>0%</span>
        </div>
        <div class="coach-upload-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-upload-track>
          <div class="coach-upload-progress__bar" data-upload-bar></div>
        </div>
        <p class="coach-upload-progress__meta" data-upload-meta></p>
      </div>

      <div class="admin-modal__footer">
        <button type="button" class="admin-btn admin-btn-ghost" data-upload-cancel>Cancel</button>
        <button type="submit" class="admin-btn admin-btn-primary" data-upload-submit>Share with player</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/admin.js') }}"></script>
  <script src="{{ asset('assets/js/coach-portal.js') }}"></script>
  <script src="{{ asset('assets/js/video-player-modal.js') }}?v={{ @filemtime(public_path('assets/js/video-player-modal.js')) ?: time() }}"></script>
  <script>
    (function () {
      const form = document.querySelector('[data-share-video-form]');
      if (!form) return;

      const progress = form.querySelector('[data-upload-progress]');
      const bar = form.querySelector('[data-upload-bar]');
      const track = form.querySelector('[data-upload-track]');
      const labelEl = form.querySelector('[data-upload-label]');
      const pctEl = form.querySelector('[data-upload-pct]');
      const metaEl = form.querySelector('[data-upload-meta]');
      const submitBtn = form.querySelector('[data-upload-submit]');
      const cancelBtn = form.querySelector('[data-upload-cancel]');
      let xhr = null;
      let busy = false;

      const formatBytes = (bytes) => {
        if (!Number.isFinite(bytes) || bytes < 0) return '0 B';
        if (bytes < 1024) return `${Math.round(bytes)} B`;
        if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
      };

      const formatSpeed = (bytesPerSec) => {
        if (!Number.isFinite(bytesPerSec) || bytesPerSec <= 0) return '';
        return `${formatBytes(bytesPerSec)}/s`;
      };

      const setProgress = (pct, label, meta) => {
        const value = Math.max(0, Math.min(100, Math.round(pct)));
        if (progress) progress.hidden = false;
        if (bar) bar.style.width = `${value}%`;
        if (track) track.setAttribute('aria-valuenow', String(value));
        if (pctEl) pctEl.textContent = `${value}%`;
        if (labelEl) labelEl.textContent = label || `Uploading ${value}%`;
        if (metaEl) metaEl.textContent = meta || '';
        progress?.classList.toggle('is-indeterminate', value >= 100 && /compress|process|sav/i.test(label || ''));
      };

      const resetProgress = () => {
        if (progress) progress.hidden = true;
        if (bar) bar.style.width = '0%';
        if (track) track.setAttribute('aria-valuenow', '0');
        if (pctEl) pctEl.textContent = '0%';
        if (labelEl) labelEl.textContent = 'Uploading 0%';
        if (metaEl) metaEl.textContent = '';
        progress?.classList.remove('is-indeterminate');
      };

      const setBusy = (on) => {
        busy = on;
        form.dataset.cnBusy = on ? '1' : '0';
        form.querySelectorAll('input, textarea, button[type="submit"]').forEach((el) => {
          if (el === cancelBtn) return;
          el.disabled = on;
        });
        if (submitBtn) {
          submitBtn.disabled = on;
          submitBtn.textContent = on ? 'Sharing…' : 'Share with player';
        }
        if (cancelBtn) cancelBtn.textContent = on ? 'Cancel upload' : 'Cancel';
      };

      const sync = () => {
        const selected = form.querySelector('[data-video-source]:checked')?.value || 'upload';
        form.querySelectorAll('[data-video-panel]').forEach((panel) => {
          const active = panel.getAttribute('data-video-panel') === selected;
          panel.hidden = !active;
          panel.querySelectorAll('input').forEach((input) => {
            input.required = active && (input.name === 'video' || input.name === 'url');
            if (!active && input.type === 'file') input.value = '';
          });
        });
      };

      form.querySelectorAll('[data-video-source]').forEach((input) => {
        input.addEventListener('change', sync);
      });
      sync();

      cancelBtn?.addEventListener('click', () => {
        if (busy && xhr) {
          xhr.abort();
          xhr = null;
          setBusy(false);
          resetProgress();
          return;
        }

        const modal = form.closest('.admin-modal');
        if (modal) {
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          document.body.classList.remove('admin-modal-open');
        }
      });

      form.addEventListener('submit', (event) => {
        event.preventDefault();
        if (busy) return;
        if (!form.reportValidity()) return;

        const source = form.querySelector('[data-video-source]:checked')?.value || 'upload';
        const fileInput = form.querySelector('input[name="video"]');
        const file = fileInput?.files?.[0] || null;

        if (source === 'upload' && !file) {
          fileInput?.focus();
          return;
        }

        const token = form.querySelector('input[name="_token"]')?.value
          || document.querySelector('meta[name="csrf-token"]')?.content
          || '';

        // FormData must be built before disabling fields — disabled inputs are omitted.
        const body = new FormData(form);

        setBusy(true);
        resetProgress();
        if (source === 'upload') {
          setProgress(0, 'Uploading 0%', file ? `${formatBytes(0)} of ${formatBytes(file.size)}` : '');
        } else {
          setProgress(10, 'Saving link…', '');
        }

        xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        if (token) xhr.setRequestHeader('X-CSRF-TOKEN', token);

        const startedAt = Date.now();
        let lastLoaded = 0;
        let lastAt = startedAt;

        xhr.upload.addEventListener('progress', (e) => {
          if (!e.lengthComputable) return;
          const pct = (e.loaded / e.total) * 100;
          const now = Date.now();
          const dt = Math.max((now - lastAt) / 1000, 0.05);
          const speed = (e.loaded - lastLoaded) / dt;
          lastLoaded = e.loaded;
          lastAt = now;

          const etaSec = speed > 0 ? Math.max(0, (e.total - e.loaded) / speed) : 0;
          const eta = etaSec > 0
            ? (etaSec >= 60 ? `${Math.ceil(etaSec / 60)} min left` : `${Math.ceil(etaSec)}s left`)
            : '';
          const speedLabel = formatSpeed(speed);
          const meta = [
            `${formatBytes(e.loaded)} of ${formatBytes(e.total)}`,
            speedLabel,
            eta,
          ].filter(Boolean).join(' · ');

          setProgress(Math.min(pct, 99), `Uploading ${Math.min(Math.round(pct), 99)}%`, meta);
        });

        xhr.upload.addEventListener('load', () => {
          if (source === 'upload') {
            setProgress(100, 'Compressing on server…', 'Upload complete — optimizing video');
          } else {
            setProgress(70, 'Saving…', '');
          }
        });

        xhr.addEventListener('load', () => {
          let payload = null;
          try {
            payload = JSON.parse(xhr.responseText || '{}');
          } catch (_) {
            payload = null;
          }

          if (xhr.status >= 200 && xhr.status < 300) {
            setProgress(100, 'Done', payload?.message || 'Video shared');
            const redirect = payload?.redirect || window.location.href;
            window.location.assign(redirect);
            return;
          }

          setBusy(false);
          resetProgress();

          let message = payload?.message || '';
          if (payload?.errors && typeof payload.errors === 'object') {
            const details = Object.values(payload.errors).flat().filter(Boolean);
            if (details.length) message = details.join('\n');
          }
          if (!message) {
            message = xhr.status === 413
              ? 'File is too large for the server.'
              : 'Could not share this video. Please try again.';
          }

          if (window.CoachNowDialog?.alert) {
            window.CoachNowDialog.alert({ title: 'Upload failed', message });
          } else {
            window.alert(message);
          }
        });

        xhr.addEventListener('error', () => {
          setBusy(false);
          resetProgress();
          const message = 'Network error while uploading. Check your connection and try again.';
          if (window.CoachNowDialog?.alert) {
            window.CoachNowDialog.alert({ title: 'Upload failed', message });
          } else {
            window.alert(message);
          }
        });

        xhr.addEventListener('abort', () => {
          setBusy(false);
          resetProgress();
        });

        xhr.send(body);
      });
    })();

    document.querySelectorAll('[data-confirm-remove]').forEach((form) => {
      form.addEventListener('submit', async (event) => {
        if (form.dataset.confirmed === '1') return;
        event.preventDefault();

        const ok = window.CoachNowDialog?.confirm
          ? await window.CoachNowDialog.confirm({
              title: 'Remove this video?',
              message: 'This shared video will be removed from the player’s library.',
              confirmLabel: 'Remove video',
              cancelLabel: 'Keep it',
            })
          : false;

        if (!ok) return;
        form.dataset.confirmed = '1';
        form.submit();
      });
    });
  </script>
@endpush
