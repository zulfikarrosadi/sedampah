@extends('layouts.nature')

@section('title', 'Bukit Embun — Terbangun di Atas Lautan Kabut Pagi')

@section('content')
<!-- Hero dengan Tinggi Hampir Satu Layar -->
<section class="relative min-h-[88vh] flex items-center justify-center overflow-hidden bg-forest-900">
  @if($heroBanner)
  <img src="{{ $heroBanner->image_url }}" alt="{{ $heroBanner->title }}"
    class="absolute inset-0 w-full h-full object-cover object-center scale-105">
  @endif
  <div class="absolute inset-0 bg-gradient-to-b from-forest-900/75 via-forest-900/55 to-stone-50"></div>

  <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 text-center pt-12 pb-20">
    <span class="inline-block px-4 py-1.5 rounded-full bg-white/15 backdrop-blur-md text-amber-200 text-xs font-semibold uppercase tracking-widest border border-white/20 mb-6">
      Suaka Hutan Pinus Pegunungan &bull; 1.420 MDPL
    </span>
    <h1 class="font-serif text-4xl sm:text-6xl font-bold text-white leading-tight drop-shadow">
      Terbangun di Atas Hamparan <span class="italic text-golden-400">Kabut Pagi</span>
    </h1>
    <p class="mt-5 text-base sm:text-lg text-stone-100 max-w-2xl mx-auto leading-relaxed">
      Pilih lokasi perkemahan yang Anda inginkan di punggung bukit atau di antara hutan pinus melalui peta interaktif kami, tambahkan perlengkapan berkemah, dan dapatkan tiket masuk dengan kode QR secara instan.
    </p>

    <!-- Form Pencarian Ketersediaan -->
    <form action="{{ route('booking.index') }}" method="GET"
      class="mt-10 max-w-3xl mx-auto backdrop-blur-md bg-white/85 p-4 sm:p-5 rounded-2xl shadow-2xl border border-white/60 grid grid-cols-1 sm:grid-cols-4 gap-3 text-left">
      <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-forest-900 mb-1">Tanggal Kedatangan</label>
        <input type="date" name="check_in" value="{{ now()->addDay()->toDateString() }}" min="{{ now()->toDateString() }}" required
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
      </div>
      <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-forest-900 mb-1">Tanggal Kepulangan</label>
        <input type="date" name="check_out" value="{{ now()->addDays(2)->toDateString() }}" min="{{ now()->addDay()->toDateString() }}" required
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
      </div>
      <div>
        <label class="block text-[11px] font-bold uppercase tracking-wider text-forest-900 mb-1">Jumlah Peserta</label>
        <select name="guests" class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
          @foreach([1,2,3,4,5,6,8] as $g)
          <option value="{{ $g }}" @selected($g===2)>{{ $g }} orang</option>
          @endforeach
        </select>
      </div>
      <div class="flex items-end">
        <button type="submit"
          class="w-full h-[42px] rounded-xl bg-terracotta-600 hover:bg-terracotta-700 text-white font-semibold text-sm shadow-lg transition cursor-pointer">
          Jelajahi Peta &rarr;
        </button>
      </div>
    </form>
  </div>
</section>

