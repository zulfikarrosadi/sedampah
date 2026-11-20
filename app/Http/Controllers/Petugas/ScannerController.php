<?php

namespace App\Http\Controllers\Petugas;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScannerController extends Controller
{
  public function index(): View
  {
    $recentCheckIns = Ticket::with(['reservation.user', 'reservation.campingArea'])
      ->where('is_checked_in', true)
      ->latest('checked_in_at')
      ->take(10)
      ->get();

    return view('petugas.scan', compact('recentCheckIns'));
  }

  public function verify(Request $request): JsonResponse
  {
    $validated = $request->validate([
      'qr_code_hash' => ['required', 'string', 'size:64'],
    ]);

    return DB::transaction(function () use ($validated, $request) {
      $ticket = Ticket::where('qr_code_hash', $validated['qr_code_hash'])
        ->with(['reservation.user', 'reservation.campingArea', 'reservation.items.facility'])
        ->lockForUpdate()
        ->first();

      if (!$ticket) {
        return response()->json([
          'success' => false,
          'message' => 'Invalid QR Code: E-Ticket not found in Bukit Embun records.',
          'data' => null,
        ], 404);
      }

      $reservation = $ticket->reservation;

      if ($reservation->payment_status !== 'settled') {
        return response()->json([
          'success' => false,
          'message' => "Ticket payment status is '{$reservation->payment_status}'. Entry denied.",
          'data' => null,
        ], 422);
      }

      if ($ticket->is_checked_in) {
        return response()->json([
          'success' => false,
          'message' => 'Ticket already used at ' . $ticket->checked_in_at?->format('d M Y H:i') . ' WIB.',
          'data' => [
            'booking_code' => $reservation->booking_code,
            'visitor_name' => $reservation->user->name,
            'checked_in_at' => $ticket->checked_in_at?->toIso8601String(),
          ],
        ], 409);
      }

      $ticket->update([
        'is_checked_in' => true,
        'checked_in_at' => now(),
        'checked_in_by' => $request->user()->id,
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Check-in verified! Welcome to Bukit Embun.',
        'data' => [
          'booking_code' => $reservation->booking_code,
          'visitor_name' => $reservation->user->name,
          'guest_count' => $reservation->guest_count,
          'camping_area' => $reservation->campingArea->name,
          'zone' => $reservation->campingArea->layout_coordinates['zone'] ?? 'Main Zone',
          'check_in_date' => $reservation->check_in_date->format('d M Y'),
          'check_out_date' => $reservation->check_out_date->format('d M Y'),
          'items' => $reservation->items->map(fn($i) => [
            'name' => $i->facility->name,
            'qty' => $i->qty,
            'unit' => $i->facility->unit,
          ])->values()->all(),
        ],
      ]);
    });
  }
}
