@extends('layouts.nature')

@section('title', 'Panel Kontrol Admin — Bukit Embun')

@section('content')
<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-8"
  x-data="adminHub({
         plots: @js($campingAreas),
         defaultTab: @js($errors->has('image_file') || $errors->has('image_url') ? 'galleries' : ($errors->has('rental_price') && !$errors->has('capacity') ? 'facilities' : 'plots'))
     })">

  <!-- Header & Ekspor -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="font-serif text-3xl font-bold text-forest-900">Pusat Operasional Admin</h1>
      <p class="text-sm text-stone-500">Kelola 30+ kapling perkemahan, koordinat SVG, stok perlengkapan, unggahan galeri lokal, dan transaksi.</p>
    </div>
    <a href="{{ route('admin.export.csv') }}" class="px-4 py-2.5 rounded-xl bg-forest-900 text-white text-xs font-semibold shadow hover:bg-forest-800">
      Ekspor Transaksi CSV
    </a>
  </div>

  <!-- Notifikasi Flash & Validasi -->
  @if(session('success'))
  <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-medium">
    {{ session('success') }}
  </div>
  @endif
  @if(session('error'))
  <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-sm font-medium">
    {{ session('error') }}
  </div>
  @endif
  @if($errors->any())
  <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 text-xs space-y-1">
    <p class="font-bold">Harap perbaiki kesalahan input berikut:</p>
    <ul class="list-disc pl-5">
      @foreach($errors->all() as $err)
      <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  <!-- Kartu KPI -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-5">
    <div class="bg-white p-5 rounded-2xl border border-stone-200">
      <p class="text-xs font-bold uppercase text-stone-400">Pendapatan Lunas</p>
      <p class="text-2xl font-bold text-forest-900 mt-1">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-stone-200">
      <p class="text-xs font-bold uppercase text-stone-400">Pemesanan Lunas</p>
      <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $stats['settled_bookings'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-stone-200">
      <p class="text-xs font-bold uppercase text-stone-400">Menunggu Pembayaran</p>
      <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['pending_bookings'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-stone-200">
      <p class="text-xs font-bold uppercase text-stone-400">Total Kapling Kemah</p>
      <p class="text-2xl font-bold text-stone-900 mt-1">{{ $stats['total_plots'] }}</p>
    </div>
  </div>

  <!-- Tab Navigasi -->
  <div class="flex flex-wrap gap-2 border-b border-stone-200 pb-3">
    <button type="button" @click="activeTab = 'plots'"
      :class="activeTab === 'plots' ? 'bg-forest-900 text-white' : 'bg-white text-stone-700 border border-stone-200'"
      class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
      Kapling Kemah ({{ $campingAreas->count() }})
    </button>
    <button type="button" @click="activeTab = 'facilities'"
      :class="activeTab === 'facilities' ? 'bg-forest-900 text-white' : 'bg-white text-stone-700 border border-stone-200'"
      class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
      Perlengkapan &amp; Alat ({{ $facilities->count() }})
    </button>
    <button type="button" @click="activeTab = 'galleries'"
      :class="activeTab === 'galleries' ? 'bg-forest-900 text-white' : 'bg-white text-stone-700 border border-stone-200'"
      class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
      Media Galeri ({{ $galleries->count() }})
    </button>
    <button type="button" @click="activeTab = 'transactions'"
      :class="activeTab === 'transactions' ? 'bg-forest-900 text-white' : 'bg-white text-stone-700 border border-stone-200'"
      class="px-4 py-2 rounded-xl text-xs font-bold transition cursor-pointer">
      Transaksi
    </button>
  </div>

  <!-- TAB 1: CRUD KAPLING KEMAH + PEMILIH KOORDINAT INTERAKTIF -->
  <div x-show="activeTab === 'plots'" class="space-y-8">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 bg-white rounded-2xl p-6 shadow-sm border border-stone-200">
      <!-- Formulir Tambah Kapling Baru -->
      <div class="lg:col-span-5 space-y-4">
        <div>
          <h2 class="font-serif text-xl font-bold text-forest-900">Tambah Kapling Kemah Baru</h2>
          <p class="text-xs text-stone-500">Klik di mana saja pada peta pratinjau 1200&times;800 untuk mengatur koordinat X &amp; Y.</p>
        </div>

        <form method="POST" action="{{ route('admin.areas.store') }}" class="grid grid-cols-2 gap-3 text-xs">
          @csrf
          <div class="col-span-2">
            <label class="block font-bold text-stone-600 mb-1">Nama Kapling</label>
            <input name="name" placeholder="mis., Sunrise Ridge A9" required class="w-full rounded-xl border-stone-300 text-xs">
          </div>
          <div>
            <label class="block font-bold text-stone-600 mb-1">Zona</label>
            <select name="zone" class="w-full rounded-xl border-stone-300 text-xs">
              <option value="Sunrise Ridge">Sunrise Ridge</option>
              <option value="Pine Grove">Pine Grove</option>
              <option value="Mist Valley">Mist Valley</option>
              <option value="Starlight Plateau">Starlight Plateau</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-stone-600 mb-1">Ketinggian</label>
            <input name="elevation" value="1,410 mdpl" class="w-full rounded-xl border-stone-300 text-xs">
          </div>
          <div>
            <label class="block font-bold text-stone-600 mb-1">Maks. Tamu</label>
            <input type="number" name="capacity" value="4" min="1" required class="w-full rounded-xl border-stone-300 text-xs">
          </div>
          <div>
            <label class="block font-bold text-stone-600 mb-1">Harga / Malam (Rp)</label>
            <input type="number" name="price_per_night" value="175000" min="0" required class="w-full rounded-xl border-stone-300 text-xs">
          </div>
          <div>
            <label class="block font-bold text-stone-600 mb-1">Peta X (0–1140)</label>
            <input type="number" name="x" x-model.number="newPlot.x" min="0" max="1140" required class="w-full rounded-xl border-stone-300 text-xs font-mono">
          </div>
          <div>
            <label class="block font-bold text-stone-600 mb-1">Peta Y (0–740)</label>
            <input type="number" name="y" x-model.number="newPlot.y" min="0" max="740" required class="w-full rounded-xl border-stone-300 text-xs font-mono">
          </div>
          <div>
            <label class="block font-bold text-stone-600 mb-1">Lebar (px)</label>
            <input type="number" name="width" x-model.number="newPlot.width" min="40" max="300" required class="w-full rounded-xl border-stone-300 text-xs font-mono">
          </div>
          <div>
            <label class="block font-bold text-stone-600 mb-1">Tinggi (px)</label>
            <input type="number" name="height" x-model.number="newPlot.height" min="35" max="200" required class="w-full rounded-xl border-stone-300 text-xs font-mono">
          </div>
          <div class="col-span-2">
            <label class="block font-bold text-stone-600 mb-1">Status &amp; Deskripsi</label>
            <div class="flex gap-2">
              <select name="status" class="rounded-xl border-stone-300 text-xs">
                <option value="available">tersedia</option>
                <option value="maintenance">perbaikan</option>
              </select>
              <input name="description" placeholder="Deskripsi singkat pemandangan..." class="flex-1 rounded-xl border-stone-300 text-xs">
            </div>
          </div>
          <div class="col-span-2 pt-1">
            <button type="submit" class="w-full py-2.5 rounded-xl bg-terracotta-600 hover:bg-terracotta-700 text-white font-bold text-xs shadow cursor-pointer">
              + Buat Kapling Kemah
            </button>
          </div>
        </form>
      </div>

      <!-- Kanvas Pemilih Koordinat Interaktif 1200x800 -->
      <div class="lg:col-span-7">
        <div class="flex items-center justify-between text-xs mb-2">
          <span class="font-bold text-forest-900">Klik Peta untuk Menentukan Posisi Kapling Baru</span>
          <span class="font-mono text-stone-500" x-text="`Target: X=${newPlot.x}, Y=${newPlot.y} (${newPlot.width}×${newPlot.height})`"></span>
        </div>
        <div class="rounded-2xl border border-emerald-900/20 bg-[#eaf4ec] overflow-hidden">
          <svg viewBox="0 0 1200 800" @click="pickCoordinate($event)" class="w-full h-auto cursor-crosshair select-none">
            <rect x="450" y="65" width="710" height="205" rx="20" fill="#d8f3dc" opacity="0.5" />
            <rect x="50" y="285" width="595" height="210" rx="20" fill="#d8f3dc" opacity="0.5" />
            <rect x="670" y="285" width="490" height="210" rx="20" fill="#d8f3dc" opacity="0.5" />
            <rect x="430" y="540" width="730" height="225" rx="20" fill="#d8f3dc" opacity="0.5" />

            <!-- Kapling yang Sudah Ada -->
            @foreach($campingAreas as $area)
            @php $c = $area->layout_coordinates; @endphp
            <g transform="translate({{ $c['x'] ?? 0 }}, {{ $c['y'] ?? 0 }})">
              <rect width="{{ $c['width'] ?? 100 }}" height="{{ $c['height'] ?? 64 }}" rx="10"
                fill="{{ $area->status === 'available' ? '#2D6A4F' : '#94a3b8' }}" opacity="0.85" />
              <text x="{{ ($c['width'] ?? 100) / 2 }}" y="{{ (($c['height'] ?? 64) / 2) + 4 }}"
                text-anchor="middle" font-size="11" fill="#ffffff" font-weight="bold">
                {{ \Illuminate\Support\Str::afterLast($area->name, ' ') }}
              </text>
            </g>
            @endforeach

            <!-- Penanda Pratinjau Emas Langsung untuk Kapling Baru -->
            <g :transform="`translate(${newPlot.x}, ${newPlot.y})`">
              <rect :width="newPlot.width" :height="newPlot.height" rx="10"
                fill="#DDA15E" stroke="#9C5518" stroke-width="4" stroke-dasharray="6 4" />
              <text :x="newPlot.width / 2" :y="(newPlot.height / 2) + 4"
                text-anchor="middle" font-size="12" fill="#1B4332" font-weight="bold">BARU</text>
            </g>
          </svg>
        </div>
      </div>
    </div>

    <!-- Tabel Kapling yang Sudah Ada (Ubah Langsung + Hapus) -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200">
      <h3 class="font-serif text-xl font-bold text-forest-900 mb-4">Semua Kapling Kemah</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="uppercase text-stone-400 border-b">
            <tr>
              <th class="py-2">Nama</th>
              <th>Zona</th>
              <th>Kap.</th>
              <th>Harga/Malam</th>
              <th>X / Y / L / T</th>
              <th>Status</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-stone-100">
            @foreach($campingAreas as $area)
            @php $c = $area->layout_coordinates ?? []; @endphp
            <tr>
              <td class="py-2 pr-2">
                <form id="update-area-{{ $area->id }}" method="POST" action="{{ route('admin.areas.update', $area) }}">
                  @csrf @method('PATCH')
                </form>
                <input form="update-area-{{ $area->id }}" name="name" value="{{ $area->name }}" class="rounded-lg border-stone-300 text-xs w-40">
              </td>
              <td class="pr-2"><input form="update-area-{{ $area->id }}" name="zone" value="{{ $c['zone'] ?? '' }}" class="rounded-lg border-stone-300 text-xs w-32"></td>
              <td class="pr-2"><input form="update-area-{{ $area->id }}" type="number" name="capacity" value="{{ $area->capacity }}" class="rounded-lg border-stone-300 text-xs w-14"></td>
              <td class="pr-2"><input form="update-area-{{ $area->id }}" type="number" name="price_per_night" value="{{ (int) $area->price_per_night }}" class="rounded-lg border-stone-300 text-xs w-24"></td>
              <td class="pr-2 py-2">
                <div class="flex gap-1">
                  <input form="update-area-{{ $area->id }}" type="number" name="x" value="{{ $c['x'] ?? 0 }}" class="rounded-lg border-stone-300 text-xs w-14 font-mono" title="X">
                  <input form="update-area-{{ $area->id }}" type="number" name="y" value="{{ $c['y'] ?? 0 }}" class="rounded-lg border-stone-300 text-xs w-14 font-mono" title="Y">
                  <input form="update-area-{{ $area->id }}" type="number" name="width" value="{{ $c['width'] ?? 100 }}" class="rounded-lg border-stone-300 text-xs w-14 font-mono" title="Lebar">
                  <input form="update-area-{{ $area->id }}" type="number" name="height" value="{{ $c['height'] ?? 64 }}" class="rounded-lg border-stone-300 text-xs w-14 font-mono" title="Tinggi">
                </div>
              </td>
              <td class="pr-2">
                <select form="update-area-{{ $area->id }}" name="status" class="rounded-lg border-stone-300 text-xs">
                  <option value="available" @selected($area->status === 'available')>tersedia</option>
                  <option value="maintenance" @selected($area->status === 'maintenance')>perbaikan</option>
                </select>
              </td>
              <td class="text-right whitespace-nowrap space-x-1">
                <button form="update-area-{{ $area->id }}" type="submit" class="px-3 py-1.5 rounded-lg bg-forest-900 text-white font-semibold hover:bg-forest-800">Simpan</button>
                <form method="POST" action="{{ route('admin.areas.destroy', $area) }}" class="inline" onsubmit="return confirm('Hapus kapling {{ $area->name }}?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-700 font-semibold hover:bg-red-100">Hapus</button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- TAB 2: CRUD PERLENGKAPAN & ALAT -->
  <div x-show="activeTab === 'facilities'" x-cloak class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200 space-y-6">
    <div>
      <h2 class="font-serif text-xl font-bold text-forest-900">Inventaris Perlengkapan &amp; Sewa</h2>
      <p class="text-xs text-stone-500">Tambahkan perlengkapan berkemah baru, sesuaikan stok fisik, atau perbarui tarif sewa per malam.</p>
    </div>

    <!-- Formulir Tambah Perlengkapan -->
    <form method="POST" action="{{ route('admin.facilities.store') }}" class="grid grid-cols-1 sm:grid-cols-6 gap-3 bg-stone-50 p-4 rounded-xl border border-stone-200/70 text-xs">
      @csrf
      <input name="name" placeholder="Nama Perlengkapan (mis., Hammock Double)" required class="sm:col-span-2 rounded-xl border-stone-300 text-xs">
      <select name="category" class="rounded-xl border-stone-300 text-xs">
        <option value="tent">tenda</option>
        <option value="equipment">perlengkapan</option>
        <option value="lamp">lampu</option>
        <option value="cooking">alat masak</option>
      </select>
      <input type="number" name="stock" placeholder="Stok" min="0" required class="rounded-xl border-stone-300 text-xs">
      <input type="number" name="rental_price" placeholder="Harga (Rp)" min="0" required class="rounded-xl border-stone-300 text-xs">
      <div class="flex gap-2">
        <input name="unit" value="unit" placeholder="Satuan" required class="w-20 rounded-xl border-stone-300 text-xs">
        <button type="submit" class="flex-1 rounded-xl bg-terracotta-600 text-white font-bold hover:bg-terracotta-700">+ Tambah</button>
      </div>
    </form>

    <!-- Tabel Perlengkapan -->
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="uppercase text-stone-400 border-b">
          <tr>
            <th class="py-2">Nama Perlengkapan</th>
            <th>Kategori</th>
            <th>Stok</th>
            <th>Harga Sewa (Rp)</th>
            <th>Satuan</th>
            <th class="text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
          @foreach($facilities as $facility)
          <tr>
            <td class="py-2.5 pr-2">
              <form id="update-fac-{{ $facility->id }}" method="POST" action="{{ route('admin.facilities.update', $facility) }}">
                @csrf @method('PATCH')
              </form>
              <input form="update-fac-{{ $facility->id }}" name="name" value="{{ $facility->name }}" class="rounded-lg border-stone-300 text-xs w-60">
            </td>
            <td class="pr-2"><input form="update-fac-{{ $facility->id }}" name="category" value="{{ $facility->category }}" class="rounded-lg border-stone-300 text-xs w-28"></td>
            <td class="pr-2"><input form="update-fac-{{ $facility->id }}" type="number" name="stock" value="{{ $facility->stock }}" class="rounded-lg border-stone-300 text-xs w-20"></td>
            <td class="pr-2"><input form="update-fac-{{ $facility->id }}" type="number" name="rental_price" value="{{ (int) $facility->rental_price }}" class="rounded-lg border-stone-300 text-xs w-28"></td>
            <td class="pr-2"><input form="update-fac-{{ $facility->id }}" name="unit" value="{{ $facility->unit }}" class="rounded-lg border-stone-300 text-xs w-20"></td>
            <td class="text-right whitespace-nowrap space-x-1">
              <button form="update-fac-{{ $facility->id }}" type="submit" class="px-3 py-1.5 rounded-lg bg-forest-900 text-white font-semibold hover:bg-forest-800">Simpan</button>
              <form method="POST" action="{{ route('admin.facilities.destroy', $facility) }}" class="inline" onsubmit="return confirm('Hapus {{ $facility->name }}?')">
                @csrf @method('DELETE')
                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-red-50 text-red-700 font-semibold hover:bg-red-100">Hapus</button>
              </form>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- TAB 3: UNGGAH & CRUD MEDIA GALERI -->
  <div x-show="activeTab === 'galleries'" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-8"
    x-data="{ uploadMode: 'file', previewUrl: null }">

    <!-- Formulir Unggah -->
    <div class="lg:col-span-5 bg-white rounded-2xl p-6 shadow-sm border border-stone-200 space-y-4">
      <h2 class="font-serif text-xl font-bold text-forest-900">Unggah Foto Galeri</h2>

      <div class="flex gap-2 text-xs font-semibold">
        <button type="button" @click="uploadMode = 'file'"
          :class="uploadMode === 'file' ? 'bg-forest-900 text-white' : 'bg-stone-100 text-stone-700'"
          class="px-3 py-1.5 rounded-xl">Unggah Gambar Lokal</button>
        <button type="button" @click="uploadMode = 'url'"
          :class="uploadMode === 'url' ? 'bg-forest-900 text-white' : 'bg-stone-100 text-stone-700'"
          class="px-3 py-1.5 rounded-xl">URL Stok Eksternal</button>
      </div>

      <form method="POST" action="{{ route('admin.galleries.store') }}" enctype="multipart/form-data" class="space-y-3 text-xs">
        @csrf
        <input type="hidden" name="upload_mode" :value="uploadMode">

        <div>
          <label class="block font-bold text-stone-600 mb-1">Judul Foto</label>
          <input name="title" placeholder="mis., Kabut Pagi di Ridge A1" required class="w-full rounded-xl border-stone-300 text-xs">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-bold text-stone-600 mb-1">Kategori</label>
            <select name="category" class="w-full rounded-xl border-stone-300 text-xs">
              <option value="camping_area">area_kemah</option>
              <option value="ambiance">suasana</option>
              <option value="facilities">fasilitas</option>
              <option value="hero">utama</option>
            </select>
          </div>
          <div>
            <label class="block font-bold text-stone-600 mb-1">Fotografer / Kredit</label>
            <input name="photographer_name" placeholder="Tim Bukit Embun" class="w-full rounded-xl border-stone-300 text-xs">
          </div>
        </div>

        <!-- Input Unggah Berkas -->
        <div x-show="uploadMode === 'file'" class="space-y-2">
          <label class="block font-bold text-stone-600">Pilih Berkas Gambar (JPG, PNG, WEBP &bull; Maks. 5MB)</label>
          <input type="file" name="image_file" accept="image/jpeg,image/png,image/webp"
            @change="previewUrl = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
            class="w-full text-xs file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:bg-emerald-50 file:text-forest-900 file:font-semibold">
          <img x-show="previewUrl" :src="previewUrl" class="w-full h-40 object-cover rounded-xl border border-stone-200 mt-2">
        </div>

        <!-- Input URL Eksternal -->
        <div x-show="uploadMode === 'url'" class="space-y-2">
          <input name="image_url" placeholder="URL Gambar Lengkap (https://...)" class="w-full rounded-xl border-stone-300 text-xs">
          <input name="thumbnail_url" placeholder="URL Thumbnail (opsional)" class="w-full rounded-xl border-stone-300 text-xs">
        </div>

        <label class="flex items-center gap-2 pt-1 cursor-pointer">
          <input type="checkbox" name="is_featured" value="1" class="rounded border-stone-300 text-forest-900">
          <span class="font-semibold text-stone-700">Jadikan Unggulan (Hero / Sorotan Utama)</span>
        </label>

        <button type="submit" class="w-full py-2.5 rounded-xl bg-terracotta-600 hover:bg-terracotta-700 text-white font-bold text-xs shadow cursor-pointer">
          Terbitkan ke Galeri
        </button>
      </form>
    </div>

    <!-- Grid Galeri -->
    <div class="lg:col-span-7 bg-white rounded-2xl p-6 shadow-sm border border-stone-200">
      <h2 class="font-serif text-xl font-bold text-forest-900 mb-4">Aset Galeri Terbit</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[560px] overflow-y-auto pr-1">
        @foreach($galleries as $photo)
        <div class="rounded-xl border border-stone-200 overflow-hidden bg-stone-50 flex flex-col">
          <img src="{{ $photo->thumbnail_url }}" alt="{{ $photo->title }}" class="w-full h-36 object-cover">
          <div class="p-3 flex-1 flex flex-col justify-between text-xs">
            <div>
              <div class="flex items-center justify-between gap-1">
                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 font-bold uppercase text-[10px]">{{ $photo->category }}</span>
                <span class="text-[10px] uppercase text-stone-400 font-semibold">{{ $photo->source_provider }}</span>
              </div>
              <p class="font-bold text-stone-900 mt-1.5">{{ $photo->title }}</p>
              <p class="text-[11px] text-stone-500">Oleh {{ $photo->photographer_name }}</p>
            </div>
            <div class="flex items-center justify-between pt-3 mt-2 border-t border-stone-200/70">
              <form method="POST" action="{{ route('admin.galleries.toggle-featured', $photo) }}">
                @csrf @method('PATCH')
                <button type="submit" class="font-semibold {{ $photo->is_featured ? 'text-amber-600' : 'text-stone-400 hover:text-stone-700' }}">
                  {{ $photo->is_featured ? '★ Unggulan' : '☆ Jadikan Unggulan' }}
                </button>
              </form>
              <form method="POST" action="{{ route('admin.galleries.destroy', $photo) }}" onsubmit="return confirm('Hapus foto galeri ini?')">
                @csrf @method('DELETE')
                <button type="submit" class="text-red-600 hover:underline font-semibold">Hapus</button>
              </form>
            </div>
          </div>
        </div>
        @endforeach
      </div>
    </div>
  </div>

  <!-- TAB 4: TRANSAKSI TERBARU -->
  <div x-show="activeTab === 'transactions'" x-cloak class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200">
    <h2 class="font-serif text-xl font-bold text-forest-900 mb-4">Transaksi Terbaru</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="uppercase text-stone-400 border-b">
          <tr>
            <th class="py-2">Kode Booking</th>
            <th>Pengunjung</th>
            <th>Kapling</th>
            <th>Tanggal</th>
            <th>Tamu</th>
            <th>Total</th>
            <th>Status</th>
            <th>Check-In</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
          @foreach($reservations as $res)
          <tr>
            <td class="py-3 font-mono font-bold text-forest-900">{{ $res->booking_code }}</td>
            <td>{{ $res->user->name }}</td>
            <td class="font-medium">{{ $res->area_names }}</td>
            <td>{{ $res->check_in_date->format('d M') }} – {{ $res->check_out_date->format('d M Y') }}</td>
            <td>{{ $res->guest_count }} orang</td>
            <td class="font-semibold">Rp {{ number_format($res->total_price, 0, ',', '.') }}</td>
            <td>
              <span class="px-2 py-0.5 rounded-full font-bold uppercase
                                    {{ $res->payment_status === 'settled' ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900' }}">
                {{ $res->payment_status }}
              </span>
            </td>
            <td>{{ $res->ticket?->is_checked_in ? 'Sudah Check-In' : '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $reservations->links() }}</div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  function adminHub(config) {
    return {
      activeTab: config.defaultTab || 'plots',
      newPlot: {
        x: 100,
        y: 160,
        width: 110,
        height: 64
      },
      pickCoordinate(event) {
        const svg = event.currentTarget;
        const rect = svg.getBoundingClientRect();
        const scaleX = 1200 / rect.width;
        const scaleY = 800 / rect.height;
        const clickX = Math.round((event.clientX - rect.left) * scaleX - (this.newPlot.width / 2));
        const clickY = Math.round((event.clientY - rect.top) * scaleY - (this.newPlot.height / 2));

        this.newPlot.x = Math.max(0, Math.min(1200 - this.newPlot.width, clickX));
        this.newPlot.y = Math.max(0, Math.min(800 - this.newPlot.height, clickY));
      }
    };
  }
</script>
@endpush