<!-- Tiga Keunggulan Utama -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8 relative z-20">
  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-sand-50 rounded-2xl p-7 border border-amber-200/70 shadow-sm">
      <span class="text-xs font-bold uppercase tracking-wider text-terracotta-600">01 &bull; Pilih Lokasi Secara Interaktif</span>
      <h3 class="font-serif text-xl font-bold text-forest-900 mt-2">Pilih Lokasi Perkemahan Anda</h3>
      <p class="text-sm text-stone-600 mt-2 leading-relaxed">
        Lihat pemandangan matahari terbit dari punggung tebing, area teduh di antara hutan pinus, dan tempat menikmati langit berbintang secara langsung sebelum menentukan lokasi menginap.
      </p>
    </div>
    <div class="bg-white rounded-2xl p-7 border border-stone-200/80 shadow-sm">
      <span class="text-xs font-bold uppercase tracking-wider text-forest-800">02 &bull; Perlengkapan Siap Pakai</span>
      <h3 class="font-serif text-xl font-bold text-forest-900 mt-2">Penyewaan Perlengkapan Pilihan</h3>
      <p class="text-sm text-stone-600 mt-2 leading-relaxed">
        Bawa barang secukupnya—tambahkan tenda kubah tahan air untuk 4–6 orang, kantong tidur untuk suhu hingga -5&deg;C, lentera LED untuk penerangan, dan kayu bakar langsung ke dalam reservasi Anda.
      </p>
    </div>
    <div class="bg-forest-900 text-white rounded-2xl p-7 shadow-sm">
      <span class="text-xs font-bold uppercase tracking-wider text-golden-400">03 &bull; Masuk Tanpa Antre</span>
      <h3 class="font-serif text-xl font-bold mt-2">Tiket QR SHA-256 Instan</h3>
      <p class="text-sm text-emerald-100 mt-2 leading-relaxed">
        Setelah pembayaran Anda dikonfirmasi oleh Midtrans, tiket elektronik terenkripsi akan diterbitkan secara otomatis agar proses masuk di gerbang hanya membutuhkan sekitar 2 detik.
      </p>
    </div>
  </div>
</section>

<!-- Galeri Foto Dinamis dari Database dengan Tampilan Lightbox -->
<section id="gallery" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-24"
  x-data="{ activeCategory: 'all', lightbox: null }">
  <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 mb-10">
    <div>
      <p class="text-xs font-bold uppercase tracking-widest text-terracotta-600">Keindahan Alam</p>
      <h2 class="font-serif text-3xl sm:text-4xl font-bold text-forest-900 mt-1">Suasana di Bukit Embun</h2>
    </div>
    <div class="flex flex-wrap gap-2">
      @foreach(['all' => 'Semua Foto', 'camping_area' => 'Area Perkemahan', 'ambiance' => 'Suasana Alam', 'facilities' => 'Perlengkapan & Fasilitas'] as $key => $label)
      <button type="button"
        @click="activeCategory = '{{ $key }}'"
        :class="activeCategory === '{{ $key }}' ? 'bg-forest-900 text-white' : 'bg-white text-stone-700 border border-stone-200 hover:bg-stone-100'"
        class="px-4 py-2 rounded-xl text-xs font-semibold transition cursor-pointer">
        {{ $label }}
      </button>
      @endforeach
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
    @foreach($galleriesByCategory->flatten() as $photo)
    @if($photo->category !== 'hero')
    <div x-show="activeCategory === 'all' || activeCategory === '{{ $photo->category }}'"
      x-transition
      @click="lightbox = {{ Js::from($photo) }}"
      class="group relative rounded-2xl overflow-hidden bg-stone-200 aspect-[4/3] shadow-sm cursor-pointer">
      <img src="{{ $photo->thumbnail_url }}" alt="{{ $photo->title }}" loading="lazy"
        class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
      <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent opacity-90 p-4 flex flex-col justify-end">
        <span class="text-[10px] uppercase tracking-wider text-amber-300 font-semibold">{{ str_replace('_', ' ', $photo->category) }}</span>
        <h3 class="text-white font-semibold text-sm leading-snug">{{ $photo->title }}</h3>
        <p class="text-[11px] text-stone-300 mt-0.5">Foto oleh {{ $photo->photographer_name }}</p>
      </div>
    </div>
    @endif
    @endforeach
  </div>

  <!-- Modal Tampilan Foto -->
  <div x-show="lightbox" x-cloak @click.self="lightbox = null"
    class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl max-w-3xl w-full overflow-hidden shadow-2xl" x-if="lightbox">
      <img :src="lightbox.image_url" :alt="lightbox.title" class="w-full max-h-[70vh] object-cover">
      <div class="p-4 flex justify-between items-center">
        <div>
          <h4 class="font-serif font-bold text-lg text-forest-900" x-text="lightbox.title"></h4>
          <p class="text-xs text-stone-500">Diabadikan oleh <span x-text="lightbox.photographer_name"></span> melalui <span class="uppercase" x-text="lightbox.source_provider"></span></p>
        </div>
        <button @click="lightbox = null" class="px-4 py-2 rounded-xl bg-stone-100 hover:bg-stone-200 text-xs font-semibold">Tutup</button>
      </div>
    </div>
  </div>
</section>
@endsection