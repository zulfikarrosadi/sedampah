@extends('layouts.nature')

@section('title', 'Selesaikan Pembayaran ' . $reservation->booking_code . ' — Bukit Embun')

@section('content')
<div class="max-w-2xl mx-auto px-4 sm:px-6 py-12"
  x-data="retryPayment({
         snapToken: @js($snapToken),
         expiresAtIso: @js($expiresAtIso),
         retryUrl: @js(route('visitor.reservations.retry-token', $reservation)),
         ticketsUrl: @js(route('visitor.tickets'))
     })">

  <div class="mb-6 flex items-center justify-between">
    <a href="{{ route('visitor.tickets') }}" class="text-xs font-semibold text-forest-900 hover:underline">
      &larr; Kembali ke Reservasi Saya
    </a>
    <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-amber-100 text-amber-900 border border-amber-300">
      Menunggu Pembayaran
    </span>
  </div>

  <div class="bg-white rounded-3xl shadow-xl border border-stone-200 overflow-hidden">
    <!-- Banner Header -->
    <div class="bg-forest-900 text-white px-7 py-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <p class="text-xs uppercase tracking-widest text-amber-300 font-semibold">Reservasi Tertunda</p>
        <h1 class="font-serif text-2xl font-bold mt-0.5">{{ $reservation->booking_code }}</h1>
      </div>
      <div class="sm:text-right">
        <p class="text-[11px] uppercase text-emerald-200">Batas Waktu Tahan</p>
        <p class="font-mono text-xl font-bold text-golden-400" x-text="countdownText">--:--</p>
      </div>
    </div>

    <!-- Isi Ringkasan -->
    <div class="p-7 space-y-6 text-sm">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-sand-50 border border-amber-200/80">
        <div>
          <p class="text-xs uppercase font-bold text-stone-400">Kavling yang Dipilih</p>
          <p class="font-serif font-bold text-base text-forest-900 mt-0.5">{{ $reservation->area_names }}</p>
          <p class="text-xs text-stone-500 mt-0.5">{{ $reservation->guest_count }} Tamu</p>
        </div>
        <div>
          <p class="text-xs uppercase font-bold text-stone-400">Jadwal Menginap</p>
          <p class="font-semibold text-stone-800 mt-0.5">
            {{ $reservation->check_in_date->format('d M Y') }} &rarr; {{ $reservation->check_out_date->format('d M Y') }}
          </p>
          <p class="text-xs text-stone-500 mt-0.5">{{ $reservation->duration_nights }} Malam</p>
        </div>
      </div>

      <!-- Rincian Biaya -->
      <div class="space-y-2.5 border-b border-stone-100 pb-4">
        <p class="text-xs uppercase font-bold text-stone-400">Rincian Biaya</p>

        @if($reservation->areas->isNotEmpty())
        @foreach($reservation->areas as $resArea)
        <div class="flex justify-between text-stone-700">
          <span>{{ $resArea->campingArea->name }} ({{ $reservation->duration_nights }} malam)</span>
          <span class="font-semibold">Rp {{ number_format($resArea->subtotal, 0, ',', '.') }}</span>
        </div>
        @endforeach
        @else
        <div class="flex justify-between text-stone-700">
          <span>{{ $reservation->campingArea->name }} ({{ $reservation->duration_nights }} malam)</span>
          <span class="font-semibold">Rp {{ number_format($reservation->area_price_snapshot * $reservation->duration_nights, 0, ',', '.') }}</span>
        </div>
        @endif

        @foreach($reservation->items as $item)
        <div class="flex justify-between text-stone-600 text-xs">
          <span>{{ $item->facility->name }} &times; {{ $item->qty }}</span>
          <span class="font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
        </div>
        @endforeach
      </div>

      <div class="flex justify-between items-center text-base font-bold text-forest-900">
        <span>Total yang Harus Dibayar</span>
        <span class="text-2xl text-terracotta-600">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</span>
      </div>

      <p x-show="errorMessage" x-text="errorMessage" x-cloak
        class="text-xs text-red-700 bg-red-50 border border-red-200 p-3 rounded-xl"></p>

      <!-- Aksi Utama & Batal -->
      <div class="flex flex-col sm:flex-row gap-3 pt-2">
        <button type="button"
          @click="openMidtrans()"
          :disabled="expired || loading"
          class="flex-1 py-3.5 rounded-xl bg-terracotta-600 hover:bg-terracotta-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm shadow-lg transition cursor-pointer">
          <span x-show="!loading && !expired">Selesaikan Pembayaran via Midtrans &rarr;</span>
          <span x-show="loading">Membuka Jendela Pembayaran...</span>
          <span x-show="expired">Reservasi Kedaluwarsa</span>
        </button>

        <form method="POST" action="{{ route('visitor.reservations.cancel', $reservation) }}"
          onsubmit="return confirm('Batalkan reservasi ini dan lepaskan kavling yang dipesan?')">
          @csrf
          <button type="submit"
            class="w-full sm:w-auto px-5 py-3.5 rounded-xl border border-stone-300 text-stone-600 hover:bg-stone-100 text-xs font-semibold transition cursor-pointer">
            Batalkan Pemesanan
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ $snapJsUrl }}" data-client-key="{{ $midtransClientKey }}"></script>
<script>
  function retryPayment(config) {
    return {
      snapToken: config.snapToken,
      expiresAt: new Date(config.expiresAtIso).getTime(),
      countdownText: '--:--',
      expired: false,
      loading: false,
      errorMessage: '',

      init() {
        this.updateCountdown();
        setInterval(() => this.updateCountdown(), 1000);
      },

      updateCountdown() {
        const diff = this.expiresAt - Date.now();
        if (diff <= 0) {
          this.expired = true;
          this.countdownText = '00:00';
          return;
        }
        const mins = Math.floor(diff / 60000);
        const secs = Math.floor((diff % 60000) / 1000);
        this.countdownText = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
      },

      async openMidtrans() {
        if (this.expired || this.loading) return;
        this.loading = true;
        this.errorMessage = '';

        try {
          // Verifikasi bahwa token masih baru di backend sebelum membuka Snap
          const res = await fetch(config.retryUrl, {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
          });
          const json = await res.json();

          if (!json.success) {
            this.errorMessage = json.message;
            this.loading = false;
            return;
          }

          this.snapToken = json.data.snap_token;

          window.snap.pay(this.snapToken, {
            onSuccess: () => {
              window.location.href = config.ticketsUrl;
            },
            onPending: () => {
              this.loading = false;
            },
            onError: () => {
              this.errorMessage = 'Pembayaran gagal. Anda dapat mengklik Selesaikan Pembayaran lagi untuk mencoba ulang.';
              this.loading = false;
            },
            onClose: () => {
              this.loading = false;
            }
          });
        } catch (e) {
          this.errorMessage = 'Terjadi kesalahan jaringan saat menghubungi server pembayaran.';
          this.loading = false;
        }
      }
    };
  }
</script>
@endpush