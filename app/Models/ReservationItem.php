<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationItem extends Model
{
  protected $fillable = [
    'reservation_id',
    'facility_id',
    'qty',
    'price_snapshot',
    'subtotal',
  ];

  protected $casts = [
    'price_snapshot' => 'decimal:2',
    'subtotal' => 'decimal:2',
  ];

  public function reservation(): BelongsTo
  {
    return $this->belongsTo(Reservation::class);
  }

  public function facility(): BelongsTo
  {
    return $this->belongsTo(Facility::class);
  }
}
