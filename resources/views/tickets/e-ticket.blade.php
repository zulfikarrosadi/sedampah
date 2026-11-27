```html
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tiket Elektronik {{ $reservation->booking_code }} — Bukit Embun</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <style>
    @media print {
      .no-print {
        display: none !important;
      }

      body {
        background: #ffffff !important;
      }
    }
  </style>
</head>

<body class="bg-stone-100 text-stone-800 antialiased py-8 px-4">
  <div class="max-w-xl mx-auto">
    <div class="no-print flex justify-between items-center mb-4">
      <a href="{{ url()->previous() }}" class="text-sm font-medium text-emerald-900 hover:underline">&larr; Kembali</a>
      <button onclick="window.print()" class="px-4 py-2 rounded-xl bg-emerald-900 text-white text-sm font-semibold shadow hover:bg-emerald-800">
        Cetak / Simpan PDF
      </button>
    </div>

    <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-stone-200">
      <!-- Header -->
      <div class="bg-[#1B4332] text-white px-8 py-6 flex justify-between items-center">
        <div>
          <p class="text-xs uppercase tracking-widest text-amber-300 font-semibold">Tiket Masuk Resmi</p>
          <h1 class="text-2xl font-serif font-bold mt-1">Bukit Embun</h1>
          <p class="text-xs text-emerald-200">Kawasan Wisata Bumi Perkemahan</p>
        </div>
        <div class="text-right">
          <span class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase bg-emerald-800 text-emerald-100 border border-emerald-600">
            {{ $reservation->payment_status }}
          </span>
          <p class="font-mono text-sm font-bold mt-2 text-amber-200">{{ $reservation->booking_code }}</p>
        </div>
      </div>

      <!-- Detail Pengunjung dan Area Kemping -->
      <div class="p-8 space-y-6">
        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p class="text-xs uppercase text-stone-400 font-semibold">Pengunjung</p>
            <p class="font-bold text-stone-900 text-base">{{ $reservation->user->name }}</p>
            <p class="text-stone-500 text-xs">{{ $reservation->guest_count }} Orang</p>
          </div>
          <div>
            <p class="text-xs uppercase text-stone-400 font-semibold">
              Area Kemping{{ $reservation->areas->count() > 1 ? ' (' . $reservation->areas->count() . ' area)' : '' }}
            </p>
            @if($reservation->areas->isNotEmpty())
            @foreach($reservation->areas as $resArea)
            <p class="font-bold text-emerald-900 text-sm leading-snug">
              {{ $resArea->campingArea->name }}
              <span class="text-stone-400 font-normal text-xs">({{ $resArea->campingArea->layout_coordinates['zone'] ?? 'Zona Utama' }})</span>
            </p>
            @endforeach
            @else
            <p class="font-bold text-emerald-900 text-base">{{ $reservation->campingArea->name }}</p>
            <p class="text-stone-500 text-xs">{{ $reservation->campingArea->layout_coordinates['zone'] ?? 'Zona Utama' }}</p>
            @endif
          </div>
          <div>
            <p class="text-xs uppercase text-stone-400 font-semibold">Waktu Kedatangan</p>
            <p class="font-semibold text-stone-800">{{ $reservation->check_in_date->format('D, d M Y') }}</p>
            <p class="text-xs text-stone-400">Mulai pukul 14.00 WIB</p>
          </div>
          <div>
            <p class="text-xs uppercase text-stone-400 font-semibold">Waktu Kepulangan</p>
            <p class="font-semibold text-stone-800">{{ $reservation->check_out_date->format('D, d M Y') }}</p>
            <p class="text-xs text-stone-400">Maksimal pukul 12.00 WIB ({{ $reservation->duration_nights }} malam)</p>
          </div>
        </div>

        @if($reservation->items->isNotEmpty())
        <div class="border-t border-dashed border-stone-200 pt-4">
          <p class="text-xs uppercase text-stone-400 font-semibold mb-2">Perlengkapan yang Disewa</p>
          <ul class="space-y-1 text-sm">
            @foreach($reservation->items as $item)
            <li class="flex justify-between">
              <span>{{ $item->facility->name }} <span class="text-stone-400">x{{ $item->qty }}</span></span>
              <span class="font-medium">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
            </li>
            @endforeach
          </ul>
        </div>
        @endif

        <!-- Kotak Verifikasi Kode QR -->
        <div class="border-t border-dashed border-stone-300 pt-6 flex flex-col items-center text-center">
          <div id="ticket-qrcode" class="p-3 bg-white rounded-2xl border border-stone-200 shadow-inner"></div>
          <p class="text-xs text-stone-500 mt-3">Tunjukkan kode QR ini kepada petugas gerbang saat tiba di lokasi.</p>
          <p class="font-mono text-[10px] text-stone-400 mt-1 break-all">{{ $ticket->qr_code_hash }}</p>
        </div>
      </div>
    </div>
  </div>

  <script>
    new QRCode(document.getElementById("ticket-qrcode"), {
      text: @json($ticket->qr_code_hash),
      width: 180,
      height: 180,
      colorDark: "#1B4332",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H
    });
  </script>
</body>

</html>
```