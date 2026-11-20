<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
  public function run(): void
  {
    User::updateOrCreate(
      ['email' => 'admin@bukitembun.id'],
      [
        'name' => 'Admin Bukit Embun',
        'password' => Hash::make('password'),
        'phone' => '081200000001',
        'role' => 'admin',
        'email_verified_at' => now(),
      ]
    );

    User::updateOrCreate(
      ['email' => 'petugas@bukitembun.id'],
      [
        'name' => 'Petugas Gerbang',
        'password' => Hash::make('password'),
        'phone' => '081200000002',
        'role' => 'petugas',
        'email_verified_at' => now(),
      ]
    );

    User::updateOrCreate(
      ['email' => 'pengunjung@bukitembun.id'],
      [
        'name' => 'Raka Pratama',
        'password' => Hash::make('password'),
        'phone' => '081200000003',
        'role' => 'pengunjung',
        'email_verified_at' => now(),
      ]
    );
  }
}
