@extends('layouts.nature')

@section('title', 'Admin Control Panel — Bukit Embun')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-10">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h1 class="font-serif text-3xl font-bold text-forest-900">Admin Operations Hub</h1>
      <p class="text-sm text-stone-500">Manage plots, coordinates, equipment inventory, gallery assets, and revenue.</p>
    </div>
    <a href="{{ route('admin.export.csv') }}" class="px-4 py-2.5 rounded-xl bg-forest-900 text-white text-xs font-semibold shadow hover:bg-forest-800">
      Export Transactions CSV
    </a>
  </div>

  @if(session('success'))
  <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm font-medium">
    {{ session('success') }}
  </div>
  @endif

  <!-- KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-4 gap-5">
    <div class="bg-white p-5 rounded-2xl border border-stone-200">
      <p class="text-xs font-bold uppercase text-stone-400">Settled Revenue</p>
      <p class="text-2xl font-bold text-forest-900 mt-1">Rp {{ number_format($stats['total_revenue'], 0, ',', '.') }}</p>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-stone-200">
      <p class="text-xs font-bold uppercase text-stone-400">Settled Bookings</p>
      <p class="text-2xl font-bold text-emerald-700 mt-1">{{ $stats['settled_bookings'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-stone-200">
      <p class="text-xs font-bold uppercase text-stone-400">Pending Payment</p>
      <p class="text-2xl font-bold text-amber-600 mt-1">{{ $stats['pending_bookings'] }}</p>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-stone-200">
      <p class="text-xs font-bold uppercase text-stone-400">Active Plots</p>
      <p class="text-2xl font-bold text-stone-900 mt-1">{{ $stats['total_plots'] }}</p>
    </div>
  </div>

  <!-- Camping Plot Coordinates & Pricing Management -->
  <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200">
    <h2 class="font-serif text-xl font-bold text-forest-900 mb-4">Camping Plots &amp; SVG Map Coordinates</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="text-xs uppercase text-stone-400 border-b">
          <tr>
            <th class="py-2">Name</th>
            <th>Zone</th>
            <th>Cap.</th>
            <th>Price/Night</th>
            <th>X / Y</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-stone-100">
          @foreach($campingAreas as $area)
          <tr>
            <form method="POST" action="{{ route('admin.areas.update', $area) }}">
              @csrf @method('PATCH')
              <td class="py-2.5 pr-2"><input name="name" value="{{ $area->name }}" class="rounded-lg border-stone-300 text-xs w-40"></td>
              <td class="pr-2"><input name="zone" value="{{ $area->layout_coordinates['zone'] ?? '' }}" class="rounded-lg border-stone-300 text-xs w-32"></td>
              <td class="pr-2"><input type="number" name="capacity" value="{{ $area->capacity }}" class="rounded-lg border-stone-300 text-xs w-16"></td>
              <td class="pr-2"><input type="number" name="price_per_night" value="{{ (int)$area->price_per_night }}" class="rounded-lg border-stone-300 text-xs w-28"></td>
              <td class="pr-2 flex gap-1 py-2.5">
                <input type="number" name="x" value="{{ $area->layout_coordinates['x'] ?? 0 }}" class="rounded-lg border-stone-300 text-xs w-16" title="X">
                <input type="number" name="y" value="{{ $area->layout_coordinates['y'] ?? 0 }}" class="rounded-lg border-stone-300 text-xs w-16" title="Y">
              </td>
              <td class="pr-2">
                <select name="status" class="rounded-lg border-stone-300 text-xs">
                  <option value="available" @selected($area->status === 'available')>available</option>
                  <option value="maintenance" @selected($area->status === 'maintenance')>maintenance</option>
                </select>
              </td>
              <td><button class="px-3 py-1.5 rounded-lg bg-forest-900 text-white text-xs font-semibold">Save</button></td>
            </form>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>

  <!-- Equipment Stock & Gallery Asset Management -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Equipment Stock -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200">
      <h2 class="font-serif text-xl font-bold text-forest-900 mb-4">Equipment Stock &amp; Rental Rates</h2>
      <div class="space-y-3">
        @foreach($facilities as $facility)
        <form method="POST" action="{{ route('admin.facilities.update', $facility) }}" class="flex items-center justify-between gap-2 text-xs border-b border-stone-100 pb-2.5">
          @csrf @method('PATCH')
          <span class="font-semibold text-stone-800 flex-1">{{ $facility->name }}</span>
          <input type="number" name="stock" value="{{ $facility->stock }}" class="w-16 rounded-lg border-stone-300 text-xs" title="Stock">
          <input type="number" name="rental_price" value="{{ (int)$facility->rental_price }}" class="w-24 rounded-lg border-stone-300 text-xs" title="Price">
          <button class="px-3 py-1.5 rounded-lg bg-stone-800 text-white font-semibold">Update</button>
        </form>
        @endforeach
      </div>
    </div>

    <!-- Gallery Asset CRUD -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200">
      <h2 class="font-serif text-xl font-bold text-forest-900 mb-4">Gallery Assets</h2>
      <form method="POST" action="{{ route('admin.galleries.store') }}" class="grid grid-cols-2 gap-2.5 text-xs mb-5 bg-stone-50 p-3.5 rounded-xl">
        @csrf
        <input name="title" placeholder="Photo Title" required class="rounded-lg border-stone-300 col-span-2">
        <select name="category" class="rounded-lg border-stone-300">
          <option value="camping_area">camping_area</option>
          <option value="ambiance">ambiance</option>
          <option value="facilities">facilities</option>
          <option value="hero">hero</option>
        </select>
        <select name="source_provider" class="rounded-lg border-stone-300">
          <option value="unsplash">unsplash</option>
          <option value="pexels">pexels</option>
          <option value="pixabay">pixabay</option>
        </select>
        <input name="image_url" placeholder="Full Image URL" required class="rounded-lg border-stone-300 col-span-2">
        <input name="thumbnail_url" placeholder="Thumbnail URL" required class="rounded-lg border-stone-300 col-span-2">
        <input name="photographer_name" placeholder="Photographer Name" required class="rounded-lg border-stone-300">
        <button class="rounded-lg bg-terracotta-600 text-white font-semibold py-2">Add Photo</button>
      </form>

      <div class="max-h-64 overflow-y-auto divide-y divide-stone-100 text-xs">
        @foreach($galleries as $photo)
        <div class="py-2 flex items-center justify-between gap-2">
          <div class="flex items-center gap-2.5">
            <img src="{{ $photo->thumbnail_url }}" class="w-10 h-10 rounded-lg object-cover">
            <div>
              <p class="font-semibold text-stone-900">{{ $photo->title }}</p>
              <p class="text-stone-400">{{ $photo->category }} &bull; {{ $photo->photographer_name }}</p>
            </div>
          </div>
          <form method="POST" action="{{ route('admin.galleries.destroy', $photo) }}">
            @csrf @method('DELETE')
            <button class="text-red-600 hover:underline font-semibold">Delete</button>
          </form>
        </div>
        @endforeach
      </div>
    </div>
  </div>

  <!-- Real-Time Transaction Monitoring -->
  <div class="bg-white rounded-2xl p-6 shadow-sm border border-stone-200">
    <h2 class="font-serif text-xl font-bold text-forest-900 mb-4">Recent Transactions</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="uppercase text-stone-400 border-b">
          <tr>
            <th class="py-2">Booking Code</th>
            <th>Visitor</th>
            <th>Plot</th>
            <th>Dates</th>
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
            <td>{{ $res->campingArea->name }}</td>
            <td>{{ $res->check_in_date->format('d M') }} – {{ $res->check_out_date->format('d M Y') }}</td>
            <td class="font-semibold">Rp {{ number_format($res->total_price, 0, ',', '.') }}</td>
            <td>
              <span class="px-2 py-0.5 rounded-full font-bold uppercase
                                    {{ $res->payment_status === 'settled' ? 'bg-emerald-100 text-emerald-900' : 'bg-amber-100 text-amber-900' }}">
                {{ $res->payment_status }}
              </span>
            </td>
            <td>{{ $res->ticket?->is_checked_in ? 'Checked In' : '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="mt-4">{{ $reservations->links() }}</div>
  </div>
</div>
@endsection