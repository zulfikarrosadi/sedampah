<?php

namespace App\Http\Controllers\Visitor;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\Gallery;
use App\Models\Reservation;
use App\Models\Ticket;
use App\Services\BookingService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class CampingController extends Controller
{
  public function __construct(
    protected BookingService $bookingService,
    protected PaymentService $paymentService
  ) {}

  public function index(): View
  {
    $heroBanner = (object) [
      'category' => 'camping_area',
      'title' => 'Punggung Bukit Timur',
      'photographer_name' => 'Tim Bukit Embun',
      'image_url' => asset('images/bukit-embun/bg-hero.webp'),
    ];
    $galleriesByCategory = Gallery::orderByDesc('is_featured')
      ->latest()
      ->get()
      ->groupBy('category');

    $featuredAreas = CampingArea::where('status', 'available')->take(3)->get();
    $facilities = Facility::all();

    return view('visitor.landing', compact(
      'heroBanner',
      'galleriesByCategory',
      'featuredAreas',
      'facilities'
    ));
  }

  public function bookingPage(Request $request): View
  {
    $checkIn = $request->input('check_in', now()->addDay()->toDateString());
    $checkOut = $request->input('check_out', now()->addDays(2)->toDateString());
    $guestCount = max(1, (int)$request->input('guests', 2));

    try {
      $availability = $this->bookingService->checkAvailability($checkIn, $checkOut);
    } catch (BookingException) {
      $checkIn = now()->addDay()->toDateString();
      $checkOut = now()->addDays(2)->toDateString();
      $availability = $this->bookingService->checkAvailability($checkIn, $checkOut);
    }

    $facilities = Facility::orderBy('category')->orderBy('name')->get();

    return view('visitor.booking', [
      'checkIn' => $checkIn,
      'checkOut' => $checkOut,
      'guestCount' => $guestCount,
      'availability' => $availability,
      'facilities' => $facilities,
      'snapJsUrl' => config('midtrans.snap_js_url'),
      'midtransClientKey' => config('midtrans.client_key'),
    ]);
  }

  public function checkAvailabilityApi(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'check_in_date' => ['required', 'date'],
      'check_out_date' => ['required', 'date', 'after:check_in_date'],
    ]);

    try {
      $data = $this->bookingService->checkAvailability(
        $validated['check_in_date'],
        $validated['check_out_date']
      );

      return response()->json([
        'success' => true,
        'message' => 'Availability retrieved.',
        'data' => $data,
      ]);
    } catch (BookingException $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null,
      ], 422);
    }
  }

  public function checkout(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'camping_area_ids' => ['required', 'array', 'min:1'],
      'camping_area_ids.*' => ['required', 'integer', 'distinct', 'exists:camping_areas,id'],
      'check_in_date' => ['required', 'date', 'after_or_equal:today'],
      'check_out_date' => ['required', 'date', 'after:check_in_date'],
      'guest_count' => ['required', 'integer', 'min:1', 'max:100'],
      'items' => ['nullable', 'array'],
      'items.*.facility_id' => ['required_with:items', 'integer', 'exists:facilities,id'],
      'items.*.qty' => ['required_with:items', 'integer', 'min:1', 'max:50'],
    ]);

    try {
      $reservation = $this->bookingService->createReservation([
        'user_id' => $request->user()->id,
        'camping_area_ids' => $validated['camping_area_ids'],
        'check_in_date' => $validated['check_in_date'],
        'check_out_date' => $validated['check_out_date'],
        'guest_count' => $validated['guest_count'],
        'items' => $validated['items'] ?? [],
      ]);

      $snapToken = $this->paymentService->createSnapToken($reservation);

      return response()->json([
        'success' => true,
        'message' => 'Reservation created. Proceed to payment.',
        'data' => [
          'reservation_id' => $reservation->id,
          'booking_code' => $reservation->booking_code,
          'total_price' => (float) $reservation->total_price,
          'snap_token' => $snapToken,
        ],
      ]);
    } catch (BookingException $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null,
      ], 422);
    } catch (Throwable $e) {
      \Illuminate\Support\Facades\Log::error('Checkout failed', [
        'message' => $e->getMessage(),
        'file' => $e->getFile() . ':' . $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ]);
      return response()->json([
        'success' => false,
        'message' => 'Unable to process checkout right now.',
        'data' => null,
      ], 500);
    }
  }

  public function myTickets(Request $request): View
  {
    $reservations = Reservation::with([
      'campingArea',
      'areas.campingArea',
      'items.facility',
      'ticket',
    ])
      ->where('user_id', $request->user()->id)
      ->latest()
      ->paginate(10);

    return view('visitor.my-tickets', compact('reservations'));
  }

  public function showTicket(Request $request, Ticket $ticket): View
  {
    $ticket->load([
      'reservation.user',
      'reservation.campingArea',
      'reservation.areas.campingArea',
      'reservation.items.facility',
    ]);

    $user = $request->user();
    if ($ticket->reservation->user_id !== $user->id && !$user->isAdmin() && !$user->isPetugas()) {
      abort(403);
    }

    return view('tickets.e-ticket', [
      'ticket' => $ticket,
      'reservation' => $ticket->reservation,
    ]);
  }

  public function payReservation(Request $request, Reservation $reservation): View|RedirectResponse
  {
    if ($reservation->user_id !== $request->user()->id) {
      abort(403);
    }

    if ($reservation->payment_status !== 'pending') {
      return redirect()->route('visitor.tickets')
        ->with('status', "Reservation {$reservation->booking_code} is currently '{$reservation->payment_status}'.");
    }

    $expiryMinutes = (int) config('camping.pending_expiry_minutes', 60);
    $expiresAt = $reservation->created_at->copy()->addMinutes($expiryMinutes);

    try {
      $snapToken = $this->paymentService->createSnapToken($reservation);
    } catch (BookingException $e) {
      return redirect()->route('visitor.tickets')
        ->with('error', $e->getMessage());
    }

    $reservation->load(['campingArea', 'areas.campingArea', 'items.facility']);

    return view('visitor.retry-payment', [
      'reservation' => $reservation,
      'snapToken' => $snapToken,
      'expiresAtIso' => $expiresAt->toIso8601String(),
      'snapJsUrl' => config('midtrans.snap_js_url'),
      'midtransClientKey' => config('midtrans.client_key'),
    ]);
  }

  public function retrySnapToken(Request $request, Reservation $reservation): JsonResponse
  {
    if ($reservation->user_id !== $request->user()->id) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthorized access to this reservation.',
        'data' => null,
      ], 403);
    }

    try {
      $snapToken = $this->paymentService->createSnapToken($reservation);

      return response()->json([
        'success' => true,
        'message' => 'Payment session ready.',
        'data' => [
          'booking_code' => $reservation->booking_code,
          'snap_token' => $snapToken,
        ],
      ]);
    } catch (BookingException $e) {
      return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null,
      ], 422);
    } catch (Throwable $e) {
      Log::error('Retry Snap token error', [
        'booking_code' => $reservation->booking_code,
        'error' => $e->getMessage(),
      ]);

      return response()->json([
        'success' => false,
        'message' => 'Unable to resume payment session right now.',
        'data' => null,
      ], 500);
    }
  }

  public function cancelReservation(Request $request, Reservation $reservation): RedirectResponse
  {
    if ($reservation->user_id !== $request->user()->id) {
      abort(403);
    }

    try {
      $this->bookingService->cancelOrExpireReservation($reservation, 'cancelled');

      return redirect()->route('visitor.tickets')
        ->with('status', "Reservation {$reservation->booking_code} has been cancelled and plot availability released.");
    } catch (BookingException $e) {
      return redirect()->route('visitor.tickets')
        ->with('error', $e->getMessage());
    }
  }
}
