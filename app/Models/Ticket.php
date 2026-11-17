<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
  protected $fillable = [
    'reservation_id',
    'qr_code_hash',
    'is_checked_in',
    'checked_in_at',
    'checked_in_by',
  ];

  protected $casts = [
    'is_checked_in' => 'boolean',
    'checked_in_at' => 'datetime',
  ];

  public function reservation(): BelongsTo
  {
    return $this->belongsTo(Reservation::class);
  }

  public function verifier(): BelongsTo
  {
    return $this->belongsTo(User::class, 'checked_in_by');
  }
}
