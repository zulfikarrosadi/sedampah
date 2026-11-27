<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Bukit Embun — Bumi Perkemahan Dataran Tinggi')</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <style>
    [x-cloak] {
      display: none !important;
    }
  </style>
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
  <!-- Navigasi Kaca Mengambang -->
  <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-40 backdrop-blur-md bg-white/80 border-b border-stone-200/70">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
      <!-- Logo Merek -->
      <a href="{{ route('home') }}" class="flex items-center gap-2.5">
        <span class="w-9 h-9 rounded-xl bg-forest-900 text-amber-200 flex items-center justify-center font-serif font-bold text-lg shadow">BE</span>
        <div>
          <span class="font-serif font-bold text-lg text-forest-900 tracking-tight">Bukit Embun</span>
          <span class="hidden sm:inline-block text-xs text-stone-500 ml-2">Perkemahan Dataran Tinggi 1.420 mdpl</span>
        </div>
      </a>

      <!-- Navigasi Desktop -->
      <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
        <a href="{{ route('home') }}#gallery" class="text-stone-600 hover:text-forest-900 transition">Galeri</a>
        <a href="{{ route('booking.index') }}" class="text-stone-700 hover:text-forest-900 font-semibold transition">Reservasi</a>

        @auth
        <a href="{{ route('visitor.tickets') }}" class="text-stone-600 hover:text-forest-900 transition">Tiket Saya</a>

        @if(auth()->user()->isPetugas() || auth()->user()->isAdmin())
        <a href="{{ route('petugas.scan') }}" class="px-3 py-1.5 rounded-xl bg-emerald-100 text-forest-900 font-semibold hover:bg-emerald-200 transition">
          Pemindai QR
        </a>
        @endif

        @if(auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-xl bg-amber-100 text-amber-900 font-semibold hover:bg-amber-200 transition">
          Admin
        </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="inline">
          @csrf
          <button type="submit" class="text-xs text-stone-500 hover:text-red-600 transition">Keluar</button>
        </form>
        @else
        <a href="{{ route('login') }}" class="text-stone-600 hover:text-forest-900">Masuk</a>
        @if (Route::has('register'))
        <a href="{{ route('register') }}" class="text-forest-900 hover:text-terracotta-600 font-semibold">Daftar</a>
        @endif
        <a href="{{ route('booking.index') }}" class="px-4 py-2 rounded-xl bg-terracotta-600 text-white font-semibold shadow hover:bg-terracotta-700 transition">
          Reservasi Sekarang
        </a>
        @endauth
      </nav>

      <!-- Tombol Hamburger Seluler -->
      <div class="flex items-center md:hidden">
        <button
          @click="mobileMenuOpen = !mobileMenuOpen"
          type="button"
          class="p-2 rounded-xl text-stone-600 hover:text-forest-900 hover:bg-stone-100 focus:outline-none transition"
          aria-label="Alihkan menu navigasi">
          <!-- Ikon Hamburger -->
          <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <!-- Ikon Tutup (X) -->
          <svg x-cloak x-show="mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Menu Dropdown Seluler -->
    <div
      x-cloak
      x-show="mobileMenuOpen"
      @click.away="mobileMenuOpen = false"
      x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="opacity-0 -translate-y-2"
      x-transition:enter-end="opacity-100 translate-y-0"
      x-transition:leave="transition ease-in duration-150"
      x-transition:leave-start="opacity-100 translate-y-0"
      x-transition:leave-end="opacity-0 -translate-y-2"
      class="md:hidden bg-white/95 backdrop-blur-md border-b border-stone-200/70 px-4 pt-2 pb-5 space-y-2 shadow-lg">
      <a href="{{ route('home') }}#gallery" @click="mobileMenuOpen = false" class="block px-3 py-2 rounded-xl text-base font-medium text-stone-600 hover:text-forest-900 hover:bg-stone-100">
        Galeri
      </a>
      <a href="{{ route('booking.index') }}" class="block px-3 py-2 rounded-xl text-base font-semibold text-stone-800 hover:text-forest-900 hover:bg-stone-100">
        Reservasi
      </a>

      @auth
      <a href="{{ route('visitor.tickets') }}" class="block px-3 py-2 rounded-xl text-base font-medium text-stone-600 hover:text-forest-900 hover:bg-stone-100">
        Tiket Saya
      </a>

      @if(auth()->user()->isPetugas() || auth()->user()->isAdmin())
      <a href="{{ route('petugas.scan') }}" class="block px-3 py-2 rounded-xl text-base font-semibold bg-emerald-100 text-forest-900 hover:bg-emerald-200">
        Pemindai QR
      </a>
      @endif

      @if(auth()->user()->isAdmin())
      <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 rounded-xl text-base font-semibold bg-amber-100 text-amber-900 hover:bg-amber-200">
        Admin
      </a>
      @endif

      <div class="pt-2 border-t border-stone-200/70">
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" class="w-full text-left px-3 py-2 rounded-xl text-base font-medium text-red-600 hover:bg-red-50">
            Keluar
          </button>
        </form>
      </div>
      @else
      <div class="pt-2 border-t border-stone-200/70 flex flex-col gap-2">
        <a href="{{ route('login') }}" class="block px-3 py-2 rounded-xl text-base font-medium text-stone-600 hover:text-forest-900 hover:bg-stone-100">
          Masuk
        </a>
        @if (Route::has('register'))
        <a href="{{ route('register') }}" class="block px-3 py-2 rounded-xl text-base font-semibold text-forest-900 hover:text-terracotta-600 hover:bg-stone-100">
          Daftar
        </a>
        @endif
        <a href="{{ route('booking.index') }}" class="block text-center px-4 py-2.5 rounded-xl bg-terracotta-600 text-white font-semibold shadow hover:bg-terracotta-700 transition">
          Reservasi Sekarang
        </a>
      </div>
      @endauth
    </div>
  </header>

  <main>
    @yield('content')
  </main>

  <footer class="bg-forest-900 text-emerald-100 mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 flex flex-col md:flex-row justify-between items-center gap-4 text-sm">
      <div>
        <p class="font-serif text-xl font-bold text-white">Bumi Perkemahan Wisata Bukit Embun</p>
        <p class="text-emerald-300 text-xs mt-1">Sistem Reservasi Digital, Penyewaan Perlengkapan & Check-In QR Instan</p>
      </div>
      <p class="text-xs text-emerald-300">&copy; {{ date('Y') }} Bukit Embun Highland Sanctuary. Hak cipta dilindungi undang-undang.</p>
    </div>
  </footer>

  @stack('scripts')
</body>

</html>