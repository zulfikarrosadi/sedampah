<?php

namespace Tests\Feature;

use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\Ticket;
use App\Models\User;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
  use RefreshDatabase;

  private string $serverKey = 'SB-Mid-server-TESTKEY12345';

  protected function setUp(): void
  {
    parent::setUp();
    Config::set('midtrans.server_key', $this->serverKey);
    Config::set('midtrans.snap_base_url', 'https://app.sandbox.midtrans.com/snap/v1');
  }

  private function createSampleReservation()
  {
    $visitor = User::create([
      'name' => 'Raka Pratama',
      'email' => 'raka@example.com',
      'password' => bcrypt('password'),
      'phone' => '081234567890',
      'role' => 'pengunjung',
    ]);

    $area = CampingArea::create([
      'name' => 'Sunrise Ridge A1',
      'capacity' => 4,
      'price_per_night' => 175000,
      'status' => 'available',
      'description' => 'Cliffside plot',
      'layout_coordinates' => ['x' => 100, 'y' => 80, 'width' => 60, 'height' => 40, 'zone' => 'Sunrise Ridge'],
    ]);

    $tent = Facility::create([
      'name' => 'Dome Tent 4P',
      'category' => 'tent',
      'stock' => 5,
      'rental_price' => 85000,
      'unit' => 'set',
    ]);

    return app(BookingService::class)->createReservation([
      'user_id' => $visitor->id,
      'camping_area_id' => $area->id,
      'check_in_date' => '2026-12-24',
      'check_out_date' => '2026-12-26',
      'guest_count' => 2,
      'items' => [
        ['facility_id' => $tent->id, 'qty' => 1],
      ],
    ]);
  }

  public function test_creates_snap_token_from_midtrans_api(): void
  {
    Http::fake([
      'https://app.sandbox.midtrans.com/snap/v1/transactions' => Http::response([
        'token' => 'snap-token-abc-123',
        'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/snap-token-abc-123',
      ], 201),
    ]);

    $reservation = $this->createSampleReservation();
    $token = app(PaymentService::class)->createSnapToken($reservation);

    $this->assertEquals('snap-token-abc-123', $token);
    $this->assertEquals('snap-token-abc-123', $reservation->fresh()->snap_token);
  }

  public function test_webhook_settlement_updates_reservation_and_generates_ticket(): void
  {
    $reservation = $this->createSampleReservation();
    $grossAmount = number_format((float) $reservation->total_price, 2, '.', '');
    $statusCode = '200';

    $signature = hash('sha512', $reservation->booking_code . $statusCode . $grossAmount . $this->serverKey);

    $response = $this->postJson('/api/midtrans/notification', [
      'order_id' => $reservation->booking_code,
      'status_code' => $statusCode,
      'gross_amount' => $grossAmount,
      'transaction_status' => 'settlement',
      'fraud_status' => 'accept',
      'signature_key' => $signature,
    ]);

    $response->assertOk()
      ->assertJson([
        'success' => true,
        'data' => [
          'booking_code' => $reservation->booking_code,
          'payment_status' => 'settled',
          'ticket_issued' => true,
        ],
      ]);

    $reservation->refresh();
    $this->assertEquals('settled', $reservation->payment_status);
    $this->assertNotNull($reservation->paid_at);

    $ticket = Ticket::where('reservation_id', $reservation->id)->first();
    $this->assertNotNull($ticket);
    $this->assertEquals(64, strlen($ticket->qr_code_hash));
    $this->assertFalse($ticket->is_checked_in);
  }

  public function test_webhook_rejects_forged_signature(): void
  {
    $reservation = $this->createSampleReservation();

    $response = $this->postJson('/api/midtrans/notification', [
      'order_id' => $reservation->booking_code,
      'status_code' => '200',
      'gross_amount' => '435000.00',
      'transaction_status' => 'settlement',
      'signature_key' => 'invalid-forged-signature',
    ]);

    $response->assertStatus(403)
      ->assertJson([
        'success' => false,
      ]);

    $this->assertEquals('pending', $reservation->fresh()->payment_status);
    $this->assertDatabaseCount('tickets', 0);
  }

  public function test_webhook_expire_releases_facility_stock(): void
  {
    $reservation = $this->createSampleReservation();
    $facility = $reservation->items->first()->facility;
    $this->assertEquals(4, $facility->fresh()->stock);

    $grossAmount = number_format((float) $reservation->total_price, 2, '.', '');
    $statusCode = '202';
    $signature = hash('sha512', $reservation->booking_code . $statusCode . $grossAmount . $this->serverKey);

    $this->postJson('/api/midtrans/notification', [
      'order_id' => $reservation->booking_code,
      'status_code' => $statusCode,
      'gross_amount' => $grossAmount,
      'transaction_status' => 'expire',
      'signature_key' => $signature,
    ])->assertOk();

    $this->assertEquals('expired', $reservation->fresh()->payment_status);
    $this->assertEquals(5, $facility->fresh()->stock);
    $this->assertDatabaseCount('tickets', 0);
  }
}
