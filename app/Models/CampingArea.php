<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampingArea extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'capacity',
    'price_per_night',
    'status',
    'description',
    'layout_coordinates',
  ];

  protected function casts(): array
  {
    return [
      'capacity' => 'integer',
      'price_per_night' => 'decimal:2',
      'layout_coordinates' => 'array',
    ];
  }

  public function reservations(): HasMany
  {
    return $this->hasMany(Reservation::class);
  }

  public function reservationAreas(): HasMany
  {
    return $this->hasMany(ReservationArea::class);
  }

  public function bookedReservations(): BelongsToMany
  {
    return $this->belongsToMany(Reservation::class, 'reservation_areas')
      ->withPivot(['area_price_snapshot', 'subtotal'])
      ->withTimestamps();
  }
}
