<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\ReservationArea;
use App\Models\ReservationItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
   * Check date-level quota and plot-level availability across both `reservation_areas`
   * and legacy `reservations.camping_area_id` for the interactive map & booking gate.
   *
   * @param  int|array<int>|null  $campingAreaIds
   */
  public function checkAvailability(
    string $checkInDate,
    ?string $checkOutDate = null,
    int|array|null $campingAreaIds = null
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

    $maxQuota = (int) config('camping.max_daily_reservations', 50);
    $remainingQuota = max(0, $maxQuota - $dailyActiveCount);

    // 2. Find all camping_area_ids with overlapping active reservations in [checkIn, checkOut)
    $overlappingReservationIds = $this->scopeActiveHold(Reservation::query())
      ->whereDate('check_in_date', '<', $checkOut->toDateString())
      ->whereDate('check_out_date', '>', $checkIn->toDateString())
      ->pluck('id');

    $pivotBookedIds = ReservationArea::whereIn('reservation_id', $overlappingReservationIds)
      ->pluck('camping_area_id');

    $legacyBookedIds = Reservation::whereIn('id', $overlappingReservationIds)
      ->whereNotNull('camping_area_id')
      ->pluck('camping_area_id');

    $bookedAreaIds = $pivotBookedIds
      ->merge($legacyBookedIds)
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

    if ($campingAreaIds !== null) {
      $requestedIds = is_array($campingAreaIds) ? $campingAreaIds : [$campingAreaIds];
      $plotMap = collect($plots)->keyBy('id');

      foreach ($requestedIds as $id) {
        $target = $plotMap->get((int) $id);
        if (!$target || !$target['is_available']) {
          $overallAvailable = false;
          break;
        }
      }
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
   * Calculate exact pricing breakdown across 1..N camping areas and optional add-ons.
   *
   * @param  Collection<int, CampingArea>|CampingArea  $areas
   * @param  array<int, array{facility_id: int, qty: int}>  $addOns
   */
  public function calculateTotal(
    Collection|CampingArea $areas,
    string $checkInDate,
    string $checkOutDate,
    array $addOns = []
  ): array {
    $checkIn = Carbon::parse($checkInDate)->startOfDay();
    $checkOut = Carbon::parse($checkOutDate)->startOfDay();

    if ($checkOut->lte($checkIn)) {
      throw new BookingException('Check-out date must be after check-in date.');
    }

    $areaCollection = $areas instanceof CampingArea ? collect([$areas]) : $areas;
    if ($areaCollection->isEmpty()) {
      throw new BookingException('At least one camping plot must be selected.');
    }

    $durationNights = (int) $checkIn->diffInDays($checkOut);

    $resolvedAreas = [];
    $areasSubtotal = 0.0;
    $totalCapacity = 0;

    foreach ($areaCollection as $area) {
      $priceSnapshot = (float) $area->price_per_night;
      $subtotal = round($priceSnapshot * $durationNights, 2);
      $areasSubtotal += $subtotal;
      $totalCapacity += (int) $area->capacity;

      $resolvedAreas[] = [
        'camping_area' => $area,
        'camping_area_id' => $area->id,
        'area_price_snapshot' => $priceSnapshot,
        'subtotal' => $subtotal,
      ];
    }

    $areasSubtotal = round($areasSubtotal, 2);

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
    $totalPrice = round($areasSubtotal + $itemsSubtotal, 2);

    return [
      'duration_nights' => $durationNights,
      'total_capacity' => $totalCapacity,
      'areas_subtotal' => $areasSubtotal,
      'items_subtotal' => $itemsSubtotal,
      'total_price' => $totalPrice,
      'resolved_areas' => $resolvedAreas,
      'resolved_items' => $resolvedItems,
    ];
  }

  /**
   * Atomically create a single- or multi-plot reservation with deterministic row locks
   * (`lockForUpdate()`) on all target CampingAreas and Facilities.
   *
   * Supports both `camping_area_ids` (array) and legacy `camping_area_id` (int).
   *
   * @param  array{
   *   user_id: int,
   *   camping_area_ids?: array<int>,
   *   camping_area_id?: int,
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

    // Normalize single or multiple area IDs in deterministic ascending order (prevents deadlocks)
    $rawAreaIds = $payload['camping_area_ids'] ?? (isset($payload['camping_area_id']) ? [$payload['camping_area_id']] : []);
    $areaIds = collect($rawAreaIds)
      ->map(fn($id) => (int) $id)
      ->filter(fn($id) => $id > 0)
      ->unique()
      ->sort()
      ->values()
      ->all();

    if (empty($areaIds)) {
      throw new BookingException('Please select at least one camping plot.');
    }

    if ($checkOut->lte($checkIn)) {
      throw new BookingException('Check-out date must be after check-in date.');
    }

    DB::beginTransaction();

    try {
      // 1. Lock all target camping area rows in ascending ID order
      $lockedAreas = CampingArea::whereIn('id', $areaIds)
        ->orderBy('id')
        ->lockForUpdate()
        ->get();

      if ($lockedAreas->count() !== count($areaIds)) {
        throw new BookingException('One or more selected camping plots do not exist.');
      }

      foreach ($lockedAreas as $area) {
        if ($area->status !== 'available') {
          throw new BookingException("Camping plot {$area->name} is currently under maintenance.");
        }
      }

      $combinedCapacity = (int) $lockedAreas->sum('capacity');
      if ($guestCount > $combinedCapacity) {
        throw new BookingException(
          "Guest count ({$guestCount}) exceeds combined plot capacity ({$combinedCapacity} guests). Please add another plot."
        );
      }

      // 2. Verify daily quota & check date overlap across ALL selected plots inside locked transaction
      $maxQuota = (int) config('camping.max_daily_reservations', 50);
      $dailyActiveCount = $this->scopeActiveHold(Reservation::query())
        ->whereDate('check_in_date', '<=', $checkIn->toDateString())
        ->whereDate('check_out_date', '>', $checkIn->toDateString())
        ->lockForUpdate()
        ->count();

      if ($dailyActiveCount >= $maxQuota) {
        throw new BookingException('Daily campsite reservation quota is full for the selected check-in date.');
      }

      $overlappingReservationIds = $this->scopeActiveHold(Reservation::query())
        ->whereDate('check_in_date', '<', $checkOut->toDateString())
        ->whereDate('check_out_date', '>', $checkIn->toDateString())
        ->lockForUpdate()
        ->pluck('id');

      if ($overlappingReservationIds->isNotEmpty()) {
        $conflictingPivotAreaIds = ReservationArea::whereIn('reservation_id', $overlappingReservationIds)
          ->whereIn('camping_area_id', $areaIds)
          ->pluck('camping_area_id');

        $conflictingLegacyAreaIds = Reservation::whereIn('id', $overlappingReservationIds)
          ->whereIn('camping_area_id', $areaIds)
          ->pluck('camping_area_id');

        $conflictingIds = $conflictingPivotAreaIds->merge($conflictingLegacyAreaIds)->unique();

        if ($conflictingIds->isNotEmpty()) {
          $conflictNames = $lockedAreas
            ->whereIn('id', $conflictingIds->all())
            ->pluck('name')
            ->join(', ');

          throw new BookingException("Camping plot(s) {$conflictNames} already reserved for the selected dates.");
        }
      }

      // 3. Calculate pricing & lock requested facility rows in deterministic ID order
      $pricing = $this->calculateTotal(
        $lockedAreas,
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

      // 4. Create reservation header (populating primary plot on header for backward compatibility)
      $primaryResolvedArea = $pricing['resolved_areas'][0];

      $reservation = Reservation::create([
        'user_id' => $payload['user_id'],
        'camping_area_id' => $primaryResolvedArea['camping_area_id'],
        'booking_code' => $this->generateBookingCode($checkIn),
        'check_in_date' => $checkIn->toDateString(),
        'check_out_date' => $checkOut->toDateString(),
        'duration_nights' => $pricing['duration_nights'],
        'guest_count' => $guestCount,
        'area_price_snapshot' => $primaryResolvedArea['area_price_snapshot'],
        'total_price' => $pricing['total_price'],
        'payment_status' => 'pending',
      ]);

      // 5. Persist all selected plots in `reservation_areas`
      foreach ($pricing['resolved_areas'] as $resolvedArea) {
        ReservationArea::create([
          'reservation_id' => $reservation->id,
          'camping_area_id' => $resolvedArea['camping_area_id'],
          'area_price_snapshot' => $resolvedArea['area_price_snapshot'],
          'subtotal' => $resolvedArea['subtotal'],
        ]);
      }

      // 6. Persist reservation items & decrement physical facility stock
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

      return $reservation->load(['campingArea', 'areas.campingArea', 'items.facility', 'user']);
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
