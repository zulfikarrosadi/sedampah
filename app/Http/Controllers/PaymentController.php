<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
  public function __construct(
    protected PaymentService $paymentService
  ) {}

  /**
   * Endpoint Frontend: Mendapatkan Snap Token untuk reservasi milik user yang sedang login.
   */
  public function createSnapToken(Reservation $reservation): JsonResponse
  {
    if ($reservation->user_id !== Auth::id()) {
      return response()->json([
        'success' => false,
        'message' => 'Akses ditolak.',
        'data' => null,
      ], 403);
    }

    try {
      $snapToken = $this->paymentService->getOrCreateSnapToken($reservation);

      return response()->json([
        'success' => true,
        'message' => 'Snap token berhasil dibuat.',
        'data' => [
          'snap_token' => $snapToken,
          'booking_code' => $reservation->booking_code,
        ],
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null,
      ], 422);
    }
  }

  /**
   * Endpoint Webhook Midtrans (POST /api/midtrans/notification).
   */
  public function webhook(Request $request): JsonResponse
  {
    try {
      $reservation = $this->paymentService->handleNotification($request->all());

      return response()->json([
        'success' => true,
        'message' => 'Notifikasi berhasil diproses.',
        'data' => [
          'booking_code' => $reservation->booking_code,
          'payment_status' => $reservation->payment_status,
        ],
      ]);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null,
      ], 400);
    }
  }
}
