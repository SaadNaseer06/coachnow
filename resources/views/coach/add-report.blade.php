@extends('layouts.coach')

@section('title', 'Add Report')
@section('page_title', 'Add Session Report')
@section('page_subtitle', 'Report for ' . $player['name'] . ' · ' . ($player['age'] ?? '—') . ' · ' . ($player['sport'] ?? '—'))

@section('topbar_actions')
  @if (! empty($player['slug']))
    <a href="{{ route('coach.players.show', $player['slug']) }}" class="admin-btn admin-btn-ghost">&larr; Back to player</a>
  @else
    <a href="{{ route('coach.player-overview') }}" class="admin-btn admin-btn-ghost">&larr; Back to players</a>
  @endif
  <button type="submit" form="sessionReportForm" class="admin-btn admin-btn-primary" data-share="0" data-loading-text="Saving…">Save Report</button>
@endsection

@section('content')
<div
  class="coach-layout-split coach-layout-split--wide"
  id="coachReportApp"
  data-generate-url="{{ $generateUrl }}"
  data-player-slug="{{ $player['slug'] ?? '' }}"
  data-ollama-ready="{{ !empty($ollamaReady) ? '1' : '0' }}"
>
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2>Session Report</h2>
        <p>{{ now()->format('M j, Y') }} · Private session · Development report</p>
      </div>
    </div>
    <div class="admin-card-body">
      @if ($errors->any())
        <div class="admin-alert admin-alert--error" style="margin-bottom:16px">
          {{ $errors->first() }}
        </div>
      @endif

      <form method="POST" action="{{ $storeUrl }}" id="sessionReportForm" data-no-busy>
        @csrf
        <input type="hidden" name="summary" id="reportSummary" value="{{ old('summary') }}">
        <input type="hidden" name="recommended_videos" id="reportVideosJson" value="{{ old('recommended_videos', '[]') }}">
        <input type="hidden" name="ai_source" id="reportAiSource" value="{{ old('ai_source') }}">
        <input type="hidden" name="share" id="reportShareFlag" value="0">

        @if (!empty($roster) && count($roster) > 1)
          <div class="coach-form-block">
            <label class="coach-field-label" for="reportPlayerSelect">Player</label>
            <select id="reportPlayerSelect" class="admin-input" name="player">
              @foreach ($roster as $option)
                <option value="{{ $option['slug'] }}" @selected(($player['slug'] ?? '') === ($option['slug'] ?? ''))>
                  {{ $option['name'] }}
                </option>
              @endforeach
            </select>
          </div>
        @else
          <input type="hidden" name="player" value="{{ $player['slug'] ?? '' }}">
        @endif

        <div class="coach-form-block">
          <label class="coach-field-label" for="reportWins">Wins (what went well)</label>
          <textarea id="reportWins" class="admin-input coach-textarea coach-textarea--sm" name="coach_notes_wins_input" placeholder="What did you actually see go well today?" required>{{ old('coach_notes_wins') }}</textarea>
        </div>

        <div class="coach-form-block">
          <label class="coach-field-label" for="reportWorkOns">Work-ons / needs improvement</label>
          <textarea id="reportWorkOns" class="admin-input coach-textarea coach-textarea--sm" name="coach_notes_work_ons_input" placeholder="What needs work based on this session?" required>{{ old('coach_notes_work_ons') }}</textarea>
        </div>

        <div class="coach-form-block">
          <label class="coach-field-label" for="reportFocusHint">Focus hint (optional)</label>
          <input id="reportFocusHint" class="admin-input" type="text" maxlength="1000" placeholder="Optional focus of the week hint">
        </div>

        <div class="coach-form-block">
          <label class="coach-field-label" for="reportKeywords">Session keywords (optional)</label>
          <div class="coach-keyword-row">
            <input id="reportKeywords" class="admin-input" type="text" name="keywords" value="{{ old('keywords') }}" placeholder="e.g. first touch, scanning" maxlength="255">
            <button type="button" class="admin-btn admin-btn-primary" id="reportGenerateBtn" data-loading-text="Generating…">Generate with AI</button>
          </div>
          <p class="coach-field-hint">
            @if (!empty($ollamaReady))
              AI uses your wins / work-ons notes so the draft matches what you saw.
            @else
              AI is not configured — a template draft will still use your wins / work-ons notes.
            @endif
          </p>
        </div>

        <input type="hidden" name="coach_notes_wins" id="reportNotesWins" value="{{ old('coach_notes_wins') }}">
        <input type="hidden" name="coach_notes_work_ons" id="reportNotesWorkOns" value="{{ old('coach_notes_work_ons') }}">

        <div class="coach-form-block">
          <label class="coach-field-label" for="reportFocus">Focus of the week</label>
          <textarea id="reportFocus" name="focus" class="admin-input coach-textarea coach-textarea--sm" placeholder="What should the player focus on this week?" required>{{ old('focus') }}</textarea>
        </div>

        <div class="coach-form-grid">
          <div>
            <label class="coach-field-label" for="reportWentWell">What went well</label>
            <textarea id="reportWentWell" name="went_well" class="admin-input coach-textarea" placeholder="Positive observations…" required>{{ old('went_well') }}</textarea>
          </div>
          <div>
            <label class="coach-field-label" for="reportNeedsWork">Needs work</label>
            <textarea id="reportNeedsWork" name="needs_work" class="admin-input coach-textarea" placeholder="Areas to improve…" required>{{ old('needs_work') }}</textarea>
          </div>
        </div>

        <div class="coach-form-block">
          <label class="coach-field-label" for="reportHome">Home training plan</label>
          <textarea id="reportHome" name="home_plan" class="admin-input coach-textarea coach-textarea--lg" placeholder="Drills the player can do at home…" required>{{ old('home_plan') }}</textarea>
        </div>

        <div class="coach-form-actions">
          <button type="submit" class="admin-btn admin-btn-primary" data-share="0" data-loading-text="Saving…">Save report</button>
          <button type="submit" class="admin-btn admin-btn-ghost" data-share="1" data-loading-text="Sharing…">Save &amp; share with player</button>
        </div>
      </form>
    </div>
  </div>

  <aside class="coach-aside-panel">
    @include('partials.coach.ai-assistant')
  </aside>
</div>

@include('partials.coach.subscription-note', [
  'subscriptionNote' => 'AI drafts stay private on your machine. Review every report before sharing with the player.',
])
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-portal.js') }}?v={{ @filemtime(public_path('assets/js/coach-portal.js')) ?: time() }}"></script>
@endpush
