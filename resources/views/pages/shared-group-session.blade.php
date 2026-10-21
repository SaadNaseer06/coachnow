@extends('layouts.app')

@section('title', ($group->session_type ?: 'Group session').' with '.$coach->display_name.' | CoachNow')
@section('meta_description', 'Join this open group session with '.$coach->display_name.' — '.$meta['capacity_label'].'.')
@section('body_class', 'book-flow')

@push('meta')
  @php
    $ogTitle = ($group->session_type ?: 'Group training').' with '.$coach->display_name;
    $ogText = $meta['spots_remaining'].' spot'.($meta['spots_remaining'] === 1 ? '' : 's').' left · '
      .$group->session_date?->format('D, M j').' '.$group->timeLabel()
      .($group->age_range ? ' · '.$group->age_range : '')
      .($group->location ? ' · '.$group->location->name : '');
  @endphp
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="CoachNow">
  <meta property="og:title" content="{{ $ogTitle }}">
  <meta property="og:description" content="{{ $ogText }}">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:image" content="{{ $coach->photoUrl() }}">
  <meta name="twitter:card" content="summary">
@endpush

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/book-coach.css') }}?v={{ @filemtime(public_path('assets/css/book-coach.css')) ?: time() }}">
@endpush

@section('content')
<main class="book-page">
  <section class="book-hero">
    <div class="book-wrap">
      <p class="book-kicker">Shared group session</p>
      <h1>{{ $group->session_type ?: 'Group training' }}</h1>
      <p class="book-lead">
        {{ $coach->display_name }} ·
        {{ $group->session_date?->format('D, M j') }} at {{ $group->timeLabel() }}
      </p>
      <div class="book-coach-chip">
        <img src="{{ $coach->photoUrl() }}" alt="">
        <div>
          <strong>{{ $coach->display_name }}</strong>
          <span>{{ $meta['capacity_label'] }}@if($group->age_range) · {{ $group->age_range }}@endif</span>
        </div>
      </div>
    </div>
  </section>

  <section class="book-body">
    <div class="book-wrap book-grid">
      <div class="book-panel">
        <div class="book-field is-group">
          <strong>Group training</strong>
          <div class="book-field__meta">
            <span><strong>Date:</strong> {{ $group->session_date?->format('l, F j, Y') }}</span>
            <span><strong>Time:</strong> {{ $group->timeLabel() }}</span>
            <span><strong>Location:</strong> {{ $group->location?->name ?? 'Field TBD' }}@if($group->location?->area) · {{ $group->location->area }}@endif</span>
            @if($group->sportLabel())
              <span><strong>Sport:</strong> {{ $group->sportLabel() }}</span>
            @endif
            @if($group->age_range)
              <span><strong>Ages:</strong> {{ $group->age_range }}</span>
            @endif
            <span><strong>Price:</strong> ${{ number_format((float) ($group->price_per_player ?? $coach->rate ?? 0), 0) }} per player</span>
            <span><strong>Booked:</strong> {{ $meta['booked_count'] }} of {{ $meta['max_players'] }}</span>
            <span><strong>Available spaces:</strong> {{ $meta['spots_remaining'] }}</span>
            @if($convenienceFee > 0)
              <span><strong>CoachNow fee:</strong> ${{ number_format($convenienceFee, 0) }} per booking</span>
            @endif
          </div>
        </div>
      </div>

      <aside class="book-aside">
        <div class="book-summary">
          <h2>Join this session</h2>
          @if ($meta['joinable'])
            <p class="book-hint" style="margin-bottom:1rem">{{ $meta['spots_remaining'] }} spot{{ $meta['spots_remaining'] === 1 ? '' : 's' }} left. Choose how many players on the next screen — one CoachNow fee per booking.</p>
            @auth
              @if(auth()->user()->isAthlete())
                <a href="{{ $bookUrl }}" class="book-btn">Join Group Session</a>
              @else
                <p class="book-hint">Sign in as a parent/player account to join.</p>
              @endif
            @else
              <a href="{{ route('login') }}?redirect={{ urlencode($bookUrl) }}" class="book-btn">Sign in to join</a>
            @endauth
          @elseif ($meta['full'])
            <p class="book-error" style="display:block">This group session is full.</p>
          @else
            <p class="book-error" style="display:block">This session is no longer open to join.</p>
          @endif
          <a href="{{ route('coach-profile', $coach) }}" class="book-link">View coach profile</a>
        </div>
      </aside>
    </div>
  </section>
</main>
@endsection
