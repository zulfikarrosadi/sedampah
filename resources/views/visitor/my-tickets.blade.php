@extends('layouts.nature')

@section('title', 'Reservasi & E-Tiket Saya — Bukit Embun')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <div class="flex justify-between items-center mb-8">
    <div>
      <h1 class="font-serif text-3xl font-bold text-forest-900">Reservasi &amp; Tiket Masuk Saya</h1>
      <p class="text-sm text-stone-500 mt-1">Lihat riwayat pemesanan Anda dan unduh tiket masuk QR yang dapat dipindai.</p>
    </div>
    <a href="{{ route('booking.index') }}" class="px-4 py-2.5 rounded-xl bg-terracotta-600 text-white text-sm font-semibold shadow hover:bg-terracotta-700">
      + Pemesanan Baru
    </a>
  </div>

  <div class="space-y-4">
    @forelse($reservations as $reservation)
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <div class="flex items-center gap-2.5">
          <span class="font-mono text-sm font-bold text-forest-900">{{ $reservation->booking_code }}</span>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase
                            {{ $reservation->payment_status === 'settled' ? 'bg-emerald-100 text-emerald-900' : ($reservation->payment_status === 'pending' ? 'bg-amber-100 text-amber-900' : 'bg-stone-200 text-stone-600') }}">
            {{ $reservation->payment_status }}
          </span>
          @if($reservation->areas->count() > 1)
          <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
            {{ $reservation->areas->count() }} Kapling
          </span>
          @endif
        </div>
        <h3 class="font-serif text-lg font-bold text-stone-900 mt-1">{{ $reservation->area_names }}</h3>
        <p class="text-xs text-stone-500">
          {{ $reservation->check_in_date->format('d M Y') }} &rarr; {{ $reservation->check_out_date->format('d M Y') }}
          ({{ $reservation->duration_nights }} malam) &bull; {{ $reservation->guest_count }} tamu
        </p>
      </div>

      <div class="flex items-center gap-4">
        <div class="text-right">
          <p class="text-xs text-stone-400">Total</p>
          <p class="font-bold text-base text-forest-900">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</p>
        </div>

        @if($reservation->ticket)
        <a href="{{ route('tickets.show', $reservation->ticket) }}"
          class="px-4 py-2.5 rounded-xl bg-forest-900 text-white text-xs font-semibold hover:bg-forest-800 shadow">
          Lihat E-Tiket QR
        </a>
        @endif
      </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl p-12 text-center border border-stone-200">
      <p class="text-stone-500 text-sm">Anda belum memiliki reservasi kapling kemah.</p>
      <a href="{{ route('booking.index') }}" class="inline-block mt-4 px-5 py-2.5 rounded-xl bg-forest-900 text-white text-sm font-semibold">
        Jelajahi Peta Perkemahan
      </a>
    </div>
    @endforelse
  </div>

  <div class="mt-6">{{ $reservations->links() }}</div>
</div>
@endsection