<?php

namespace Database\Seeders;

use App\Models\CampingArea;
use Illuminate\Database\Seeder;

class CampingAreaSeeder extends Seeder
{
  public function run(): void
  {
    $areas = [];

    // 1. Sunrise Ridge (A1 - A8): 8 cliffside plots along the upper eastern ridge
    for ($i = 1; $i <= 8; $i++) {
      $col = ($i - 1) % 4;
      $row = intdiv($i - 1, 4);
      $areas[] = [
        'name' => "Sunrise Ridge A{$i}",
        'capacity' => 4,
        'price_per_night' => 175000,
        'status' => 'available',
        'description' => 'Elevated eastern cliffside plot offering unobstructed sunrise views above the morning mist.',
        'layout_coordinates' => [
          'x' => 470 + ($col * 165),
          'y' => 95 + ($row * 85),
          'width' => 135,
          'height' => 64,
          'zone' => 'Sunrise Ridge',
          'elevation' => (1425 - ($row * 4)) . ' mdpl',
        ],
      ];
    }

    // 2. Pine Grove (B1 - B10): 10 shaded woodland plots on the western hillside
    for ($i = 1; $i <= 10; $i++) {
      $col = ($i - 1) % 5;
      $row = intdiv($i - 1, 5);
      $areas[] = [
        'name' => "Pine Grove B{$i}",
        'capacity' => 6,
        'price_per_night' => 225000,
        'status' => 'available',
        'description' => 'Shaded family plot nestled beneath mature Merkusii pines near the campfire amphitheater.',
        'layout_coordinates' => [
          'x' => 70 + ($col * 115),
          'y' => 315 + ($row * 88),
          'width' => 98,
          'height' => 68,
          'zone' => 'Pine Grove',
          'elevation' => (1398 - ($row * 3)) . ' mdpl',
        ],
      ];
    }

    // 3. Mist Valley (C1 - C8): 8 intimate fern-terrace plots on the eastern terrace
    for ($i = 1; $i <= 8; $i++) {
      $col = ($i - 1) % 4;
      $row = intdiv($i - 1, 4);
      $areas[] = [
        'name' => "Mist Valley C{$i}",
        'capacity' => 2,
        'price_per_night' => 125000,
        'status' => 'available',
        'description' => 'Intimate solo/couple deck surrounded by wild mountain ferns and cool morning dew.',
        'layout_coordinates' => [
          'x' => 690 + ($col * 115),
          'y' => 315 + ($row * 88),
          'width' => 96,
          'height' => 66,
          'zone' => 'Mist Valley',
          'elevation' => (1382 - ($row * 2)) . ' mdpl',
        ],
      ];
    }

    // 4. Starlight Plateau (D1 - D6): 6 wide wooden stargazing decks along the southern plateau
    for ($i = 1; $i <= 6; $i++) {
      $col = ($i - 1) % 3;
      $row = intdiv($i - 1, 3);
      $areas[] = [
        'name' => "Starlight Deck D{$i}",
        'capacity' => 8,
        'price_per_night' => 320000,
        'status' => $i === 6 ? 'maintenance' : 'available',
        'description' => 'Premium wooden observation deck with zero canopy obstruction for night-sky stargazing.',
        'layout_coordinates' => [
          'x' => 450 + ($col * 225),
          'y' => 570 + ($row * 95),
          'width' => 195,
          'height' => 74,
          'zone' => 'Starlight Plateau',
          'elevation' => '1,435 mdpl',
        ],
      ];
    }

    foreach ($areas as $area) {
      CampingArea::updateOrCreate(['name' => $area['name']], $area);
    }
  }
}
