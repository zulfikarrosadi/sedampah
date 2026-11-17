<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
  protected $fillable = [
    'name',
    'category',
    'stock',
    'rental_price',
    'unit',
    'description',
    'image_path',
  ];

  protected $casts = [
    'rental_price' => 'decimal:2',
  ];

  public function reservationItems(): HasMany
  {
    return $this->hasMany(ReservationItem::class);
  }
}
