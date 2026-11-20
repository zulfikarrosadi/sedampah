<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Bukit Embun — Highland Camping Ground')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            serif: ['"Playfair Display"', 'Georgia', 'serif'],
            sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
          },
          colors: {
            forest: {
              900: '#1B4332',
              800: '#2D6A4F',
              100: '#D8F3DC'
            },
            terracotta: {
              600: '#BC6C25',
              700: '#9C5518'
            },
            golden: {
              400: '#DDA15E'
            },
            sand: {
              50: '#FEFAE0'
            },
          }
        }
      }
    }
  </script>
</head>

<body class="bg-stone-50 text-stone-800 font-sans antialiased selection:bg-forest-900 selection:text-white">
  <!-- Floating Glass Navigation -->
  <header class="sticky top-0 z-40 backdrop-blur-md bg-white/80 border-b border-stone-200/70">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <a href="{{ route('home') }}" class="flex items-center gap-2.5">
        <span class="w-9 h-9 rounded-xl bg-forest-900 text-amber-200 flex items-center justify-center font-serif font-bold text-lg shadow">BE</span>
        <div>
          <span class="font-serif font-bold text-lg text-forest-900 tracking-tight">Bukit Embun</span>
          <span class="hidden sm:inline-block text-xs text-stone-500 ml-2">1,420 mdpl Highland Camp</span>
        </div>
      </a>

      <nav class="flex items-center gap-3 sm:gap-6 text-sm font-medium">
        <a href="{{ route('home') }}#gallery" class="hidden md:inline text-stone-600 hover:text-forest-900">Gallery</a>
        <a href="{{ route('booking.index') }}" class="text-stone-700 hover:text-forest-900 font-semibold">Book Plot</a>

        @auth
        <a href="{{ route('visitor.tickets') }}" class="text-stone-600 hover:text-forest-900">My Tickets</a>

        @if(auth()->user()->isPetugas() || auth()->user()->isAdmin())
        <a href="{{ route('petugas.scan') }}" class="px-3 py-1.5 rounded-xl bg-emerald-100 text-forest-900 font-semibold hover:bg-emerald-200">
          QR Scanner
        </a>
        @endif

        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-xl bg-amber-100 text-amber-900 font-semibold hover:bg-amber-200">
          Admin
        </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="inline">
          @csrf
          <button type="submit" class="text-xs text-stone-500 hover:text-red-600">Logout</button>
        </form>
        @else
        <a href="{{ route('login') }}" class="text-stone-600 hover:text-forest-900">Sign In</a>
        <a href="{{ route('booking.index') }}" class="px-4 py-2 rounded-xl bg-terracotta-600 text-white font-semibold shadow hover:bg-terracotta-700 transition">
          Reserve Now
        </a>
        @endauth
      </nav>
    </div>
  </header>

  <main>
    @yield('content')
  </main>

  <footer class="bg-forest-900 text-emerald-100 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
      <div>
        <p class="font-serif text-xl font-bold text-white">Bukit Embun Tourism Camping Ground</p>
        <p class="text-emerald-300 text-xs mt-1">Digital Reservation, Gear Rental & Instant QR Check-In System</p>
      </div>
      <p class="text-xs text-emerald-300">&copy; {{ date('Y') }} Bukit Embun Highland Sanctuary. All rights reserved.</p>
    </div>
  </footer>

  @stack('scripts')
</body>

</html>