<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="{{ asset('assets/favicon.ico') }}" sizes="any">
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
  <title>@yield('title', 'Coach Dashboard') · CoachNow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['"Plus Jakarta Sans"', 'sans-serif'] },
          colors: {
            brand: {
              red: '#DA020C',
              'red-hover': '#BF020A',
              dark: '#191615'
            }
          }
        }
      }
    }
  </script>
  <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/coach-portal.css') }}">
  @stack('styles')
</head>
<body class="admin-body font-sans antialiased">
  <div id="coachSidebarBackdrop" class="admin-sidebar-backdrop" aria-hidden="true"></div>

  <div class="admin-shell">
    @include('partials.coach.sidebar')

    <div class="admin-main">
      <header class="admin-topbar">
        <div class="flex items-center gap-3 min-w-0">
          <button type="button" id="coachMenuBtn" class="admin-menu-btn" aria-label="Open menu">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <div class="admin-page-title">
            <h1>@yield('page_title')</h1>
            <p>@yield('page_subtitle')</p>
          </div>
        </div>
        <div class="admin-topbar-actions">
          @php $openSessionRequestCount = collect($sessionRequests ?? [])->where('status', 'open')->count(); @endphp
          <button
            type="button"
            id="coachSessionRequestsBell"
            class="coach-req-bell"
            aria-label="Open session requests"
            aria-expanded="false"
            aria-controls="coachSessionRequestsModal"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
              <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
            </svg>
            <span class="coach-req-bell__badge" id="coachSessionRequestsBadge" @if ($openSessionRequestCount === 0) hidden @endif>{{ $openSessionRequestCount }}</span>
          </button>
          @yield('topbar_actions')
        </div>
      </header>

      <main class="admin-content">
        @yield('content')
      </main>
    </div>
  </div>

  @include('partials.coach.session-requests-modal', ['sessionRequests' => $sessionRequests ?? []])

  <script src="{{ asset('assets/js/coach-dashboard.js') }}"></script>
  <script src="{{ asset('assets/js/coach-session-requests.js') }}"></script>
  @stack('scripts')
</body>
</html>
