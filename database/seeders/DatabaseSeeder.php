<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\CampingArea;
use App\Models\Facility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
  public function run(): void
  {
    // 1. Seed Users (Admin, Petugas, Pengunjung Demo)
    User::create([
      'name' => 'Ari Setya Kusuma (Admin)',
      'email' => 'admin@bukitembun.id',
      'phone' => '081229699875',
      'password' => Hash::make('password123'),
      'role' => 'admin',
    ]);

    User::create([
      'name' => 'Petugas Gerbang 1',
      'email' => 'petugas@bukitembun.id',
      'phone' => '081200000001',
      'password' => Hash::make('password123'),
      'role' => 'petugas',
    ]);

    User::create([
      'name' => 'Rijal Hakim',
      'email' => 'pengunjung@bukitembun.id',
      'phone' => '081200000002',
      'password' => Hash::make('password123'),
      'role' => 'pengunjung',
    ]);

    // 2. Seed Camping Areas (Tapak)
    $areas = [
      [
        'name' => 'Zona A - Sunrise Cliff',
        'code' => 'ZN-A',
        'capacity' => 15,
        'price_per_night' => 50000,
        'status' => 'available',
        'description' => 'Area tepi tebing dengan pemandangan langsung matahari terbit dan lautan awan.',
        'layout_coordinates' => ['x' => 120, 'y' => 80],
      ],
      [
        'name' => 'Zona B - Pine Forest',
        'code' => 'ZN-B',
        'capacity' => 20,
        'price_per_night' => 35000,
        'status' => 'available',
        'description' => 'Area rindang di bawah pepohonan pinus, dekat dengan fasilitas toilet dan mushola.',
        'layout_coordinates' => ['x' => 240, 'y' => 150],
      ],
      [
        'name' => 'Zona C - Family Deck',
        'code' => 'ZN-C',
        'capacity' => 10,
        'price_per_night' => 75000,
        'status' => 'available',
        'description' => 'Area dek kayu datar yang cocok untuk rombongan keluarga.',
        'layout_coordinates' => ['x' => 310, 'y' => 210],
      ],
    ];

    foreach ($areas as $area) {
      CampingArea::create($area);
    }

    // 3. Seed Facilities (Add-on Peralatan)
    $facilities = [
      ['name' => 'Tenda Dome Kapasitas 2 Orang', 'category' => 'tenda', 'stock' => 15, 'rental_price' => 60000, 'unit' => 'unit'],
      ['name' => 'Tenda Dome Kapasitas 4 Orang', 'category' => 'tenda', 'stock' => 20, 'rental_price' => 90000, 'unit' => 'unit'],
      ['name' => 'Tenda Dome Kapasitas 6 Orang', 'category' => 'tenda', 'stock' => 10, 'rental_price' => 130000, 'unit' => 'unit'],
      ['name' => 'Matras Spons', 'category' => 'perlengkapan_tidur', 'stock' => 50, 'rental_price' => 10000, 'unit' => 'pcs'],
      ['name' => 'Sleeping Bag Polar', 'category' => 'perlengkapan_tidur', 'stock' => 30, 'rental_price' => 20000, 'unit' => 'pcs'],
      ['name' => 'Kursi Lipat Outdoor', 'category' => 'lainnya', 'stock' => 40, 'rental_price' => 15000, 'unit' => 'pcs'],
      ['name' => 'Lampu Tenda LED', 'category' => 'penerangan', 'stock' => 35, 'rental_price' => 15000, 'unit' => 'unit'],
      ['name' => 'Kompor Portable + Gas Kaleng', 'category' => 'alat_masak', 'stock' => 20, 'rental_price' => 35000, 'unit' => 'set'],
      ['name' => 'Nesting / Alat Masak Set', 'category' => 'alat_masak', 'stock' => 20, 'rental_price' => 25000, 'unit' => 'set'],
    ];

    foreach ($facilities as $facility) {
      Facility::create($facility);
    }
  }
}
