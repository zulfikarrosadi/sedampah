<?php

namespace App\Services;

use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingService
{
  /**
   * Menghitung sisa kuota kavling/area pada tanggal tertentu.
   * Reservasi yang dihitung mengurangi kuota adalah status 'settled' dan 'pending'.
   */
  public function getRemainingAreaQuota(int $campingAreaId, string $checkInDate, string $checkOutDate): int
  {
    $area = CampingArea::findOrFail($campingAreaId);

    if ($area->status !== 'available') {
      return 0;
    }

    // Hitung jumlah reservasi yang tanggalnya beririsan (overlap) dengan rentang tanggal yang dipilih
    $bookedCount = Reservation::where('camping_area_id', $campingAreaId)
      ->whereIn('payment_status', ['settled', 'pending'])
      ->where(function ($query) use ($checkInDate, $checkOutDate) {
        $query->where('check_in_date', '<', $checkOutDate)
          ->where('check_out_date', '>', $checkInDate);
      })
      ->count();

    return max(0, $area->capacity - $bookedCount);
  }

  /**
   * Menghitung sisa stok peralatan (add-on) pada rentang tanggal tertentu.
   */
  public function getRemainingFacilityStock(int $facilityId, string $checkInDate, string $checkOutDate): int
  {
    $facility = Facility::findOrFail($facilityId);

    $bookedQty = ReservationItem::where('facility_id', $facilityId)
      ->whereHas('reservation', function ($query) use ($checkInDate, $checkOutDate) {
        $query->whereIn('payment_status', ['settled', 'pending'])
          ->where('check_in_date', '<', $checkOutDate)
          ->where('check_out_date', '>', $checkInDate);
      })
      ->sum('qty');

    return max(0, $facility->stock - $bookedQty);
  }

  /**
   * Mendapatkan ringkasan ketersediaan seluruh area & jumlah pengunjung per tanggal (untuk kalender publik).
   */
  public function getDailySummaryByDate(string $date): array
  {
    $totalCapacity = CampingArea::where('status', 'available')->sum('capacity');

    $activeReservations = Reservation::whereIn('payment_status', ['settled', 'pending'])
      ->where('check_in_date', '<=', $date)
      ->where('check_out_date', '>', $date)
      ->get();

    $usedSlots = $activeReservations->count();
    $totalVisitors = $activeReservations->sum('guest_count');

    return [
      'date' => $date,
      'total_capacity' => $totalCapacity,
      'used_slots' => $usedSlots,
      'remaining_slots' => max(0, $totalCapacity - $usedSlots),
      'total_visitors' => $totalVisitors,
    ];
  }

  /**
   * Membuat reservasi baru dengan penguncian database (Atomic Transaction).
   *
   * @param int $userId
   * @param array $data ['camping_area_id', 'check_in_date', 'check_out_date', 'guest_count', 'items' => [['facility_id' => 1, 'qty' => 2]]]
   * @return Reservation
   * @throws Exception
   */
  public function createReservation(int $userId, array $data): Reservation
  {
    return DB::transaction(function () use ($userId, $data) {
      $checkIn = Carbon::parse($data['check_in_date'])->startOfDay();
      $checkOut = Carbon::parse($data['check_out_date'])->startOfDay();

      if ($checkOut->lte($checkIn)) {
        throw new Exception('Tanggal check-out harus setelah tanggal check-in.');
      }

      $durationNights = $checkIn->diffInDays($checkOut);

      // 1. Kunci baris CampingArea untuk mencegah race condition / overbooking
      $area = CampingArea::where('id', $data['camping_area_id'])
        ->lockForUpdate()
        ->firstOrFail();

      $remainingAreaQuota = $this->getRemainingAreaQuota(
        $area->id,
        $checkIn->toDateString(),
        $checkOut->toDateString()
      );

      if ($remainingAreaQuota <= 0) {
        throw new Exception("Kuota untuk {$area->name} pada tanggal yang dipilih sudah penuh.");
      }

      // 2. Hitung biaya dasar area camping
      $areaSubtotal = $area->price_per_night * $durationNights;
      $addOnsTotal = 0;
      $preparedItems = [];

      // 3. Validasi & kunci stok setiap peralatan tambahan (add-on)
      if (!empty($data['items'])) {
        foreach ($data['items'] as $item) {
          if ($item['qty'] <= 0) {
            continue;
          }

          $facility = Facility::where('id', $item['facility_id'])
            ->lockForUpdate()
            ->firstOrFail();

          $remainingStock = $this->getRemainingFacilityStock(
            $facility->id,
            $checkIn->toDateString(),
            $checkOut->toDateString()
          );

          if ($item['qty'] > $remainingStock) {
            throw new Exception("Stok {$facility->name} tidak mencukupi (tersisa {$remainingStock} {$facility->unit}).");
          }

          $itemSubtotal = $facility->rental_price * $item['qty'] * $durationNights;
          $addOnsTotal += $itemSubtotal;

          $preparedItems[] = [
            'facility_id' => $facility->id,
            'qty' => $item['qty'],
            'price_snapshot' => $facility->rental_price,
            'subtotal' => $itemSubtotal,
          ];
        }
      }

      // 4. Generate Kode Booking Unik (Format: BE-YYYYMMDD-XXXX)
      $bookingCode = 'BE-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

      // 5. Simpan Header Reservasi
      $reservation = Reservation::create([
        'user_id' => $userId,
        'camping_area_id' => $area->id,
        'booking_code' => $bookingCode,
        'check_in_date' => $checkIn->toDateString(),
        'check_out_date' => $checkOut->toDateString(),
        'duration_nights' => $durationNights,
        'guest_count' => $data['guest_count'] ?? 1,
        'area_price_snapshot' => $area->price_per_night,
        'total_price' => $areaSubtotal + $addOnsTotal,
        'payment_status' => 'pending',
      ]);

      // 6. Simpan Detail Item Add-on
      foreach ($preparedItems as $prepItem) {
        $reservation->items()->create($prepItem);
      }

      return $reservation->load(['campingArea', 'items.facility']);
    });
  }
}
