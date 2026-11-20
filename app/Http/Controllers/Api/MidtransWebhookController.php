<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class MidtransWebhookController extends Controller
{
  public function __construct(protected PaymentService $paymentService) {}

  public function handle(Request $request): JsonResponse
  {
    try {
      $result = $this->paymentService->handleNotification($request->all());

      return response()->json([
        'success' => true,
        'message' => 'Notification processed successfully.',
        'data' => [
          'booking_code' => $result['reservation']->booking_code,
          'payment_status' => $result['reservation']->payment_status,
          'ticket_issued' => $result['ticket_issued'],
        ],
      ]);
    } catch (BookingException $e) {
      $status = str_contains($e->getMessage(), 'signature') ? 403 : 422;

      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null,
      ], $status);
    } catch (Throwable $e) {
      Log::error('Midtrans webhook error', ['error' => $e->getMessage()]);

      return response()->json([
        'success' => false,
        'message' => 'Internal server error processing payment notification.',
        'data' => null,
      ], 500);
    }
  }
}
