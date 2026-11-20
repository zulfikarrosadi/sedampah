<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationArea extends Model
{
  use HasFactory;

  protected $fillable = [
    'reservation_id',
    'camping_area_id',
    'area_price_snapshot',
    'subtotal',
  ];

  protected function casts(): array
  {
    return [
      'area_price_snapshot' => 'decimal:2',
      'subtotal' => 'decimal:2',
    ];
  }

  public function reservation(): BelongsTo
  {
    return $this->belongsTo(Reservation::class);
  }

  public function campingArea(): BelongsTo
  {
    return $this->belongsTo(CampingArea::class);
  }
}
