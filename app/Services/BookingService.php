<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class BookingService
{
  /**
   * Scope query to reservations that actively hold inventory:
   * - payment_status === 'settled'
   * - OR payment_status === 'pending' within the unexpired payment window
   */
  public function scopeActiveHold(Builder $query): Builder
  {
    $expiryCutoff = now()->subMinutes(config('camping.pending_expiry_minutes', 60));

    return $query->where(function (Builder $q) use ($expiryCutoff) {
      $q->where('payment_status', 'settled')
        ->orWhere(function (Builder $pending) use ($expiryCutoff) {
          $pending->where('payment_status', 'pending')
            ->where('created_at', '>=', $expiryCutoff);
        });
    });
  }

  /**
   * Check date-level quota and plot-level availability for the interactive map & booking gate.
   *
   * @return array{
   *   available: bool,
   *   daily_active_count: int,
   *   max_daily_quota: int,
   *   remaining_daily_quota: int,
   *   booked_area_ids: array<int>,
   *   plots: array<int, array<string, mixed>>
   * }
   */
  public function checkAvailability(
    string $checkInDate,
    ?string $checkOutDate = null,
    ?int $campingAreaId = null
  ): array {
    $checkIn = Carbon::parse($checkInDate)->startOfDay();
    $checkOut = $checkOutDate
      ? Carbon::parse($checkOutDate)->startOfDay()
      : $checkIn->copy()->addDay();

    if ($checkOut->lte($checkIn)) {
      throw new BookingException('Check-out date must be at least 1 night after check-in date.');
    }

    // 1. Count active reservations overlapping the check-in date
    $dailyActiveCount = $this->scopeActiveHold(Reservation::query())
      ->whereDate('check_in_date', '<=', $checkIn->toDateString())
      ->whereDate('check_out_date', '>', $checkIn->toDateString())
      ->count();

    $maxQuota = (int) config('camping.max_daily_reservations', 20);
    $remainingQuota = max(0, $maxQuota - $dailyActiveCount);

    // 2. Find all camping_area_ids with overlapping active reservations in [checkIn, checkOut)
    $bookedAreaIds = $this->scopeActiveHold(Reservation::query())
      ->whereDate('check_in_date', '<', $checkOut->toDateString())
      ->whereDate('check_out_date', '>', $checkIn->toDateString())
      ->pluck('camping_area_id')
      ->unique()
      ->values()
      ->all();

    $areas = CampingArea::orderBy('id')->get();
    $plots = $areas->map(function (CampingArea $area) use ($bookedAreaIds, $remainingQuota) {
      $isMaintenance = $area->status !== 'available';
      $isBooked = in_array($area->id, $bookedAreaIds, true);
      $isAvailable = !$isMaintenance && !$isBooked && $remainingQuota > 0;

      return [
        'id' => $area->id,
        'name' => $area->name,
        'capacity' => $area->capacity,
        'price_per_night' => (float) $area->price_per_night,
        'status' => $isMaintenance ? 'maintenance' : ($isBooked ? 'booked' : 'available'),
        'is_available' => $isAvailable,
        'description' => $area->description,
        'layout_coordinates' => $area->layout_coordinates,
      ];
    })->all();

    $overallAvailable = $remainingQuota > 0;

    if ($campingAreaId !== null) {
      $targetPlot = collect($plots)->firstWhere('id', $campingAreaId);
      $overallAvailable = $targetPlot !== null && $targetPlot['is_available'];
    }

    return [
      'available' => $overallAvailable,
      'daily_active_count' => $dailyActiveCount,
      'max_daily_quota' => $maxQuota,
      'remaining_daily_quota' => $remainingQuota,
      'booked_area_ids' => $bookedAreaIds,
      'plots' => $plots,
    ];
  }

  /**
   * Calculate exact pricing breakdown before or during reservation creation.
   *
   * @param  array<int, array{facility_id: int, qty: int}>  $addOns
   * @return array{
   *   duration_nights: int,
   *   area_price_snapshot: float,
   *   area_subtotal: float,
   *   items_subtotal: float,
   *   total_price: float,
   *   resolved_items: array<int, array<string, mixed>>
   * }
   */
  public function calculateTotal(
    CampingArea $area,
    string $checkInDate,
    string $checkOutDate,
    array $addOns = []
  ): array {
    $checkIn = Carbon::parse($checkInDate)->startOfDay();
    $checkOut = Carbon::parse($checkOutDate)->startOfDay();

    if ($checkOut->lte($checkIn)) {
      throw new BookingException('Check-out date must be after check-in date.');
    }

    $durationNights = (int) $checkIn->diffInDays($checkOut);
    $areaPriceSnapshot = (float) $area->price_per_night;
    $areaSubtotal = round($areaPriceSnapshot * $durationNights, 2);

    // Normalize duplicate facility_id entries if passed
    $normalizedQty = [];
    foreach ($addOns as $item) {
      $facilityId = (int) ($item['facility_id'] ?? 0);
      $qty = (int) ($item['qty'] ?? 0);
      if ($facilityId > 0 && $qty > 0) {
        $normalizedQty[$facilityId] = ($normalizedQty[$facilityId] ?? 0) + $qty;
      }
    }

    $resolvedItems = [];
    $itemsSubtotal = 0.0;

    if (!empty($normalizedQty)) {
      $facilities = Facility::whereIn('id', array_keys($normalizedQty))
        ->get()
        ->keyBy('id');

      foreach ($normalizedQty as $facilityId => $qty) {
        $facility = $facilities->get($facilityId);
        if (!$facility) {
          throw new BookingException("Selected facility ID {$facilityId} does not exist.");
        }

        $priceSnapshot = (float) $facility->rental_price;
        $subtotal = round($priceSnapshot * $qty, 2);
        $itemsSubtotal += $subtotal;

        $resolvedItems[] = [
          'facility' => $facility,
          'facility_id' => $facility->id,
          'qty' => $qty,
          'price_snapshot' => $priceSnapshot,
          'subtotal' => $subtotal,
        ];
      }
    }

    $itemsSubtotal = round($itemsSubtotal, 2);
    $totalPrice = round($areaSubtotal + $itemsSubtotal, 2);

    return [
      'duration_nights' => $durationNights,
      'area_price_snapshot' => $areaPriceSnapshot,
      'area_subtotal' => $areaSubtotal,
      'items_subtotal' => $itemsSubtotal,
      'total_price' => $totalPrice,
      'resolved_items' => $resolvedItems,
    ];
  }

  /**
   * Atomically create a reservation with pessimistic row locks (lockForUpdate)
   * on the target CampingArea and requested Facilities to prevent race conditions.
   *
   * @param  array{
   *   user_id: int,
   *   camping_area_id: int,
   *   check_in_date: string,
   *   check_out_date: string,
   *   guest_count?: int,
   *   items?: array<int, array{facility_id: int, qty: int}>
   * }  $payload
   *
   * @throws Throwable
   */
  public function createReservation(array $payload): Reservation
  {
    $checkIn = Carbon::parse($payload['check_in_date'])->startOfDay();
    $checkOut = Carbon::parse($payload['check_out_date'])->startOfDay();
    $guestCount = max(1, (int) ($payload['guest_count'] ?? 1));
    $rawItems = $payload['items'] ?? [];

    if ($checkOut->lte($checkIn)) {
      throw new BookingException('Check-out date must be after check-in date.');
    }

    DB::beginTransaction();

    try {
      // 1. Lock the target camping area row to serialize concurrent bookings on this plot
      $area = CampingArea::where('id', $payload['camping_area_id'])
        ->lockForUpdate()
        ->first();

      if (!$area) {
        throw new BookingException('Selected camping plot was not found.');
      }

      if ($area->status !== 'available') {
        throw new BookingException("Camping plot {$area->name} is currently under maintenance.");
      }

      if ($guestCount > $area->capacity) {
        throw new BookingException(
          "Guest count ({$guestCount}) exceeds {$area->name} maximum capacity ({$area->capacity} guests)."
        );
      }

      // 2. Verify daily quota & plot-specific overlap inside the locked transaction
      $maxQuota = (int) config('camping.max_daily_reservations', 20);
      $dailyActiveCount = $this->scopeActiveHold(Reservation::query())
        ->whereDate('check_in_date', '<=', $checkIn->toDateString())
        ->whereDate('check_out_date', '>', $checkIn->toDateString())
        ->lockForUpdate()
        ->count();

      if ($dailyActiveCount >= $maxQuota) {
        throw new BookingException('Daily campsite reservation quota is full for the selected check-in date.');
      }

      $hasPlotConflict = $this->scopeActiveHold(Reservation::query())
        ->where('camping_area_id', $area->id)
        ->whereDate('check_in_date', '<', $checkOut->toDateString())
        ->whereDate('check_out_date', '>', $checkIn->toDateString())
        ->lockForUpdate()
        ->exists();

      if ($hasPlotConflict) {
        throw new BookingException("Camping plot {$area->name} is already reserved for the selected dates.");
      }

      // 3. Calculate pricing & lock requested facility rows in deterministic ID order (prevents deadlocks)
      $pricing = $this->calculateTotal(
        $area,
        $checkIn->toDateString(),
        $checkOut->toDateString(),
        $rawItems
      );

      $facilityIds = collect($pricing['resolved_items'])
        ->pluck('facility_id')
        ->sort()
        ->values()
        ->all();

      $lockedFacilities = empty($facilityIds)
        ? collect()
        : Facility::whereIn('id', $facilityIds)
        ->orderBy('id')
        ->lockForUpdate()
        ->get()
        ->keyBy('id');

      foreach ($pricing['resolved_items'] as $resolved) {
        $facility = $lockedFacilities->get($resolved['facility_id']);
        if (!$facility || $facility->stock < $resolved['qty']) {
          $availableStock = $facility ? $facility->stock : 0;
          $name = $facility ? $facility->name : "ID #{$resolved['facility_id']}";
          throw new BookingException(
            "Insufficient stock for {$name}. Requested: {$resolved['qty']}, Available: {$availableStock}."
          );
        }
      }

      // 4. Create reservation record with historical price snapshots
      $reservation = Reservation::create([
        'user_id' => $payload['user_id'],
        'camping_area_id' => $area->id,
        'booking_code' => $this->generateBookingCode($checkIn),
        'check_in_date' => $checkIn->toDateString(),
        'check_out_date' => $checkOut->toDateString(),
        'duration_nights' => $pricing['duration_nights'],
        'guest_count' => $guestCount,
        'area_price_snapshot' => $pricing['area_price_snapshot'],
        'total_price' => $pricing['total_price'],
        'payment_status' => 'pending',
      ]);

      // 5. Persist reservation items & decrement physical facility stock
      foreach ($pricing['resolved_items'] as $resolved) {
        ReservationItem::create([
          'reservation_id' => $reservation->id,
          'facility_id' => $resolved['facility_id'],
          'qty' => $resolved['qty'],
          'price_snapshot' => $resolved['price_snapshot'],
          'subtotal' => $resolved['subtotal'],
        ]);

        $lockedFacilities->get($resolved['facility_id'])->decrement('stock', $resolved['qty']);
      }

      DB::commit();

      return $reservation->load(['campingArea', 'items.facility', 'user']);
    } catch (Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }

  /**
   * Release reserved stock if a reservation expires or is cancelled.
   */
  public function cancelOrExpireReservation(Reservation $reservation, string $targetStatus = 'cancelled'): Reservation
  {
    if (!in_array($targetStatus, ['cancelled', 'expired'], true)) {
      throw new BookingException('Invalid cancellation status.');
    }

    DB::beginTransaction();

    try {
      $lockedReservation = Reservation::where('id', $reservation->id)
        ->with('items')
        ->lockForUpdate()
        ->firstOrFail();

      if (in_array($lockedReservation->payment_status, ['cancelled', 'expired'], true)) {
        DB::commit();
        return $lockedReservation;
      }

      if ($lockedReservation->payment_status === 'settled') {
        throw new BookingException('Settled reservations cannot be auto-expired or cancelled directly.');
      }

      $facilityIds = $lockedReservation->items->pluck('facility_id')->sort()->values()->all();
      $lockedFacilities = empty($facilityIds)
        ? collect()
        : Facility::whereIn('id', $facilityIds)
        ->orderBy('id')
        ->lockForUpdate()
        ->get()
        ->keyBy('id');

      foreach ($lockedReservation->items as $item) {
        if ($facility = $lockedFacilities->get($item->facility_id)) {
          $facility->increment('stock', $item->qty);
        }
      }

      $lockedReservation->update(['payment_status' => $targetStatus]);

      DB::commit();

      return $lockedReservation;
    } catch (Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }

  protected function generateBookingCode(Carbon $checkIn): string
  {
    do {
      $code = sprintf('BE-%s-%s', $checkIn->format('Ymd'), strtoupper(Str::random(4)));
    } while (Reservation::where('booking_code', $code)->exists());

    return $code;
  }
}
