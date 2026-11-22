<?php

namespace Tests\Feature;

use App\Exceptions\BookingException;
use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
  use RefreshDatabase;

  private BookingService $bookingService;
  private User $visitor;
  private CampingArea $areaA;
  private CampingArea $areaB;
  private Facility $tent;
  private Facility $lantern;

  protected function setUp(): void
  {
    parent::setUp();

    $this->bookingService = new BookingService();

    $this->visitor = User::create([
      'name' => 'Raka Pratama',
      'email' => 'raka@example.com',
      'password' => bcrypt('password'),
      'phone' => '081234567890',
      'role' => 'pengunjung',
    ]);

    $this->areaA = CampingArea::create([
      'name' => 'Sunrise Ridge A1',
      'capacity' => 4,
      'price_per_night' => 175000,
      'status' => 'available',
      'description' => 'Cliffside plot',
      'layout_coordinates' => ['x' => 100, 'y' => 80, 'width' => 60, 'height' => 40, 'zone' => 'Sunrise Ridge'],
    ]);

    $this->areaB = CampingArea::create([
      'name' => 'Pine Grove B1',
      'capacity' => 6,
      'price_per_night' => 225000,
      'status' => 'available',
      'description' => 'Shaded pine plot',
      'layout_coordinates' => ['x' => 180, 'y' => 150, 'width' => 80, 'height' => 50, 'zone' => 'Pine Grove'],
    ]);

    $this->tent = Facility::create([
      'name' => 'Dome Tent 4P',
      'category' => 'tent',
      'stock' => 5,
      'rental_price' => 85000,
      'unit' => 'set',
    ]);

    $this->lantern = Facility::create([
      'name' => 'LED Lantern',
      'category' => 'lamp',
      'stock' => 10,
      'rental_price' => 15000,
      'unit' => 'unit',
    ]);
  }

  public function test_calculates_multi_night_stay_and_addons_accurately_with_snapshots(): void
  {
    $reservation = $this->bookingService->createReservation([
      'user_id' => $this->visitor->id,
      'camping_area_ids' => [$this->areaA->id],
      'check_in_date' => '2026-12-10',
      'check_out_date' => '2026-12-13',
      'guest_count' => 4,
      'items' => [
        ['facility_id' => $this->tent->id, 'qty' => 2],
        ['facility_id' => $this->lantern->id, 'qty' => 3],
      ],
    ]);

    $this->assertEquals(3, $reservation->duration_nights);
    $this->assertEquals('740000.00', $reservation->total_price);
    $this->assertCount(1, $reservation->areas);
    $this->assertEquals(3, $this->tent->fresh()->stock);
    $this->assertEquals(7, $this->lantern->fresh()->stock);
  }

  public function test_allows_multi_plot_booking_for_large_group_and_locks_both_plots(): void
  {
    // Group of 9 guests exceeds single plot capacity (4 or 6), but fits combined (4 + 6 = 10)
    // 2 nights: (175,000 * 2) + (225,000 * 2) + (85,000 * 2 tents) = 350,000 + 450,000 + 170,000 = 970,000
    $reservation = $this->bookingService->createReservation([
      'user_id' => $this->visitor->id,
      'camping_area_ids' => [$this->areaA->id, $this->areaB->id],
      'check_in_date' => '2026-12-10',
      'check_out_date' => '2026-12-12',
      'guest_count' => 9,
      'items' => [
        ['facility_id' => $this->tent->id, 'qty' => 2],
      ],
    ]);

    $this->assertEquals('970000.00', $reservation->total_price);
    $this->assertCount(2, $reservation->areas);

    $availability = $this->bookingService->checkAvailability('2026-12-10', '2026-12-12');
    $this->assertContains($this->areaA->id, $availability['booked_area_ids']);
    $this->assertContains($this->areaB->id, $availability['booked_area_ids']);
  }

  public function test_rejects_booking_when_guest_count_exceeds_combined_plot_capacity(): void
  {
    $this->expectException(BookingException::class);
    $this->expectExceptionMessage('exceeds combined plot capacity');

    $this->bookingService->createReservation([
      'user_id' => $this->visitor->id,
      'camping_area_ids' => [$this->areaA->id, $this->areaB->id],
      'check_in_date' => '2026-12-10',
      'check_out_date' => '2026-12-12',
      'guest_count' => 11, // max combined is 10
    ]);
  }

  public function test_rejects_multi_plot_booking_if_one_of_the_plots_is_already_booked(): void
  {
    $this->bookingService->createReservation([
      'user_id' => $this->visitor->id,
      'camping_area_ids' => [$this->areaB->id],
      'check_in_date' => '2026-12-10',
      'check_out_date' => '2026-12-12',
      'guest_count' => 4,
    ]);

    $this->expectException(BookingException::class);
    $this->expectExceptionMessage('already reserved');

    $this->bookingService->createReservation([
      'user_id' => $this->visitor->id,
      'camping_area_ids' => [$this->areaA->id, $this->areaB->id],
      'check_in_date' => '2026-12-11',
      'check_out_date' => '2026-12-13',
      'guest_count' => 8,
    ]);
  }

  public function test_allows_back_to_back_checkout_and_checkin_on_same_day(): void
  {
    $this->bookingService->createReservation([
      'user_id' => $this->visitor->id,
      'camping_area_ids' => [$this->areaA->id],
      'check_in_date' => '2026-12-10',
      'check_out_date' => '2026-12-12',
      'guest_count' => 2,
    ]);

    $nextBooking = $this->bookingService->createReservation([
      'user_id' => $this->visitor->id,
      'camping_area_ids' => [$this->areaA->id],
      'check_in_date' => '2026-12-12',
      'check_out_date' => '2026-12-14',
      'guest_count' => 2,
    ]);

    $this->assertInstanceOf(Reservation::class, $nextBooking);
  }

  public function test_rejects_booking_when_daily_quota_is_full(): void
  {
    Config::set('camping.max_daily_reservations', 1);

    $this->bookingService->createReservation([
      'user_id' => $this->visitor->id,
      'camping_area_ids' => [$this->areaA->id],
      'check_in_date' => '2026-12-15',
      'check_out_date' => '2026-12-16',
      'guest_count' => 2,
    ]);

    $this->expectException(BookingException::class);
    $this->expectExceptionMessage('quota is full');

    $this->bookingService->createReservation([
      'user_id' => $this->visitor->id,
      'camping_area_ids' => [$this->areaB->id],
      'check_in_date' => '2026-12-15',
      'check_out_date' => '2026-12-16',
      'guest_count' => 2,
    ]);
  }
}
