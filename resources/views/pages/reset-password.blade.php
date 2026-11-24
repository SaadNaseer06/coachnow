@extends('layouts.app')

@section('title', 'Choose a new password | CoachNow')
@section('meta_description', 'Choose a new password for your CoachNow account.')

@push('styles')
  {{-- The address holds the reset token, so don't pass it on or index it. --}}
  <meta name="referrer" content="no-referrer">
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
@endpush

@section('content')
<main>
  @include('partials.auth-hero', ['title' => 'Choose a new password', 'lead' => 'Once it’s changed, you’ll sign in again on all your devices.'])

  <section class="py-20 lg:py-24 bg-[#F6F6F6] motion-section">
    <div class="max-w-[480px] mx-auto px-6">
      <div class="auth-card p-7 lg:p-8 motion-item motion-soft-up">
        <h2 class="text-[22px] font-semibold text-[#191615] mb-1">New password</h2>
        <p class="text-[13px] text-zinc-500 mb-6">Use at least 8 characters.</p>

        @if ($errors->any())
          <div class="mb-4 rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-700" role="alert">
            {{ $errors->first() }}
            @if (str_contains($errors->first(), 'no longer valid'))
              <a href="{{ route('password.request') }}" class="font-semibold underline">Send a new link</a>
            @endif
          </div>
        @endif

        <form action="{{ route('password.update') }}" method="post" class="space-y-4">
          @csrf
          <input type="hidden" name="token" value="{{ $token }}">
          <label class="block">
            <span class="block text-[12px] font-medium text-[#191615] mb-2">Email</span>
            <input type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="username" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">
          </label>
          <label class="block">
            <span class="block text-[12px] font-medium text-[#191615] mb-2">New password</span>
            <input type="password" name="password" required minlength="8" autofocus autocomplete="new-password" placeholder="At least 8 characters" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">
          </label>
          <label class="block">
            <span class="block text-[12px] font-medium text-[#191615] mb-2">Confirm new password</span>
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="••••••••" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">
          </label>
          <button type="submit" class="w-full h-11 rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-[13px] font-semibold transition-colors">Change password</button>
        </form>
      </div>
    </div>
  </section>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
@endpush
