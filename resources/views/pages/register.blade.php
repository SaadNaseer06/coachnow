@extends('layouts.app')

@section('title', 'CoachNow - Sign Up')
@section('meta_description', 'Create your CoachNow account as a player or coach.')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/find-a-coach.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/inner-pages.css') }}">
@endpush

@section('content')
<main class="auth-page">
  <section id="hero" class="auth-hero relative pt-[106px] pb-28 lg:pb-36 flex items-end bg-zinc-950 text-white overflow-hidden" style="background-image: linear-gradient(90deg, rgba(12,13,14,0.82) 0%, rgba(12,13,14,0.58) 34%, rgba(12,13,14,0.12) 68%, rgba(12,13,14,0.02) 100%), linear-gradient(180deg, rgba(12,13,14,0.10) 0%, rgba(12,13,14,0.08) 43%, rgba(10,11,12,0.58) 100%), url('{{ asset("assets/hero-bg.png") }}'); background-size: cover; background-position: center bottom;">
    <div class="max-w-[1506px] mx-auto px-5 sm:px-8 lg:px-12 xl:px-16 2xl:px-20 w-full relative z-10 pb-8 lg:pb-10">
      <div class="hero-fade-target inline-flex items-center px-4 py-2 rounded-full bg-brand-red text-white text-xs sm:text-sm font-medium tracking-[0.01em] uppercase mb-4 shadow-[0_4px_14px_rgba(218,2,12,0.3)]" style="--hero-delay:40ms">GET STARTED</div>
      <h1 class="hero-fade-target max-w-[900px] text-4xl sm:text-5xl md:text-[3.2rem] lg:text-[3.5rem] font-medium tracking-[0.01em] text-white leading-none mb-4" style="--hero-delay:110ms">Create your account</h1>
      <p class="hero-fade-target text-[13px] sm:text-[14px] lg:text-[15px] text-zinc-200/90 max-w-[680px] leading-[1.55] font-light" style="--hero-delay:180ms">Sign up as a player or coach to access your dashboard and start training.</p>
    </div>
  </section>

  <section class="auth-form-section motion-section">
    <div class="auth-form-wrap">
      <div class="auth-card auth-card--signup p-7 sm:p-8 lg:p-9 motion-item motion-soft-up">
        <h2 class="text-[22px] font-semibold text-[#191615] mb-1">Sign up</h2>
        <p class="text-[13px] text-zinc-500 mb-6">Create a free CoachNow account in a minute.</p>

        @if ($errors->any())
          <div class="mb-4 rounded-[10px] border border-red-200 bg-red-50 px-4 py-3 text-[13px] text-red-700">
            {{ $errors->first() }}
          </div>
        @endif

        <form action="{{ route('register.store') }}" method="post" class="space-y-4" data-register-form>
          @csrf

          <div>
            <span class="block text-[12px] font-medium text-[#191615] mb-2">I am signing up as</span>
            <div class="grid grid-cols-2 gap-2 max-w-[320px]">
              <label class="auth-role-option">
                <input type="radio" name="role" value="athlete" class="sr-only" {{ old('role', 'athlete') === 'athlete' ? 'checked' : '' }} required data-register-role>
                <span class="auth-role-card">Player</span>
              </label>
              <label class="auth-role-option">
                <input type="radio" name="role" value="coach" class="sr-only" {{ old('role') === 'coach' ? 'checked' : '' }} data-register-role>
                <span class="auth-role-card">Coach</span>
              </label>
            </div>
          </div>

          <div class="auth-grid-2">
            <label class="block">
              <span class="block text-[12px] font-medium text-[#191615] mb-2">Full name</span>
              <input type="text" name="name" value="{{ old('name') }}" required autofocus placeholder="Your name" class="auth-input">
            </label>

            <label class="block">
              <span class="block text-[12px] font-medium text-[#191615] mb-2">Email</span>
              <input type="email" name="email" value="{{ old('email') }}" required placeholder="you@email.com" class="auth-input">
            </label>
          </div>

          <div data-coach-fields class="space-y-4 {{ old('role') === 'coach' ? '' : 'hidden' }}">
            <div class="auth-grid-2">
              <label class="block">
                <span class="block text-[12px] font-medium text-[#191615] mb-2">Specialty</span>
                <select name="specialty" class="auth-input appearance-none bg-white">
                  @foreach (\App\Models\Coach::SPECIALTIES as $specialty)
                    <option value="{{ $specialty }}" @selected(old('specialty', 'Private Soccer Training') === $specialty)>{{ $specialty }}</option>
                  @endforeach
                </select>
              </label>

              <label class="block">
                <span class="block text-[12px] font-medium text-[#191615] mb-2">Years of Experience</span>
                <select name="experience" class="auth-input appearance-none bg-white">
                  <option value="1-3 years" @selected(old('experience', '1-3 years') === '1-3 years')>1–3 years</option>
                  <option value="4-5 years" @selected(old('experience') === '4-5 years')>4–5 years</option>
                  <option value="6-7 years" @selected(old('experience') === '6-7 years')>6–7 years</option>
                  <option value="8+ years" @selected(old('experience') === '8+ years')>8+ years</option>
                </select>
              </label>
            </div>

            <label class="block">
              <span class="block text-[12px] font-medium text-[#191615] mb-2">Short Bio</span>
              <textarea name="bio" rows="3" placeholder="Share your coaching style and who you train" class="auth-textarea">{{ old('bio') }}</textarea>
            </label>
          </div>

          <label class="block" data-player-fields>
            <span class="block text-[12px] font-medium text-[#191615] mb-2">Phone <span class="text-zinc-400 font-normal">(optional)</span></span>
            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="555-0100" class="auth-input">
          </label>

          <div class="auth-grid-2">
            <label class="block">
              <span class="block text-[12px] font-medium text-[#191615] mb-2">Password</span>
              <input type="password" name="password" required placeholder="At least 8 characters" class="auth-input">
            </label>

            <label class="block">
              <span class="block text-[12px] font-medium text-[#191615] mb-2">Confirm password</span>
              <input type="password" name="password_confirmation" required placeholder="••••••••" class="auth-input">
            </label>
          </div>

          <button type="submit" class="auth-submit">Create account</button>
        </form>

        <p class="mt-5 text-center text-[13px] text-zinc-500">Already have an account? <a href="{{ route('login') }}" class="text-brand-red font-medium hover:text-brand-red-hover">Log in</a></p>
        <p class="mt-2 text-center text-[12px] text-zinc-400">Want to learn about coaching with us first? <a href="{{ route('become-a-coach') }}" class="text-brand-red font-medium hover:underline">Become a Coach</a></p>
      </div>
    </div>
  </section>
</main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/coach-profile.js') }}"></script>
  <script>
    (() => {
      const form = document.querySelector('[data-register-form]');
      if (!form) return;

      const roles = form.querySelectorAll('[data-register-role]');
      const coachFields = form.querySelector('[data-coach-fields]');
      const playerPhone = form.querySelector('[data-player-fields]');
      const coachInputs = coachFields?.querySelectorAll('select, textarea') || [];

      const sync = () => {
        const role = form.querySelector('[data-register-role]:checked')?.value || 'athlete';
        const isCoach = role === 'coach';

        coachFields?.classList.toggle('hidden', !isCoach);
        playerPhone?.classList.toggle('hidden', isCoach);

        coachInputs.forEach((el) => {
          if (isCoach) el.setAttribute('required', 'required');
          else el.removeAttribute('required');
        });
      };

      roles.forEach((input) => input.addEventListener('change', sync));
      sync();
    })();
  </script>
@endpush
