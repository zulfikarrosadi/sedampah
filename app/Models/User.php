<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
  use HasFactory, Notifiable;

  protected $fillable = [
    'name',
    'email',
    'password',
    'phone',
    'role',
  ];

  protected $hidden = [
    'password',
    'remember_token',
  ];

  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
    ];
  }

  public function reservations(): HasMany
  {
    return $this->hasMany(Reservation::class);
  }

  public function checkedInTickets(): HasMany
  {
    return $this->hasMany(Ticket::class, 'checked_in_by');
  }

  public function isAdmin(): bool
  {
    return $this->role === 'admin';
  }

  public function isPetugas(): bool
  {
    return $this->role === 'petugas';
  }
}
