@extends('layouts.nature')

@section('title', 'Gate QR Scanner — Petugas Portal')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 py-10" x-data="gateScanner(@js(route('api.tickets.verify')))">
  <div class="mb-6">
    <span class="text-xs font-bold uppercase tracking-wider text-forest-800">Gate Operations</span>
    <h1 class="font-serif text-3xl font-bold text-forest-900">Visitor E-Ticket Verification</h1>
    <p class="text-sm text-stone-500">Scan the visitor's QR code via camera or paste the 64-character ticket token manually.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Camera + Manual Token Box -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200 space-y-4">
      <div class="flex items-center justify-between">
        <span class="text-xs font-bold uppercase text-stone-500">Camera Scanner</span>
        <div class="flex gap-2">
          <button type="button"
            x-show="!cameraActive"
            @click="startCamera()"
            class="px-3.5 py-1.5 rounded-xl bg-terracotta-600 text-white text-xs font-semibold hover:bg-terracotta-700">
            Start Camera
          </button>
          <button type="button"
            x-show="cameraActive"
            x-cloak
            @click="stopCamera()"
            class="px-3.5 py-1.5 rounded-xl bg-stone-700 text-white text-xs font-semibold hover:bg-stone-800">
            Stop Camera
          </button>
        </div>
      </div>

      <div id="qr-reader" class="rounded-xl overflow-hidden border border-stone-200 bg-stone-900 min-h-[260px] flex items-center justify-center text-xs text-stone-400">
        <span x-show="!cameraActive">Click "Start Camera" to allow webcam access</span>
      </div>

      <p x-show="cameraError" x-text="cameraError" x-cloak
        class="text-xs text-red-700 bg-red-50 border border-red-200 p-3 rounded-xl"></p>

      <form @submit.prevent="verifyToken(manualHash)" class="pt-2">
        <label class="block text-xs font-bold uppercase text-stone-500 mb-1">Manual QR Hash Fallback</label>
        <div class="flex gap-2">
          <input type="text" x-model="manualHash" placeholder="Paste 64-char SHA-256 hash..."
            class="flex-1 rounded-xl border-stone-300 text-xs font-mono">
          <button type="submit" class="px-4 py-2 rounded-xl bg-forest-900 text-white text-xs font-semibold hover:bg-forest-800">
            Verify
          </button>
        </div>
      </form>
    </div>

    <!-- Recent Gate Check-Ins -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200">
      <h2 class="font-serif text-lg font-bold text-forest-900 mb-4">Recent Gate Check-Ins</h2>
      <div class="space-y-3 text-sm">
        @forelse($recentCheckIns as $ticket)
        <div class="p-3 rounded-xl bg-stone-50 border border-stone-200/70 flex justify-between items-center">
          <div>
            <p class="font-bold text-stone-900">{{ $ticket->reservation->user->name }}</p>
            <p class="text-xs text-stone-500">{{ $ticket->reservation->campingArea->name }} &bull; {{ $ticket->reservation->booking_code }}</p>
          </div>
          <span class="text-xs font-semibold text-emerald-800">{{ $ticket->checked_in_at?->format('H:i') }}</span>
        </div>
        @empty
        <p class="text-xs text-stone-400">No check-ins recorded yet today.</p>
        @endforelse
      </div>
    </div>
  </div>

  <!-- Verification Result Modal -->
  <div x-show="modal" x-cloak class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border-t-8"
      :class="modal?.success ? 'border-emerald-600' : 'border-red-600'">
      <div class="flex items-center justify-between">
        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase"
          :class="modal?.success ? 'bg-emerald-100 text-emerald-900' : 'bg-red-100 text-red-900'"
          x-text="modal?.success ? 'ACCESS GRANTED' : 'VERIFICATION FAILED'"></span>
      </div>

      <p class="mt-4 font-serif text-xl font-bold text-stone-900" x-text="modal?.message"></p>

      <template x-if="modal?.success && modal?.data">
        <div class="mt-4 p-4 rounded-2xl bg-sand-50 border border-amber-200 space-y-2 text-sm">
          <p><span class="text-stone-500">Visitor:</span> <strong x-text="modal.data.visitor_name"></strong> (<span x-text="modal.data.guest_count"></span> pax)</p>
          <p><span class="text-stone-500">Plot:</span> <strong class="text-forest-900" x-text="modal.data.camping_area"></strong> (<span x-text="modal.data.zone"></span>)</p>
          <p><span class="text-stone-500">Dates:</span> <span x-text="`${modal.data.check_in_date} – ${modal.data.check_out_date}`"></span></p>
          <template x-if="modal.data.items && modal.data.items.length">
            <div class="pt-2 border-t border-amber-200/70">
              <p class="text-xs font-bold uppercase text-stone-500">Rented Equipment to Issue:</p>
              <ul class="mt-1 space-y-1 text-xs">
                <template x-for="item in modal.data.items" :key="item.name">
                  <li>&bull; <strong x-text="item.name"></strong> &times; <span x-text="item.qty"></span> <span x-text="item.unit"></span></li>
                </template>
              </ul>
            </div>
          </template>
        </div>
      </template>

      <button @click="closeModal()"
        class="mt-6 w-full py-3 rounded-xl bg-forest-900 text-white font-semibold text-sm hover:bg-forest-800">
        Scan Next Ticket
      </button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
  function gateScanner(verifyUrl) {
    return {
      manualHash: '',
      modal: null,
      scanningLocked: false,
      cameraActive: false,
      cameraError: '',
      html5QrCode: null,

      async startCamera() {
        this.cameraError = '';

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          this.cameraError = 'Camera API unavailable. Ensure you are on http://localhost:8000 or HTTPS.';
          return;
        }

        if (typeof Html5Qrcode === 'undefined') {
          this.cameraError = 'QR scanner library failed to load. Check your internet connection.';
          return;
        }

        try {
          // Explicitly trigger the browser camera permission prompt first
          const stream = await navigator.mediaDevices.getUserMedia({
            video: true
          });
          stream.getTracks().forEach(track => track.stop());

          this.html5QrCode = new Html5Qrcode('qr-reader');
          await this.html5QrCode.start({
              facingMode: 'environment'
            }, {
              fps: 10,
              qrbox: {
                width: 220,
                height: 220
              }
            },
            (decodedText) => {
              if (!this.scanningLocked) {
                this.verifyToken(decodedText.trim());
              }
            },
            () => {} // ignore per-frame scan misses
          );
          this.cameraActive = true;
        } catch (err) {
          this.cameraActive = false;
          this.cameraError = `Camera access denied or unavailable (${err.name || err}). In Brave/macOS, click the camera icon in the address bar or check System Settings → Privacy & Security → Camera.`;
        }
      },

      async stopCamera() {
        if (this.html5QrCode && this.cameraActive) {
          await this.html5QrCode.stop();
          await this.html5QrCode.clear();
        }
        this.cameraActive = false;
      },

      async verifyToken(hash) {
        if (!hash || this.scanningLocked) return;
        this.scanningLocked = true;

        try {
          const res = await fetch(verifyUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
              qr_code_hash: hash
            })
          });
          this.modal = await res.json();
        } catch (e) {
          this.modal = {
            success: false,
            message: 'Network error communicating with verification server.',
            data: null
          };
        }
      },

      closeModal() {
        this.modal = null;
        this.manualHash = '';
        this.scanningLocked = false;
      }
    };
  }
</script>
@endpush