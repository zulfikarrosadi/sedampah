<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
  protected $fillable = [
    'user_id',
    'camping_area_id',
    'booking_code',
    'check_in_date',
    'check_out_date',
    'duration_nights',
    'guest_count',
    'area_price_snapshot',
    'total_price',
    'payment_status',
    'snap_token',
    'paid_at',
  ];

  protected $casts = [
    'check_in_date' => 'date',
    'check_out_date' => 'date',
    'area_price_snapshot' => 'decimal:2',
    'total_price' => 'decimal:2',
    'paid_at' => 'datetime',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function campingArea(): BelongsTo
  {
    return $this->belongsTo(CampingArea::class);
  }

  public function items(): HasMany
  {
    return $this->hasMany(ReservationItem::class);
  }

  public function ticket(): HasOne
  {
    return $this->hasOne(Ticket::class);
  }
}
