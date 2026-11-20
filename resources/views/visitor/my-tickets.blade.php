@extends('layouts.nature')

@section('title', 'My Reservations & E-Tickets — Bukit Embun')

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
  <div class="flex justify-between items-center mb-8">
    <div>
      <h1 class="font-serif text-3xl font-bold text-forest-900">My Reservations &amp; Passes</h1>
      <p class="text-sm text-stone-500 mt-1">View your booking history and download scannable QR entry passes.</p>
    </div>
    <a href="{{ route('booking.index') }}" class="px-4 py-2.5 rounded-xl bg-terracotta-600 text-white text-sm font-semibold shadow hover:bg-terracotta-700">
      + New Booking
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
        </div>
        <h3 class="font-serif text-lg font-bold text-stone-900 mt-1">{{ $reservation->campingArea->name }}</h3>
        <p class="text-xs text-stone-500">
          {{ $reservation->check_in_date->format('d M Y') }} &rarr; {{ $reservation->check_out_date->format('d M Y') }}
          ({{ $reservation->duration_nights }} night) &bull; {{ $reservation->guest_count }} guest(s)
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
          View QR E-Ticket
        </a>
        @endif
      </div>
    </div>
    @empty
    <div class="bg-white rounded-2xl p-12 text-center border border-stone-200">
      <p class="text-stone-500 text-sm">You have no campsite reservations yet.</p>
      <a href="{{ route('booking.index') }}" class="inline-block mt-4 px-5 py-2.5 rounded-xl bg-forest-900 text-white text-sm font-semibold">
        Explore Campsite Map
      </a>
    </div>
    @endforelse
  </div>

  <div class="mt-6">{{ $reservations->links() }}</div>
</div>
@endsection