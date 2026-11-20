<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Reservation;
use App\Models\Ticket;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class TicketService
{
  /**
   * Issue a unique e-ticket for a settled reservation (idempotent).
   */
  public function issueTicket(Reservation $reservation): Ticket
  {
    if ($reservation->payment_status !== 'settled') {
      throw new BookingException(
        "Cannot issue ticket for reservation {$reservation->booking_code} with status '{$reservation->payment_status}'."
      );
    }

    if ($reservation->ticket()->exists()) {
      return $reservation->ticket;
    }

    $qrCodeHash = $this->generateSecureHash($reservation);

    return Ticket::create([
      'reservation_id' => $reservation->id,
      'qr_code_hash' => $qrCodeHash,
      'is_checked_in' => false,
    ]);
  }

  /**
   * Generate a non-sequential SHA-256 hash using booking code, microtime, salt, and app key.
   */
  public function generateSecureHash(Reservation $reservation): string
  {
    $secret = (string) config('app.key');
    $entropy = implode('|', [
      $reservation->booking_code,
      now()->format('Uu'),
      Str::random(24),
    ]);

    return hash_hmac('sha256', $entropy, $secret);
  }

  /**
   * Render the e-ticket HTML view (ready for browser print or Barryvdh\DomPDF\Facade\Pdf).
   */
  public function renderTicketHtml(Ticket $ticket): string
  {
    $ticket->loadMissing(['reservation.user', 'reservation.campingArea', 'reservation.items.facility']);

    return View::make('tickets.e-ticket', [
      'ticket' => $ticket,
      'reservation' => $ticket->reservation,
    ])->render();
  }
}
