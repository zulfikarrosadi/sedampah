@extends('layouts.nature')

@section('title', 'Select Camping Plots — Bukit Embun')

@section('content')
<div class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 py-8"
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
  <div class="bg-white rounded-2xl p-5 shadow-sm border border-stone-200/80 mb-6 flex flex-col lg:flex-row lg:items-end justify-between gap-4">
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
        <label class="block text-xs font-bold uppercase text-stone-500 mb-1">Total Group Guests</label>
        <input type="number" x-model.number="guestCount" min="1" max="100"
          class="w-full rounded-xl border-stone-300 text-sm focus:ring-forest-800 focus:border-forest-800">
      </div>
    </div>

    <div class="flex flex-wrap items-center gap-2.5 text-xs">
      <span class="px-3 py-2 rounded-xl bg-emerald-50 text-forest-900 font-semibold border border-emerald-200">
        Daily Quota Left: <strong x-text="remainingQuota"></strong> bookings
      </span>
      <span class="px-3 py-2 rounded-xl bg-sand-50 text-amber-900 font-semibold border border-amber-200">
        Stay: <strong x-text="nights"></strong> night(s)
      </span>
    </div>
  </div>

  <!-- Full-Width Interactive Campsite Map -->
  <div class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-stone-200/80 mb-8">
    <!-- Header + Zoom Controls -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
      <div>
        <h2 class="font-serif text-2xl font-bold text-forest-900">Bukit Embun Campsite Map</h2>
        <p class="text-xs text-stone-500">Click one or multiple green plots to accommodate your group size.</p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Zoom Controls -->
        <div class="inline-flex rounded-xl border border-stone-200 bg-stone-50 p-0.5 text-xs font-semibold">
          <button type="button" @click="zoom = 1" :class="zoom === 1 ? 'bg-forest-900 text-white' : 'text-stone-600'" class="px-2.5 py-1 rounded-lg transition">100%</button>
          <button type="button" @click="zoom = 1.25" :class="zoom === 1.25 ? 'bg-forest-900 text-white' : 'text-stone-600'" class="px-2.5 py-1 rounded-lg transition">125%</button>
          <button type="button" @click="zoom = 1.5" :class="zoom === 1.5 ? 'bg-forest-900 text-white' : 'text-stone-600'" class="px-2.5 py-1 rounded-lg transition">150%</button>
        </div>
      </div>
    </div>

    <!-- Zone Filter Pills & Legend -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-stone-100 mb-4">
      <div class="flex flex-wrap gap-1.5 text-xs font-semibold">
        <template x-for="zone in zones" :key="zone">
          <button type="button"
            @click="activeZone = zone"
            :class="activeZone === zone ? 'bg-forest-900 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'"
            class="px-3 py-1.5 rounded-xl transition cursor-pointer"
            x-text="zone"></button>
        </template>
      </div>

      <div class="flex items-center gap-4 text-xs font-medium">
        <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-[#2D6A4F] inline-block"></span> Available</span>
        <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-[#DDA15E] ring-2 ring-amber-700 inline-block"></span> Selected</span>
        <span class="flex items-center gap-1.5"><span class="w-3.5 h-3.5 rounded bg-stone-300 inline-block"></span> Booked / Maint.</span>
      </div>
    </div>

    <!-- Hover Tooltip Banner -->
    <div class="min-h-[36px] mb-3 px-3.5 py-2 rounded-xl bg-stone-50 border border-stone-200/70 flex items-center justify-between text-xs">
      <template x-if="hoveredPlot">
        <div class="flex flex-wrap items-center gap-3">
          <span class="font-bold text-forest-900" x-text="hoveredPlot.name"></span>
          <span class="text-stone-500" x-text="hoveredPlot.layout_coordinates.zone"></span>
          <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 font-semibold" x-text="`Max ${hoveredPlot.capacity} guests`"></span>
          <span class="font-bold text-terracotta-600" x-text="formatRupiah(hoveredPlot.price_per_night) + ' / night'"></span>
        </div>
      </template>
      <template x-if="!hoveredPlot">
        <span class="text-stone-400">Hover or tap any plot on the map to inspect elevation, capacity, and nightly rate.</span>
      </template>
    </div>

    <!-- Full-Width Responsive / Zoomable 1200x800 SVG Canvas -->
    <div class="relative overflow-auto max-h-[760px] rounded-2xl border border-emerald-900/20 bg-[#eaf4ec]">
      <div :style="zoom === 1 ? 'width: 100%;' : `width: ${zoom * 100}%;`" class="transition-all duration-200">
        <svg viewBox="0 0 1200 800" class="w-full h-auto block select-none" role="img" aria-label="Bukit Embun 1200x800 Interactive Campsite Map">
          <defs>
            <pattern id="bookedHatch" width="10" height="10" patternTransform="rotate(45 0 0)" patternUnits="userSpaceOnUse">
              <line x1="0" y1="0" x2="0" y2="10" stroke="#94a3b8" stroke-width="3.5" />
            </pattern>
          </defs>

          <!-- Topographic Contour Lines -->
          <path d="M 0,80 Q 450,25 1200,75" fill="none" stroke="#b7e4c7" stroke-width="2.5" stroke-dasharray="8 6" />
          <path d="M 0,275 Q 600,245 1200,285" fill="none" stroke="#b7e4c7" stroke-width="2.5" stroke-dasharray="8 6" />
          <path d="M 0,520 Q 580,490 1200,530" fill="none" stroke="#b7e4c7" stroke-width="2.5" stroke-dasharray="8 6" />

          <!-- Zone Region Backdrops -->
          <rect x="450" y="65" width="710" height="205" rx="20" fill="#d8f3dc" opacity="0.45" />
          <text x="470" y="85" font-size="11" font-weight="bold" fill="#1b4332" letter-spacing="1.5">ZONE A &bull; SUNRISE RIDGE (1,425 MDPL)</text>

          <rect x="50" y="285" width="595" height="210" rx="20" fill="#d8f3dc" opacity="0.45" />
          <text x="70" y="305" font-size="11" font-weight="bold" fill="#1b4332" letter-spacing="1.5">ZONE B &bull; PINE GROVE WOODLAND</text>

          <rect x="670" y="285" width="490" height="210" rx="20" fill="#d8f3dc" opacity="0.45" />
          <text x="690" y="305" font-size="11" font-weight="bold" fill="#1b4332" letter-spacing="1.5">ZONE C &bull; MIST VALLEY TERRACE</text>

          <rect x="430" y="540" width="730" height="225" rx="20" fill="#d8f3dc" opacity="0.45" />
          <text x="450" y="560" font-size="11" font-weight="bold" fill="#1b4332" letter-spacing="1.5">ZONE D &bull; STARLIGHT OBSERVATION PLATEAU</text>

          <!-- Main Hiking Trails -->
          <path d="M 130,175 L 130,275 L 1140,275" fill="none" stroke="#d4a373" stroke-width="7" stroke-linecap="round" opacity="0.75" />
          <path d="M 655,275 L 655,525 L 1140,525" fill="none" stroke="#d4a373" stroke-width="7" stroke-linecap="round" opacity="0.75" />
          <text x="240" y="268" font-size="10" fill="#7f5539" font-weight="bold" letter-spacing="1">NORTH RIDGE TRAIL</text>
          <text x="760" y="518" font-size="10" fill="#7f5539" font-weight="bold" letter-spacing="1">SOUTH PLATEAU BOARDWALK</text>

          <!-- Environmental Landmarks -->
          <g transform="translate(55, 75)">
            <rect width="150" height="64" rx="14" fill="#1B4332" />
            <text x="75" y="28" text-anchor="middle" font-size="12" fill="#fefae0" font-weight="bold">MAIN GATE &amp; QR</text>
            <text x="75" y="46" text-anchor="middle" font-size="10" fill="#95d5b2">Check-In &amp; Gear Hub</text>
          </g>

          <g transform="translate(230, 75)">
            <rect width="185" height="64" rx="14" fill="#fefae0" stroke="#dda15e" stroke-width="2" />
            <text x="92" y="28" text-anchor="middle" font-size="11" fill="#9c5518" font-weight="bold">&#9728; SUNRISE VIEWPOINT</text>
            <text x="92" y="45" text-anchor="middle" font-size="9.5" fill="#7f5539">Eastern Cliff Overlook</text>
          </g>

          <g transform="translate(70, 560)">
            <circle cx="65" cy="65" r="55" fill="#bc6c25" opacity="0.92" />
            <text x="65" y="61" text-anchor="middle" font-size="12" fill="#fff" font-weight="bold">CAMPFIRE</text>
            <text x="65" y="77" text-anchor="middle" font-size="10" fill="#fefae0">Amphitheater</text>
          </g>

          <g transform="translate(235, 580)">
            <rect width="155" height="52" rx="12" fill="#475569" />
            <text x="77" y="24" text-anchor="middle" font-size="11" fill="#f8fafc" font-weight="bold">WASHROOMS</text>
            <text x="77" y="39" text-anchor="middle" font-size="9.5" fill="#cbd5e1">Hot Showers &amp; Prayer Room</text>
          </g>

          <!-- Dynamic Camping Plots -->
          @foreach($availability['plots'] as $plotItem)
          @php
          $coords = $plotItem['layout_coordinates'];
          $plotZone = $coords['zone'] ?? 'Main Zone';
          @endphp
          <g transform="translate({{ $coords['x'] }}, {{ $coords['y'] }})"
            @click="togglePlotById({{ $plotItem['id'] }})"
            @keydown.enter.prevent="togglePlotById({{ $plotItem['id'] }})"
            @mouseenter="hoveredPlot = getPlot({{ $plotItem['id'] }})"
            @mouseleave="hoveredPlot = null"
            :tabindex="isPlotAvailable({{ $plotItem['id'] }}) ? 0 : -1"
            role="button"
            :aria-pressed="isPlotSelected({{ $plotItem['id'] }}) ? 'true' : 'false'"
            :aria-label="`{{ $plotItem['name'] }} - Max {{ $plotItem['capacity'] }} guests`"
            data-area-id="{{ $plotItem['id'] }}"
            :data-status="getPlotStatus({{ $plotItem['id'] }})"
            :opacity="(activeZone === 'All Zones' || activeZone === @js($plotZone)) ? 1 : 0.25"
            :class="isPlotAvailable({{ $plotItem['id'] }}) ? 'cursor-pointer' : 'cursor-not-allowed'">

            <rect width="{{ $coords['width'] }}"
              height="{{ $coords['height'] }}"
              rx="12"
              :fill="isPlotSelected({{ $plotItem['id'] }}) ? '#DDA15E' : (isPlotAvailable({{ $plotItem['id'] }}) ? '#2D6A4F' : 'url(#bookedHatch)')"
              :stroke="isPlotSelected({{ $plotItem['id'] }}) ? '#9C5518' : (isPlotAvailable({{ $plotItem['id'] }}) ? '#1B4332' : '#94a3b8')"
              :stroke-width="isPlotSelected({{ $plotItem['id'] }}) ? 3.5 : 1.5" />

            <text x="{{ $coords['width'] / 2 }}"
              y="{{ ($coords['height'] / 2) - 4 }}"
              text-anchor="middle"
              font-size="12"
              font-weight="bold"
              :fill="isPlotSelected({{ $plotItem['id'] }}) ? '#1B4332' : (isPlotAvailable({{ $plotItem['id'] }}) ? '#ffffff' : '#475569')">
              {{ \Illuminate\Support\Str::afterLast($plotItem['name'], ' ') }}
            </text>

            <text x="{{ $coords['width'] / 2 }}"
              y="{{ ($coords['height'] / 2) + 13 }}"
              text-anchor="middle"
              font-size="10"
              :fill="isPlotSelected({{ $plotItem['id'] }}) ? '#1B4332' : (isPlotAvailable({{ $plotItem['id'] }}) ? '#d8f3dc' : '#64748b')"
              x-text="isPlotSelected({{ $plotItem['id'] }}) ? '✓ {{ $plotItem['capacity'] }}p' : (isPlotAvailable({{ $plotItem['id'] }}) ? '{{ $plotItem['capacity'] }} pax' : 'FULL')">
            </text>
          </g>
          @endforeach
        </svg>
      </div>
    </div>
  </div>

  <!-- 2-Column Section Below Map: Directory & Add-Ons (Left) + Sticky Summary (Right) -->
  <div class="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
    <!-- Left 8 Cols: Plot Directory + Equipment Add-Ons -->
    <div class="xl:col-span-8 space-y-8">
      <!-- Accessible Synchronized Card Selector Fallback -->
      <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200/80">
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500">
            Plot Directory (<span x-text="filteredPlots.length"></span> plots)
          </h3>
          <button type="button" x-show="selectedPlotIds.length > 0" @click="selectedPlotIds = []"
            class="text-xs text-red-600 hover:underline font-semibold">
            Clear Selection
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 max-h-80 overflow-y-auto pr-1">
          <template x-for="plot in filteredPlots" :key="'card-' + plot.id">
            <button type="button"
              @click="togglePlot(plot)"
              :disabled="!plot.is_available"
              :class="{
                'ring-2 ring-amber-600 bg-sand-50 border-amber-400': isPlotSelected(plot.id),
                'bg-white hover:border-forest-800 border-stone-200': plot.is_available && !isPlotSelected(plot.id),
                'bg-stone-100 opacity-60 cursor-not-allowed border-stone-200': !plot.is_available
              }"
              class="text-left p-3.5 rounded-xl border transition">
              <div class="flex justify-between items-start gap-2">
                <span class="font-bold text-sm text-forest-900" x-text="plot.name"></span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase shrink-0"
                  :class="isPlotSelected(plot.id) ? 'bg-amber-200 text-amber-950' : (plot.is_available ? 'bg-emerald-100 text-forest-900' : 'bg-stone-200 text-stone-600')"
                  x-text="isPlotSelected(plot.id) ? 'Selected' : (plot.is_available ? 'Available' : plot.status)"></span>
              </div>
              <p class="text-xs text-stone-500 mt-1" x-text="`Max ${plot.capacity} guests • ${plot.layout_coordinates.zone || ''}`"></p>
              <p class="text-sm font-bold text-terracotta-600 mt-1.5" x-text="formatRupiah(plot.price_per_night) + ' / night'"></p>
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

    <!-- Right 4 Cols: Sticky Multi-Plot Summary & Group Capacity Gauge -->
    <div class="xl:col-span-4 sticky top-24">
      <div class="bg-white rounded-2xl p-6 shadow-lg border border-stone-200">
        <div class="flex items-center justify-between border-b border-stone-100 pb-3">
          <h3 class="font-serif text-xl font-bold text-forest-900">Booking Summary</h3>
          <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-forest-100 text-forest-900"
            x-text="`${selectedPlots.length} Plot(s)`"></span>
        </div>

        <!-- Live Group Capacity Gauge -->
        <div class="mt-4 p-3.5 rounded-xl border"
          :class="selectedCapacity >= guestCount ? 'bg-emerald-50/70 border-emerald-200' : 'bg-amber-50 border-amber-300'">
          <div class="flex justify-between text-xs font-bold">
            <span :class="selectedCapacity >= guestCount ? 'text-emerald-900' : 'text-amber-900'">Group Capacity</span>
            <span :class="selectedCapacity >= guestCount ? 'text-emerald-900' : 'text-amber-900'"
              x-text="`${selectedCapacity} / ${guestCount} Guests`"></span>
          </div>
          <div class="w-full h-2 rounded-full bg-stone-200 mt-2 overflow-hidden">
            <div class="h-full transition-all duration-300"
              :class="selectedCapacity >= guestCount ? 'bg-emerald-600' : 'bg-amber-500'"
              :style="`width: ${Math.min(100, Math.round((selectedCapacity / Math.max(1, guestCount)) * 100))}%`"></div>
          </div>
          <p x-show="selectedCapacity < guestCount" class="text-[11px] text-amber-800 mt-1.5">
            Select <strong x-text="guestCount - selectedCapacity"></strong> more guest capacity by adding another plot.
          </p>
        </div>

        <template x-if="selectedPlots.length === 0">
          <p class="text-sm text-stone-500 py-8 text-center">Click one or more available green plots on the map to build your reservation.</p>
        </template>

        <template x-if="selectedPlots.length > 0">
          <div class="mt-4 space-y-4 text-sm">
            <!-- Selected Plot Pills -->
            <div class="space-y-2 max-h-44 overflow-y-auto pr-1">
              <template x-for="plot in selectedPlots" :key="'sel-' + plot.id">
                <div class="p-3 rounded-xl bg-sand-50 border border-amber-200 flex items-center justify-between gap-2">
                  <div>
                    <p class="font-bold text-forest-900 text-xs" x-text="plot.name"></p>
                    <p class="text-[11px] text-stone-500" x-text="`${plot.capacity} pax • ${formatRupiah(plot.price_per_night)} × ${nights} night`"></p>
                  </div>
                  <div class="text-right">
                    <p class="font-bold text-xs text-stone-900" x-text="formatRupiah(plot.price_per_night * nights)"></p>
                    <button type="button" @click="togglePlot(plot)" class="text-[10px] text-red-600 hover:underline">Remove</button>
                  </div>
                </div>
              </template>
            </div>

            <!-- Gear Add-On Line Items -->
            <div x-show="cartEntries.length > 0" class="space-y-1.5 border-t border-stone-100 pt-3">
              <p class="text-xs font-bold uppercase text-stone-400">Equipment Add-Ons</p>
              <template x-for="entry in cartEntries" :key="entry.id">
                <div class="flex justify-between text-xs text-stone-600">
                  <span x-text="`${entry.name} × ${entry.qty}`"></span>
                  <span class="font-medium" x-text="formatRupiah(entry.subtotal)"></span>
                </div>
              </template>
            </div>

            <div class="flex justify-between items-center text-base font-bold text-forest-900 border-t border-stone-200 pt-3">
              <span>Total Payment</span>
              <span class="text-xl text-terracotta-600" x-text="formatRupiah(grandTotal)"></span>
            </div>

            <p x-show="errorMessage" x-text="errorMessage" class="text-xs text-red-600 bg-red-50 p-2.5 rounded-xl"></p>

            <button type="button"
              @click="submitCheckout()"
              :disabled="submitting || selectedCapacity < guestCount"
              class="w-full py-3.5 rounded-xl bg-terracotta-600 hover:bg-terracotta-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold text-sm shadow-lg transition cursor-pointer">
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
      selectedPlotIds: [],
      hoveredPlot: null,
      activeZone: 'All Zones',
      zoom: 1,
      cart: {},
      submitting: false,
      errorMessage: '',

      get zones() {
        const uniqueZones = [...new Set(this.plots.map(p => p.layout_coordinates?.zone).filter(Boolean))];
        return ['All Zones', ...uniqueZones];
      },

      get filteredPlots() {
        if (this.activeZone === 'All Zones') return this.plots;
        return this.plots.filter(p => p.layout_coordinates?.zone === this.activeZone);
      },

      get nights() {
        const start = new Date(this.checkIn);
        const end = new Date(this.checkOut);
        const diff = Math.round((end - start) / (1000 * 60 * 60 * 24));
        return diff > 0 ? diff : 1;
      },

      get selectedPlots() {
        return this.plots.filter(p => this.selectedPlotIds.includes(p.id));
      },

      get selectedCapacity() {
        return this.selectedPlots.reduce((sum, p) => sum + Number(p.capacity), 0);
      },

      get plotsSubtotal() {
        return this.selectedPlots.reduce((sum, p) => sum + (Number(p.price_per_night) * this.nights), 0);
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
        return this.plotsSubtotal + gearTotal;
      },

      getPlot(id) {
        return this.plots.find(p => p.id === id) || null;
      },

      isPlotAvailable(id) {
        const plot = this.getPlot(id);
        return plot ? Boolean(plot.is_available) : false;
      },

      isPlotSelected(id) {
        return this.selectedPlotIds.includes(id);
      },

      getPlotStatus(id) {
        const plot = this.getPlot(id);
        return plot ? plot.status : 'unavailable';
      },

      togglePlotById(id) {
        const plot = this.getPlot(id);
        if (plot) this.togglePlot(plot);
      },

      togglePlot(plot) {
        if (!plot.is_available) return;
        this.errorMessage = '';

        if (this.selectedPlotIds.includes(plot.id)) {
          this.selectedPlotIds = this.selectedPlotIds.filter(id => id !== plot.id);
        } else {
          this.selectedPlotIds = [...this.selectedPlotIds, plot.id];
        }
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
          const availableIds = new Set(this.plots.filter(p => p.is_available).map(p => p.id));
          this.selectedPlotIds = this.selectedPlotIds.filter(id => availableIds.has(id));
        } else {
          this.errorMessage = json.message;
        }
      },

      async submitCheckout() {
        if (!config.isAuthenticated) {
          window.location.href = config.loginUrl;
          return;
        }
        if (this.selectedPlotIds.length === 0 || this.selectedCapacity < this.guestCount) return;

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
              camping_area_ids: this.selectedPlotIds,
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