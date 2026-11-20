<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CheckInController extends Controller
{
  public function index(): View
  {
    $recentCheckIns = Ticket::with(['reservation.user', 'reservation.campingArea'])
      ->where('is_checked_in', true)
      ->latest('checked_in_at')
      ->take(10)
      ->get();

    return view('petugas.scanner', compact('recentCheckIns'));
  }

  /**
   * API Endpoint: Validasi QR Code Hash atau Kode Booking saat pengunjung tiba di gerbang.
   */
  public function verify(Request $request): JsonResponse
  {
    $request->validate([
      'token' => ['required', 'string'],
    ]);

    $token = trim($request->token);

    return DB::transaction(function () use ($token) {
      // Cari berdasarkan qr_code_hash atau booking_code (fallback jika sinyal kamera bermasalah)
      $ticket = Ticket::with(['reservation.user', 'reservation.campingArea', 'reservation.items.facility'])
        ->where('qr_code_hash', $token)
        ->orWhereHas('reservation', function ($q) use ($token) {
          $q->where('booking_code', $token);
        })
        ->lockForUpdate()
        ->first();

      if (!$ticket) {
        return response()->json([
          'success' => false,
          'message' => 'Tiket tidak ditemukan atau kode QR tidak valid.',
          'data' => null,
        ], 404);
      }

      if ($ticket->reservation->payment_status !== 'settled') {
        return response()->json([
          'success' => false,
          'message' => 'Pembayaran untuk reservasi ini belum lunas.',
          'data' => null,
        ], 422);
      }

      if ($ticket->is_checked_in) {
        return response()->json([
          'success' => false,
          'message' => 'Tiket sudah digunakan check-in pada ' . $ticket->checked_in_at->format('d M Y H:i'),
          'data' => [
            'booking_code' => $ticket->reservation->booking_code,
            'visitor_name' => $ticket->reservation->user->name,
          ],
        ], 409);
      }

      $ticket->update([
        'is_checked_in' => true,
        'checked_in_at' => now(),
        'checked_in_by' => Auth::id(),
      ]);

      return response()->json([
        'success' => true,
        'message' => 'Verifikasi Berhasil! Pengunjung diizinkan masuk.',
        'data' => [
          'booking_code' => $ticket->reservation->booking_code,
          'visitor_name' => $ticket->reservation->user->name,
          'area' => $ticket->reservation->campingArea->name,
          'check_in_date' => $ticket->reservation->check_in_date->format('d M Y'),
          'check_out_date' => $ticket->reservation->check_out_date->format('d M Y'),
          'guest_count' => $ticket->reservation->guest_count,
          'items' => $ticket->reservation->items->map(fn($item) => [
            'name' => $item->facility->name,
            'qty' => $item->qty,
            'unit' => $item->facility->unit,
          ]),
        ],
      ]);
    });
  }
}
