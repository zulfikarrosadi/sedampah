<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'category',
    'stock',
    'rental_price',
    'unit',
  ];

  protected function casts(): array
  {
    return [
      'stock' => 'integer',
      'rental_price' => 'decimal:2',
    ];
  }

  public function reservationItems(): HasMany
  {
    return $this->hasMany(ReservationItem::class);
  }
}
