<!DOCTYPE html>
<html lang="en" class="hero-motion-pending">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="{{ asset('assets/favicon.ico') }}" sizes="any">
  <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon.svg') }}">
  <link rel="icon" type="image/png" href="{{ asset('assets/favicon-32.png') }}" sizes="32x32">
  <link rel="icon" type="image/png" href="{{ asset('assets/favicon-192.png') }}" sizes="192x192">
  <link rel="apple-touch-icon" href="{{ asset('assets/apple-touch-icon.png') }}">
  <title>@yield('title', 'CoachNow')</title>
  <meta name="description" content="@yield('meta_description', 'Discover and book vetted local soccer coaches tailored to your skill level, age group, and schedule.')">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600;1,700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
          },
          colors: {
            brand: {
              red: '#DA020C',
              'red-hover': '#BF020A',
              'red-light': '#FFF0F1',
              'red-subtle': 'rgba(218, 2, 12, 0.08)',
              dark: '#0C0D0E',
              surface: '#141618',
              'surface-2': '#1B1E22',
            }
          },
          boxShadow: {
            'brand-glow': '0 4px 20px rgba(218, 2, 12, 0.35)',
            'card-hover': '0 18px 36px rgba(0, 0, 0, 0.1)',
          }
        }
      }
    }
  </script>

  <link rel="stylesheet" href="https://unpkg.com/lenis@1.3.26/dist/lenis.css">
  <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}">
  @stack('styles')
</head>
<body class="font-sans bg-white text-zinc-900 antialiased selection:bg-brand-red selection:text-white">
  @include('partials.scroll-ui')
  @include('partials.preloader')
  @include('partials.header')

  @yield('content')

  @include('partials.footer')

  <script src="https://unpkg.com/lenis@1.3.26/dist/lenis.min.js"></script>
  <script src="{{ asset('assets/js/site.js') }}"></script>
  @stack('scripts')
</body>
</html>
