@extends('layouts.nature')

@section('title', 'Reservasi & E-Tiket Saya — Bukit Embun')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <div class="flex justify-between items-center mb-8">
    <div>
      <h1 class="font-serif text-3xl font-bold text-forest-900">Reservasi &amp; Tiket Masuk Saya</h1>
      <p class="text-sm text-stone-500 mt-1">Lihat riwayat pemesanan Anda, lanjutkan pembayaran yang tertunda, dan unduh tiket masuk QR.</p>
    </div>
    <a href="{{ route('booking.index') }}" class="px-4 py-2.5 rounded-xl bg-terracotta-600 text-white text-sm font-semibold shadow hover:bg-terracotta-700">
      + Pemesanan Baru
    </a>
  </div>

  @if(session('status'))
  <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-medium">
    {{ session('status') }}
  </div>
  @endif
  @if(session('error'))
  <div class="mb-6 p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-sm font-medium">
    {{ session('error') }}
  </div>
  @endif

  @php $expiryMinutes = (int) config('camping.pending_expiry_minutes', 60); @endphp

  <div class="space-y-4">
    @forelse($reservations as $reservation)
    @php
    $isPending = $reservation->payment_status === 'pending';
    $expiresAt = $reservation->created_at->copy()->addMinutes($expiryMinutes);
    $isHoldExpired = $isPending && $expiresAt->isPast();
    @endphp

    <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex flex-wrap items-center gap-2.5">
          <span class="font-mono text-sm font-bold text-forest-900">{{ $reservation->booking_code }}</span>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase
                            {{ $reservation->payment_status === 'settled' ? 'bg-emerald-100 text-emerald-900' : ($isPending && !$isHoldExpired ? 'bg-amber-100 text-amber-900' : 'bg-stone-200 text-stone-600') }}">
            {{ $isHoldExpired ? 'kedaluwarsa' : $reservation->payment_status }}
          </span>
          @if($reservation->areas->count() > 1)
          <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
            {{ $reservation->areas->count() }} Kavling
          </span>
          @endif
        </div>
        <h3 class="font-serif text-lg font-bold text-stone-900 mt-1">{{ $reservation->area_names }}</h3>
        <p class="text-xs text-stone-500">
          {{ $reservation->check_in_date->format('d M Y') }} &rarr; {{ $reservation->check_out_date->format('d M Y') }}
          ({{ $reservation->duration_nights }} malam) &bull; {{ $reservation->guest_count }} tamu
        </p>
        @if($isPending && !$isHoldExpired)
        <p class="text-[11px] text-amber-700 font-medium mt-1">
          Selesaikan pembayaran sebelum pukul {{ $expiresAt->format('H:i') }} WIB untuk mempertahankan pemesanan kavling Anda.
        </p>
        @endif
      </div>

      <div class="flex flex-wrap items-center gap-3">
        <div class="text-right mr-1">
          <p class="text-xs text-stone-400">Total</p>
          <p class="font-bold text-base text-forest-900">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</p>
        </div>

        @if($reservation->ticket)
        <a href="{{ route('tickets.show', $reservation->ticket) }}"
          class="px-4 py-2.5 rounded-xl bg-forest-900 text-white text-xs font-semibold hover:bg-forest-800 shadow">
          Lihat E-Tiket QR
        </a>
        @elseif($isPending && !$isHoldExpired)
        <a href="{{ route('visitor.reservations.pay', $reservation) }}"
          class="px-4 py-2.5 rounded-xl bg-terracotta-600 text-white text-xs font-semibold hover:bg-terracotta-700 shadow">
          Bayar Sekarang &rarr;
        </a>
        <form method="POST" action="{{ route('visitor.reservations.cancel', $reservation) }}"
          onsubmit="return confirm('Batalkan reservasi ini?')">
          @csrf
          <button type="submit" class="px-3 py-2.5 rounded-xl border border-stone-300 text-stone-600 hover:bg-stone-100 text-xs font-semibold">
            Batalkan
          </button>
        </form>
        @endif
      </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl p-12 text-center border border-stone-200">
      <p class="text-stone-500 text-sm">Anda belum memiliki reservasi area perkemahan.</p>
      <a href="{{ route('booking.index') }}" class="inline-block mt-4 px-5 py-2.5 rounded-xl bg-forest-900 text-white text-sm font-semibold">
        Jelajahi Peta Perkemahan
      </a>
    </div>
    @endforelse
  </div>

  <div class="mt-6">{{ $reservations->links() }}</div>
</div>
@endsection