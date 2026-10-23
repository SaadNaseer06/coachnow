@extends('layouts.app')

@section('title', 'Family Membership | CoachNow')
@section('meta_description', 'CoachNow Family Membership — no booking fees, session requests and coach development reports.')
@section('body_class', 'book-flow')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/book-coach.css') }}?v={{ @filemtime(public_path('assets/css/book-coach.css')) ?: time() }}">
@endpush

@section('content')
<main class="book-page">
  <section class="book-hero">
    <div class="book-wrap">
      <p class="book-kicker">Members feature</p>
      <h1>{{ $feature ?? 'This feature' }} is part of the Family Membership</h1>
      <p class="book-lead">Booking sessions and seeing your upcoming training stay free. Members get a little more.</p>
    </div>
  </section>

  <section class="book-body">
    <div class="book-wrap book-grid">
      <div class="book-panel">
        <div class="book-field is-group">
          <strong>Family Membership · ${{ number_format((float) config('coachnow.subscription.price', 10), 0) }}/month</strong>
          <div class="book-field__meta">
            <span>✓ No ${{ number_format((float) config('coachnow.payments.convenience_fee', 2), 0) }} booking fee on any checkout</span>
            <span>✓ Request a Session — post requests and join other families’ sessions</span>
            <span>✓ Coach development reports and skill progress</span>
            <span>✓ Cancel anytime — benefits last until the end of the month you paid for</span>
          </div>
        </div>
      </div>

      <aside class="book-aside">
        <div class="book-summary">
          <h2>Join now</h2>
          <form method="POST" action="{{ route('subscription.checkout') }}">
            @csrf
            <button type="submit" class="book-btn">Become a member</button>
          </form>
          <a href="{{ route('player-dashboard') }}" class="book-link">Back to my dashboard</a>
          <a href="{{ route('find-a-coach') }}" class="book-link">Book a coach (no membership needed)</a>
        </div>
      </aside>
    </div>
  </section>
</main>
@endsection
