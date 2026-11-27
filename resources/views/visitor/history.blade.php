@extends('layouts.app')

@section('title', 'Riwayat Transaksi & E-Tiket - Bukit Embun')

@push('head')
<script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}"></script>
@endpush

@section('content')
<div class="space-y-6">
  <div class="flex justify-between items-center">
    <div>
      <h1 class="text-xl font-bold">Riwayat Transaksi & E-Tiket</h1>
      <p class="text-sm text-slate-500">Daftar reservasi bumi perkemahan yang pernah Anda lakukan.</p>
    </div>
    <a href="{{ route('home') }}" class="bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold px-4 py-2 rounded-lg">
      + Buat Reservasi Baru
    </a>
  </div>

  @if($reservations->isEmpty())
  <div class="bg-white p-8 rounded-xl border text-center text-slate-500">
    <p class="mb-3">Anda belum memiliki riwayat reservasi.</p>
    <a href="{{ route('home') }}" class="text-emerald-700 font-semibold hover:underline">Mulai pesan area kemping sekarang &rarr;</a>
  </div>
  @else
  <div class="space-y-4">
    @foreach($reservations as $res)
    <div class="bg-white p-5 rounded-xl shadow-sm border flex flex-col md:flex-row justify-between gap-4">
      <div class="space-y-1.5">
        <div class="flex items-center gap-2">
          <span class="font-mono font-bold text-sm text-slate-800">{{ $res->booking_code }}</span>
          @if($res->payment_status === 'settled')
          <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800">LUNAS</span>
          @elseif($res->payment_status === 'pending')
          <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">MENUNGGU PEMBAYARAN</span>
          @else
          <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">{{ strtoupper($res->payment_status) }}</span>
          @endif
        </div>

        <h2 class="font-bold text-base text-emerald-900">{{ $res->campingArea->name }}</h2>
        <p class="text-xs text-slate-600">
          Jadwal: <strong>{{ $res->check_in_date->format('d M Y') }}</strong> sampai <strong>{{ $res->check_out_date->format('d M Y') }}</strong> ({{ $res->duration_nights }} malam, {{ $res->guest_count }} orang)
        </p>

        @if($res->items->isNotEmpty())
        <p class="text-xs text-slate-500">
          Perlengkapan Tambahan: {{ $res->items->map(fn($i) => "{$i->facility->name} ({$i->qty} {$i->facility->unit})")->join(', ') }}
        </p>
        @endif
      </div>

      <div class="flex flex-col md:items-end justify-between border-t md:border-t-0 pt-3 md:pt-0">
        <div class="md:text-right">
          <span class="text-xs text-slate-500">Total Pembayaran</span>
          <p class="font-bold text-lg text-slate-800">Rp {{ number_format($res->total_price, 0, ',', '.') }}</p>
        </div>

        <div class="mt-3 flex gap-2">
          @if($res->payment_status === 'settled' && $res->ticket)
          <a href="{{ route('visitor.ticket', $res) }}" class="bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold px-4 py-2 rounded-lg">
            Lihat E-Tiket (Kode QR)
          </a>
          @elseif($res->payment_status === 'pending')
          <button onclick="resumePayment({{ $res->id }})" class="bg-amber-500 hover:bg-amber-600 text-slate-900 text-xs font-semibold px-4 py-2 rounded-lg">
            Selesaikan Pembayaran
          </button>
          @endif
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @endif
</div>
@endsection

@push('scripts')
<script>
  const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

  async function resumePayment(reservationId) {
    const res = await fetch(`/reservations/${reservationId}/pay`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json'
      }
    });
    const json = await res.json();
    if (!json.success) {
      alert(json.message);
      return;
    }

    window.snap.pay(json.data.snap_token, {
      onSuccess: () => window.location.reload(),
      onPending: () => window.location.reload(),
      onError: () => alert('Pembayaran gagal. Silakan coba kembali.')
    });
  }
</script>
@endpush