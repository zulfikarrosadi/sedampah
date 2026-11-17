<?php

namespace Tests\Feature;

use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\User;
use App\Services\BookingService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
  use RefreshDatabase;

  protected BookingService $bookingService;

  protected function setUp(): void
  {
    parent::setUp();
    $this->bookingService = new BookingService();
  }

  public function test_calculates_total_price_accurately_with_addons(): void
  {
    $user = User::factory()->create();
    $area = CampingArea::create([
      'name' => 'Zona Test',
      'code' => 'ZN-T',
      'capacity' => 5,
      'price_per_night' => 50000,
      'status' => 'available',
    ]);
    $tenda = Facility::create([
      'name' => 'Tenda 4P',
      'category' => 'tenda',
      'stock' => 10,
      'rental_price' => 90000,
      'unit' => 'unit',
    ]);

    // Menginap 2 malam: Area (50.000 * 2 = 100.000) + Tenda (90.000 * 1 * 2 = 180.000) = 280.000
    $reservation = $this->bookingService->createReservation($user->id, [
      'camping_area_id' => $area->id,
      'check_in_date' => now()->addDays(2)->toDateString(),
      'check_out_date' => now()->addDays(4)->toDateString(),
      'guest_count' => 4,
      'items' => [
        ['facility_id' => $tenda->id, 'qty' => 1],
      ],
    ]);

    $this->assertEquals(280000, $reservation->total_price);
    $this->assertEquals('pending', $reservation->payment_status);
  }

  public function test_prevents_overbooking_when_area_quota_is_full(): void
  {
    $user = User::factory()->create();
    $area = CampingArea::create([
      'name' => 'Zona Terbatas',
      'code' => 'ZN-LTD',
      'capacity' => 1, // Kapasitas hanya 1 slot
      'price_per_night' => 50000,
      'status' => 'available',
    ]);

    $checkIn = now()->addDays(5)->toDateString();
    $checkOut = now()->addDays(6)->toDateString();

    // Reservasi pertama berhasil
    $this->bookingService->createReservation($user->id, [
      'camping_area_id' => $area->id,
      'check_in_date' => $checkIn,
      'check_out_date' => $checkOut,
      'guest_count' => 2,
    ]);

    // Reservasi kedua pada tanggal yang sama wajib ditolak
    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Kuota untuk {$area->name} pada tanggal yang dipilih sudah penuh.");

    $this->bookingService->createReservation($user->id, [
      'camping_area_id' => $area->id,
      'check_in_date' => $checkIn,
      'check_out_date' => $checkOut,
      'guest_count' => 2,
    ]);
  }

  public function test_prevents_booking_when_facility_stock_insufficient(): void
  {
    $user = User::factory()->create();
    $area = CampingArea::create([
      'name' => 'Zona Luas',
      'code' => 'ZN-L',
      'capacity' => 10,
      'price_per_night' => 50000,
      'status' => 'available',
    ]);
    $kompor = Facility::create([
      'name' => 'Kompor Portable',
      'category' => 'alat_masak',
      'stock' => 2, // Stok hanya 2
      'rental_price' => 35000,
      'unit' => 'set',
    ]);

    $this->expectException(Exception::class);
    $this->expectExceptionMessage("Stok Kompor Portable tidak mencukupi (tersisa 2 set).");

    $this->bookingService->createReservation($user->id, [
      'camping_area_id' => $area->id,
      'check_in_date' => now()->addDays(3)->toDateString(),
      'check_out_date' => now()->addDays(4)->toDateString(),
      'guest_count' => 4,
      'items' => [
        ['facility_id' => $kompor->id, 'qty' => 3], // Memesan 3 padahal stok 2
      ],
    ]);
  }
}
