<?php

namespace App\Http\Controllers;

use App\Models\CampingArea;
use App\Models\Facility;
use App\Models\Reservation;
use App\Services\TicketService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VisitorPortalController extends Controller
{
  public function __construct(
    protected TicketService $ticketService
  ) {}

  public function index(): View
  {
    $areas = CampingArea::where('status', 'available')->get();
    $facilities = Facility::all();

    return view('visitor.booking', compact('areas', 'facilities'));
  }

  public function history(): View
  {
    $reservations = Reservation::with(['campingArea', 'items.facility', 'ticket'])
      ->where('user_id', Auth::id())
      ->latest()
      ->get();

    return view('visitor.history', compact('reservations'));
  }

  public function showTicket(Reservation $reservation): View
  {
    if ($reservation->user_id !== Auth::id() && !Auth::user()->isAdmin()) {
      abort(403);
    }

    if ($reservation->payment_status !== 'settled' || !$reservation->ticket) {
      abort(404, 'E-Tiket belum tersedia. Pastikan pembayaran telah selesai.');
    }

    $reservation->load(['user', 'campingArea', 'items.facility', 'ticket']);
    $qrSvg = $this->ticketService->generateQrSvg($reservation->ticket, 220);

    return view('visitor.ticket', compact('reservation', 'qrSvg'));
  }
}
