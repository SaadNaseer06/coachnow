@extends('layouts.app')

@section('title', 'Forgot password | CoachNow')
@section('meta_description', 'Reset the password for your CoachNow account.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
@endpush

@section('content')
<main>
  @include('partials.auth-hero', ['title' => 'Forgot your password?', 'lead' => 'Enter your email and we’ll send you a link to choose a new one.'])

  <section class="py-20 lg:py-24 bg-[#F6F6F6] motion-section">
    <div class="max-w-[480px] mx-auto px-6">
      <div class="auth-card p-7 lg:p-8 motion-item motion-soft-up">
        <h2 class="text-[22px] font-semibold text-[#191615] mb-1">Reset password</h2>
        <p class="text-[13px] text-zinc-500 mb-6">Use the email you signed up with.</p>

        @if (session('status'))
          <div class="mb-4 rounded-[10px] border border-emerald-200 bg-emerald-50 px-4 py-3 text-[13px] text-emerald-800" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
          <div class="mb-4 rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-700" role="alert">{{ $errors->first() }}</div>
        @endif

        <form action="{{ route('password.email') }}" method="post" class="space-y-4">
          @csrf
          <label class="block">
            <span class="block text-[12px] font-medium text-[#191615] mb-2">Email</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="you@email.com" class="w-full h-11 rounded-[10px] border border-zinc-300 px-4 text-[13px] outline-none focus:border-brand-red focus:ring-2 focus:ring-brand-red/10">
          </label>
          <button type="submit" class="w-full h-11 rounded-full bg-brand-red hover:bg-brand-red-hover text-white text-[13px] font-semibold transition-colors">Send reset link</button>
        </form>

        <p class="mt-4 text-center text-[13px] text-zinc-500">Remembered it? <a href="{{ route('login') }}" class="text-brand-red font-medium hover:text-brand-red-hover">Back to login</a></p>
      </div>
    </div>
  </section>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
@endpush
