@extends('layouts.nature')

@section('title', 'Select Camping Plot — Bukit Embun')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
  x-data="campsiteBooking({
         checkIn: @js($checkIn),
         checkOut: @js($checkOut),
         guestCount: {{ $guestCount }},
         plots: @js($availability['plots']),
         remainingQuota: {{ $availability['remaining_daily_quota'] }},
         facilities: @js($facilities),
         isAuthenticated: {{ auth()->check() ? 'true' : 'false' }},
         loginUrl: @js(route('login')),
         availabilityUrl: @js(route('api.availability')),
         checkoutUrl: @js(route('booking.checkout')),
         ticketsUrl: @js(route('visitor.tickets'))
     })">

  <!-- Date & Guest Filter Bar -->
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-stone-200/80 mb-8 flex flex-col lg:flex-row lg:items-end justify-between gap-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 flex-1">
      <div>
        <label class="block text-xs font-bold uppercase text-stone-500 mb-1">Check-In Date</label>
        <input type="date" x-model="checkIn" @change="refreshAvailability()" min="{{ now()->toDateString() }}"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
      </div>
      <div>
        <label class="block text-xs font-bold uppercase text-stone-500 mb-1">Check-Out Date</label>
        <input type="date" x-model="checkOut" @change="refreshAvailability()" :min="checkIn"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
      </div>
      <div>
        <label class="block text-xs font-bold uppercase text-stone-500 mb-1">Guest Count</label>
        <input type="number" x-model.number="guestCount" min="1" max="20"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
      </div>
    </div>

    <div class="flex items-center gap-3 text-xs">
      <span class="px-3 py-2 rounded-xl bg-emerald-50 text-forest-900 font-semibold border border-emerald-200">
        Daily Quota Left: <strong x-text="remainingQuota"></strong> plots
      </span>
      <span class="px-3 py-2 rounded-xl bg-sand-50 text-amber-900 font-semibold border border-amber-200">
        Stay: <strong x-text="nights"></strong> night(s)
      </span>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    <!-- Left 8 Cols: Interactive SVG Map + Gear Add-Ons -->
    <div class="lg:col-span-8 space-y-8">
      <!-- Cinema-Style Campsite Map -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200/80">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
          <div>
            <h2 class="font-serif text-2xl font-bold text-forest-900">Interactive Campsite Map</h2>
            <p class="text-xs text-stone-500">Click an available plot below to reserve your spot.</p>
          </div>
          <!-- Map Legend -->
          <div class="flex items-center gap-4 text-xs font-medium">
            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-[#2D6A4F] inline-block"></span> Available</span>
            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-[#DDA15E] ring-2 ring-amber-600 inline-block"></span> Selected</span>
            <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-stone-300 inline-block"></span> Booked / Unavailable</span>
          </div>
        </div>

        <!-- Scrollable Responsive SVG Canvas -->
        <div class="relative overflow-x-auto rounded-2xl border border-emerald-900/15 bg-[#eef5ef]">
          <svg viewBox="0 0 540 340" class="w-full min-w-[520px] h-auto select-none" role="img" aria-label="Bukit Embun Interactive Campsite Map">
            <defs>
              <pattern id="bookedHatch" width="8" height="8" patternTransform="rotate(45 0 0)" patternUnits="userSpaceOnUse">
                <line x1="0" y1="0" x2="0" y2="8" stroke="#94a3b8" stroke-width="3" />
              </pattern>
            </defs>

            <!-- Topographic Contours & Trails -->
            <path d="M 0,45 Q 260,15 540,55" fill="none" stroke="#b7e4c7" stroke-width="2" stroke-dasharray="6 4" />
            <path d="M 40,165 Q 250,145 500,175" fill="none" stroke="#d4a373" stroke-width="4" stroke-linecap="round" opacity="0.65" />
            <text x="330" y="70" font-size="8" fill="#7f5539" font-weight="bold" opacity="0.7">SUNRISE RIDGE</text>
            <text x="200" y="74" font-size="8" fill="#7f5539" font-weight="bold" opacity="0.7">MIST VALLEY</text>
            <text x="60" y="192" font-size="8" fill="#7f5539" font-weight="bold" opacity="0.7">PINE GROVE</text>
            <text x="370" y="197" font-size="8" fill="#7f5539" font-weight="bold" opacity="0.7">STARLIGHT PLATEAU</text>

            <!-- Environmental Landmarks -->
            <g transform="translate(25, 25)">
              <rect width="70" height="32" rx="8" fill="#1B4332" />
              <text x="35" y="14" text-anchor="middle" font-size="8" fill="#fefae0" font-weight="bold">GATE &amp; QR</text>
              <text x="35" y="25" text-anchor="middle" font-size="7" fill="#95d5b2">Check-In</text>
            </g>

            <g transform="translate(360, 22)">
              <rect width="150" height="30" rx="8" fill="#fefae0" stroke="#dda15e" />
              <text x="75" y="18" text-anchor="middle" font-size="9" fill="#9c5518" font-weight="bold">&#9728; EASTERN SUNRISE CLIFF</text>
            </g>

            <g transform="translate(30, 270)">
              <circle cx="20" cy="20" r="18" fill="#bc6c25" opacity="0.9" />
              <text x="20" y="23" text-anchor="middle" font-size="8" fill="#fff" font-weight="bold">FIRE PIT</text>
            </g>

            <g transform="translate(245, 282)">
              <rect width="76" height="28" rx="6" fill="#475569" />
              <text x="38" y="17" text-anchor="middle" font-size="8" fill="#f8fafc" font-weight="bold">WASHROOMS</text>
            </g>

            <!-- Dynamic Camping Plots -->
            @foreach($availability['plots'] as $plotItem)
            @php $coords = $plotItem['layout_coordinates']; @endphp
            <g transform="translate({{ $coords['x'] }}, {{ $coords['y'] }})"
              @click="selectPlotById({{ $plotItem['id'] }})"
              @keydown.enter.prevent="selectPlotById({{ $plotItem['id'] }})"
              :tabindex="isPlotAvailable({{ $plotItem['id'] }}) ? 0 : -1"
              role="button"
              :aria-label="`{{ $plotItem['name'] }} - ${isPlotAvailable({{ $plotItem['id'] }}) ? 'Available' : 'Unavailable'}`"
              data-area-id="{{ $plotItem['id'] }}"
              :data-status="getPlotStatus({{ $plotItem['id'] }})"
              :class="isPlotAvailable({{ $plotItem['id'] }}) ? 'cursor-pointer' : 'cursor-not-allowed'">

              <rect width="{{ $coords['width'] }}"
                height="{{ $coords['height'] }}"
                rx="10"
                :fill="selectedPlotId === {{ $plotItem['id'] }} ? '#DDA15E' : (isPlotAvailable({{ $plotItem['id'] }}) ? '#2D6A4F' : 'url(#bookedHatch)')"
                :stroke="selectedPlotId === {{ $plotItem['id'] }} ? '#9C5518' : (isPlotAvailable({{ $plotItem['id'] }}) ? '#1B4332' : '#94a3b8')"
                :stroke-width="selectedPlotId === {{ $plotItem['id'] }} ? 3 : 1.5" />

              <text x="{{ $coords['width'] / 2 }}"
                y="{{ ($coords['height'] / 2) - 3 }}"
                text-anchor="middle"
                font-size="9.5"
                font-weight="bold"
                :fill="selectedPlotId === {{ $plotItem['id'] }} ? '#1B4332' : (isPlotAvailable({{ $plotItem['id'] }}) ? '#ffffff' : '#475569')">
                {{ \Illuminate\Support\Str::afterLast($plotItem['name'], ' ') }}
              </text>

              <text x="{{ $coords['width'] / 2 }}"
                y="{{ ($coords['height'] / 2) + 10 }}"
                text-anchor="middle"
                font-size="8"
                :fill="selectedPlotId === {{ $plotItem['id'] }} ? '#1B4332' : (isPlotAvailable({{ $plotItem['id'] }}) ? '#d8f3dc' : '#64748b')"
                x-text="isPlotAvailable({{ $plotItem['id'] }}) ? '{{ $plotItem['capacity'] }}p' : 'FULL'">
              </text>
            </g>
            @endforeach
          </svg>
        </div>

        <!-- Accessible Synchronized Card Selector Fallback -->
        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
          <template x-for="plot in plots" :key="'card-' + plot.id">
            <button type="button"
              @click="selectPlot(plot)"
              :disabled="!plot.is_available"
              :class="{
                                    'ring-2 ring-amber-600 bg-sand-50 border-amber-400': selectedPlotId === plot.id,
                                    'bg-white hover:border-forest-800 border-stone-200': plot.is_available && selectedPlotId !== plot.id,
                                    'bg-stone-100 opacity-60 cursor-not-allowed border-stone-200': !plot.is_available
                                }"
              class="text-left p-3.5 rounded-xl border transition">
              <div class="flex justify-between items-start">
                <span class="font-bold text-sm text-forest-900" x-text="plot.name"></span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase"
                  :class="plot.is_available ? 'bg-emerald-100 text-forest-900' : 'bg-stone-200 text-stone-600'"
                  x-text="plot.is_available ? 'Available' : plot.status"></span>
              </div>
              <p class="text-xs text-stone-500 mt-1" x-text="`Max ${plot.capacity} guests • ${plot.layout_coordinates.zone || ''}`"></p>
              <p class="text-sm font-bold text-terracotta-600 mt-2" x-text="formatRupiah(plot.price_per_night) + ' / night'"></p>
            </button>
          </template>
        </div>
      </div>

      <!-- Equipment Add-On Selector -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200/80">
        <h3 class="font-serif text-xl font-bold text-forest-900">Camping Gear &amp; Equipment Add-Ons</h3>
        <p class="text-xs text-stone-500 mb-4">Optional rental gear delivered straight to your plot upon check-in.</p>

        <div class="divide-y divide-stone-100">
          <template x-for="item in facilities" :key="item.id">
            <div class="py-3.5 flex items-center justify-between gap-4">
              <div>
                <p class="font-semibold text-sm text-stone-900" x-text="item.name"></p>
                <p class="text-xs text-stone-500">
                  <span class="font-semibold text-forest-800" x-text="formatRupiah(item.rental_price)"></span> / <span x-text="item.unit"></span>
                  &bull; <span x-text="item.stock"></span> in stock
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" @click="decrementGear(item.id)"
                  class="w-8 h-8 rounded-lg border border-stone-300 font-bold text-stone-700 hover:bg-stone-100">-</button>
                <span class="w-8 text-center text-sm font-bold" x-text="cart[item.id] || 0"></span>
                <button type="button" @click="incrementGear(item)"
                  class="w-8 h-8 rounded-lg bg-forest-900 text-white font-bold hover:bg-forest-800">+</button>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Right 4 Cols: Sticky Reservation Summary & Checkout -->
    <div class="lg:col-span-4 sticky top-24">
      <div class="bg-white rounded-2xl p-6 shadow-lg border border-stone-200">
        <h3 class="font-serif text-xl font-bold text-forest-900 border-b border-stone-100 pb-3">Booking Summary</h3>

        <template x-if="!selectedPlot">
          <p class="text-sm text-stone-500 py-8 text-center">Select an available green plot on the campsite map to calculate your stay.</p>
        </template>

        <template x-if="selectedPlot">
          <div class="mt-4 space-y-4 text-sm">
            <div class="p-3.5 rounded-xl bg-sand-50 border border-amber-200">
              <p class="text-xs uppercase font-bold text-terracotta-600" x-text="selectedPlot.layout_coordinates.zone || 'Campsite Zone'"></p>
              <p class="font-serif font-bold text-lg text-forest-900" x-text="selectedPlot.name"></p>
              <p class="text-xs text-stone-600 mt-1" x-text="selectedPlot.description"></p>
            </div>

            <div class="space-y-2 border-b border-stone-100 pb-3">
              <div class="flex justify-between">
                <span class="text-stone-600" x-text="`${formatRupiah(selectedPlot.price_per_night)} × ${nights} night(s)`"></span>
                <span class="font-semibold" x-text="formatRupiah(plotSubtotal)"></span>
              </div>

              <template x-for="entry in cartEntries" :key="entry.id">
                <div class="flex justify-between text-xs text-stone-600">
                  <span x-text="`${entry.name} × ${entry.qty}`"></span>
                  <span class="font-medium" x-text="formatRupiah(entry.subtotal)"></span>
                </div>
              </template>
            </div>

            <div class="flex justify-between items-center text-base font-bold text-forest-900">
              <span>Total Payment</span>
              <span class="text-xl text-terracotta-600" x-text="formatRupiah(grandTotal)"></span>
            </div>

            <p x-show="errorMessage" x-text="errorMessage" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-xl"></p>

            <button type="button"
              @click="submitCheckout()"
              :disabled="submitting"
              class="w-full py-3.5 rounded-xl bg-terracotta-600 hover:bg-terracotta-700 disabled:opacity-50 text-white font-bold text-sm shadow-lg transition cursor-pointer">
              <span x-show="!submitting">Pay via Midtrans &rarr;</span>
              <span x-show="submitting">Preparing Snap Checkout...</span>
            </button>
          </div>
        </template>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="{{ $snapJsUrl }}" data-client-key="{{ $midtransClientKey }}"></script>
<script>
  function campsiteBooking(config) {
    return {
      checkIn: config.checkIn,
      checkOut: config.checkOut,
      guestCount: config.guestCount,
      plots: config.plots,
      remainingQuota: config.remainingQuota,
      facilities: config.facilities,
      selectedPlotId: null,
      cart: {},
      submitting: false,
      errorMessage: '',

      getPlot(id) {
        return this.plots.find(p => p.id === id) || null;
      },

      isPlotAvailable(id) {
        const plot = this.getPlot(id);
        return plot ? Boolean(plot.is_available) : false;
      },

      getPlotStatus(id) {
        const plot = this.getPlot(id);
        return plot ? plot.status : 'unavailable';
      },

      selectPlotById(id) {
        const plot = this.getPlot(id);
        if (plot) this.selectPlot(plot);
      },

      get nights() {
        const start = new Date(this.checkIn);
        const end = new Date(this.checkOut);
        const diff = Math.round((end - start) / (1000 * 60 * 60 * 24));
        return diff > 0 ? diff : 1;
      },

      get selectedPlot() {
        return this.plots.find(p => p.id === this.selectedPlotId) || null;
      },

      get plotSubtotal() {
        return this.selectedPlot ? this.selectedPlot.price_per_night * this.nights : 0;
      },

      get cartEntries() {
        return Object.entries(this.cart)
          .filter(([_, qty]) => qty > 0)
          .map(([id, qty]) => {
            const fac = this.facilities.find(f => f.id === Number(id));
            return {
              id: Number(id),
              name: fac.name,
              qty: qty,
              subtotal: fac.rental_price * qty
            };
          });
      },

      get grandTotal() {
        const gearTotal = this.cartEntries.reduce((sum, item) => sum + item.subtotal, 0);
        return this.plotSubtotal + gearTotal;
      },

      selectPlot(plot) {
        if (!plot.is_available) return;
        this.selectedPlotId = plot.id;
        this.errorMessage = '';
      },

      incrementGear(item) {
        const current = this.cart[item.id] || 0;
        if (current < item.stock) {
          this.cart[item.id] = current + 1;
        }
      },

      decrementGear(id) {
        if ((this.cart[id] || 0) > 0) {
          this.cart[id]--;
        }
      },

      formatRupiah(value) {
        return 'Rp ' + Number(value).toLocaleString('id-ID');
      },

      async refreshAvailability() {
        this.errorMessage = '';
        const url = `${config.availabilityUrl}?check_in_date=${this.checkIn}&check_out_date=${this.checkOut}`;
        const res = await fetch(url, {
          headers: {
            'Accept': 'application/json'
          }
        });
        const json = await res.json();
        if (json.success) {
          this.plots = json.data.plots;
          this.remainingQuota = json.data.remaining_daily_quota;
          if (this.selectedPlot && !this.selectedPlot.is_available) {
            this.selectedPlotId = null;
          }
        } else {
          this.errorMessage = json.message;
        }
      },

      async submitCheckout() {
        if (!config.isAuthenticated) {
          window.location.href = config.loginUrl;
          return;
        }
        if (!this.selectedPlotId) return;

        this.submitting = true;
        this.errorMessage = '';

        try {
          const response = await fetch(config.checkoutUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
              camping_area_id: this.selectedPlotId,
              check_in_date: this.checkIn,
              check_out_date: this.checkOut,
              guest_count: this.guestCount,
              items: this.cartEntries.map(e => ({
                facility_id: e.id,
                qty: e.qty
              }))
            })
          });

          const result = await response.json();
          if (!result.success) {
            this.errorMessage = result.message;
            this.submitting = false;
            return;
          }

          window.snap.pay(result.data.snap_token, {
            onSuccess: () => {
              window.location.href = config.ticketsUrl;
            },
            onPending: () => {
              window.location.href = config.ticketsUrl;
            },
            onError: () => {
              this.errorMessage = 'Payment failed or was interrupted.';
              this.submitting = false;
            },
            onClose: () => {
              window.location.href = config.ticketsUrl;
            }
          });
        } catch (e) {
          this.errorMessage = 'Network error while initiating checkout.';
          this.submitting = false;
        }
      }
    };
  }
</script>
@endpush