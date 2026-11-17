<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampingArea extends Model
{
  protected $fillable = [
    'name',
    'code',
    'capacity',
    'price_per_night',
    'status',
    'description',
    'layout_coordinates',
  ];

  protected $casts = [
    'price_per_night' => 'decimal:2',
    'layout_coordinates' => 'array',
  ];

  public function reservations(): HasMany
  {
    return $this->hasMany(Reservation::class);
  }
}
