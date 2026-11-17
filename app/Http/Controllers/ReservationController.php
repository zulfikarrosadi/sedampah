<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\StoreReservationRequest;
use App\Models\CampingArea;
use App\Models\Facility;
use App\Services\BookingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
  public function __construct(
    protected BookingService $bookingService
  ) {}

  /**
   * API Endpoint: Cek kuota area & stok peralatan secara real-time berdasarkan tanggal.
   */
  public function checkAvailability(Request $request): JsonResponse
  {
    $request->validate([
      'check_in_date' => ['required', 'date', 'after_or_equal:today'],
      'check_out_date' => ['required', 'date', 'after:check_in_date'],
    ]);

    $checkIn = $request->check_in_date;
    $checkOut = $request->check_out_date;

    $areas = CampingArea::all()->map(function ($area) use ($checkIn, $checkOut) {
      return [
        'id' => $area->id,
        'name' => $area->name,
        'code' => $area->code,
        'price_per_night' => $area->price_per_night,
        'description' => $area->description,
        'layout_coordinates' => $area->layout_coordinates,
        'remaining_quota' => $this->bookingService->getRemainingAreaQuota($area->id, $checkIn, $checkOut),
      ];
    });

    $facilities = Facility::all()->map(function ($facility) use ($checkIn, $checkOut) {
      return [
        'id' => $facility->id,
        'name' => $facility->name,
        'category' => $facility->category,
        'rental_price' => $facility->rental_price,
        'unit' => $facility->unit,
        'remaining_stock' => $this->bookingService->getRemainingFacilityStock($facility->id, $checkIn, $checkOut),
      ];
    });

    return response()->json([
      'success' => true,
      'message' => 'Data ketersediaan berhasil dimuat.',
      'data' => [
        'summary' => $this->bookingService->getDailySummaryByDate($checkIn),
        'areas' => $areas,
        'facilities' => $facilities,
      ],
    ]);
  }

  /**
   * Menyimpan reservasi baru sebelum diarahkan ke Midtrans Payment Gateway.
   */
  public function store(StoreReservationRequest $request): JsonResponse
  {
    try {
      $reservation = $this->bookingService->createReservation(
        Auth::id(),
        $request->validated()
      );

      return response()->json([
        'success' => true,
        'message' => 'Reservasi berhasil dibuat. Silakan lanjutkan pembayaran.',
        'data' => $reservation,
      ], 201);
    } catch (Exception $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null,
      ], 422);
    }
  }
}
