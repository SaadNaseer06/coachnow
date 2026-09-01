@extends('layouts.coach')

@section('title', 'Add Report')
@section('page_title', 'Add Session Report')
@section('page_subtitle', 'Report for ' . $player['name'] . ' · ' . $player['age'] . ' · ' . $player['sport'])

@section('topbar_actions')
  <a href="{{ route('coach.players.show', $player['slug']) }}" class="admin-btn admin-btn-ghost">← Back to player</a>
  <button type="button" class="admin-btn admin-btn-primary">Save Report</button>
@endsection

@section('content')
<div class="coach-layout-split coach-layout-split--wide">
  <div class="admin-card">
    <div class="admin-card-header">
      <div>
        <h2>Session Report</h2>
        <p>Aug 4, 2026 · Private session · 60 min · Field A</p>
      </div>
    </div>
    <div class="admin-card-body">
      <div class="coach-form-block">
        <label class="coach-field-label" for="reportKeywords">Keywords worked on</label>
        <textarea id="reportKeywords" class="admin-input coach-textarea" placeholder="scanning, back foot touch, finishing…"></textarea>
        <p class="coach-field-hint">Use the training assistant to generate a draft, then edit the fields below.</p>
      </div>

      <div class="coach-form-block">
        <label class="coach-field-label" for="reportFocus">Focus of the week</label>
        <textarea id="reportFocus" class="admin-input coach-textarea coach-textarea--sm" placeholder="What should the player focus on this week?"></textarea>
      </div>

      <div class="coach-form-grid">
        <div>
          <label class="coach-field-label" for="reportWentWell">What went well</label>
          <textarea id="reportWentWell" class="admin-input coach-textarea" placeholder="Positive observations…"></textarea>
        </div>
        <div>
          <label class="coach-field-label" for="reportNeedsWork">Needs work</label>
          <textarea id="reportNeedsWork" class="admin-input coach-textarea" placeholder="Areas to improve…"></textarea>
        </div>
      </div>

      <div class="coach-form-block">
        <label class="coach-field-label" for="reportHome">Home training plan</label>
        <textarea id="reportHome" class="admin-input coach-textarea coach-textarea--lg" placeholder="Drills the player can do at home…"></textarea>
      </div>

      <div class="coach-form-actions">
        <button type="button" class="admin-btn admin-btn-primary">Save report</button>
        <button type="button" class="admin-btn admin-btn-ghost">Save &amp; share with player</button>
      </div>
    </div>
  </div>

  <aside class="coach-aside-panel">
    @include('partials.coach.ai-assistant')
  </aside>
</div>

@include('partials.coach.subscription-note', [
  'subscriptionNote' => 'AI-assisted session reports and home training plans are included with Development Plus subscriptions.',
])
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-portal.js') }}"></script>
@endpush
