<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentService
{
  public function __construct(
    protected BookingService $bookingService,
    protected TicketService $ticketService
  ) {}

  /**
   * Request a Snap transaction token from Midtrans and persist it on the reservation.
   */
  public function createSnapToken(Reservation $reservation): string
  {
    if ($reservation->payment_status !== 'pending') {
      throw new BookingException("Reservation {$reservation->booking_code} is not in pending state.");
    }

    if (!empty($reservation->snap_token)) {
      return $reservation->snap_token;
    }

    $reservation->loadMissing(['user', 'campingArea', 'items.facility']);

    $itemDetails = [
      [
        'id' => 'AREA-' . $reservation->camping_area_id,
        'price' => (int) round((float) $reservation->area_price_snapshot),
        'quantity' => (int) $reservation->duration_nights,
        'name' => mb_substr($reservation->campingArea->name . ' (' . $reservation->duration_nights . ' malam)', 0, 50),
      ],
    ];

    foreach ($reservation->items as $item) {
      $itemDetails[] = [
        'id' => 'FAC-' . $item->facility_id,
        'price' => (int) round((float) $item->price_snapshot),
        'quantity' => (int) $item->qty,
        'name' => mb_substr($item->facility->name, 0, 50),
      ];
    }

    $payload = [
      'transaction_details' => [
        'order_id' => $reservation->booking_code,
        'gross_amount' => (int) round((float) $reservation->total_price),
      ],
      'customer_details' => [
        'first_name' => $reservation->user->name,
        'email' => $reservation->user->email,
        'phone' => $reservation->user->phone ?? '',
      ],
      'item_details' => $itemDetails,
    ];

    $serverKey = (string) config('midtrans.server_key');
    $response = Http::withBasicAuth($serverKey, '')
      ->acceptJson()
      ->post(config('midtrans.snap_base_url') . '/transactions', $payload);

    if ($response->failed() || empty($response->json('token'))) {
      Log::error('Midtrans Snap token creation failed', [
        'booking_code' => $reservation->booking_code,
        'status' => $response->status(),
        'body' => $response->body(),
      ]);

      throw new BookingException('Failed to initialize Midtrans payment session.');
    }

    $snapToken = (string) $response->json('token');
    $reservation->update(['snap_token' => $snapToken]);

    return $snapToken;
  }

  /**
   * Verify Midtrans SHA-512 signature_key:
   * hash('sha512', order_id + status_code + gross_amount + server_key)
   */
  public function isValidSignature(array $notification): bool
  {
    $orderId = (string) ($notification['order_id'] ?? '');
    $statusCode = (string) ($notification['status_code'] ?? '');
    $grossAmount = (string) ($notification['gross_amount'] ?? '');
    $signatureKey = (string) ($notification['signature_key'] ?? '');
    $serverKey = (string) config('midtrans.server_key');

    if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
      return false;
    }

    $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

    return hash_equals($expectedSignature, $signatureKey);
  }

  /**
   * Process an incoming Midtrans webhook notification atomically.
   *
   * @return array{reservation: Reservation, ticket_issued: bool}
   *
   * @throws Throwable
   */
  public function handleNotification(array $notification): array
  {
    if (!$this->isValidSignature($notification)) {
      throw new BookingException('Invalid Midtrans notification signature.');
    }

    $orderId = (string) $notification['order_id'];
    $transactionStatus = (string) ($notification['transaction_status'] ?? '');
    $fraudStatus = (string) ($notification['fraud_status'] ?? 'accept');

    DB::beginTransaction();

    try {
      $reservation = Reservation::where('booking_code', $orderId)
        ->lockForUpdate()
        ->first();

      if (!$reservation) {
        throw new BookingException("Reservation with booking code {$orderId} not found.");
      }

      $ticketIssued = false;

      $isSettled = ($transactionStatus === 'settlement')
        || ($transactionStatus === 'capture' && $fraudStatus === 'accept');

      if ($isSettled) {
        if ($reservation->payment_status !== 'settled') {
          $reservation->update([
            'payment_status' => 'settled',
            'paid_at' => now(),
          ]);
        }

        $this->ticketService->issueTicket($reservation);
        $ticketIssued = true;
      } elseif ($transactionStatus === 'expire') {
        if ($reservation->payment_status === 'pending') {
          $this->bookingService->cancelOrExpireReservation($reservation, 'expired');
        }
      } elseif (in_array($transactionStatus, ['cancel', 'deny'], true)) {
        if ($reservation->payment_status === 'pending') {
          $this->bookingService->cancelOrExpireReservation($reservation, 'cancelled');
        }
      }

      DB::commit();

      return [
        'reservation' => $reservation->fresh(['ticket', 'campingArea', 'items.facility']),
        'ticket_issued' => $ticketIssued,
      ];
    } catch (Throwable $e) {
      DB::rollBack();
      throw $e;
    }
  }
}
