<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
  public function run(): void
  {
    $facilities = [
      [
        'name' => 'Dome Tent (4-Person Waterproof)',
        'category' => 'tent',
        'stock' => 15,
        'rental_price' => 85000,
        'unit' => 'set',
      ],
      [
        'name' => 'Family Cabin Tent (6-Person)',
        'category' => 'tent',
        'stock' => 8,
        'rental_price' => 140000,
        'unit' => 'set',
      ],
      [
        'name' => 'Polar Sleeping Bag (-5°C Rating)',
        'category' => 'equipment',
        'stock' => 30,
        'rental_price' => 25000,
        'unit' => 'unit',
      ],
      [
        'name' => 'Self-Inflating Foam Mattress',
        'category' => 'equipment',
        'stock' => 25,
        'rental_price' => 20000,
        'unit' => 'unit',
      ],
      [
        'name' => 'Warm-Glow LED Camp Lantern',
        'category' => 'lamp',
        'stock' => 20,
        'rental_price' => 15000,
        'unit' => 'unit',
      ],
      [
        'name' => 'Portable Nesting Stove & Gas Canister',
        'category' => 'equipment',
        'stock' => 12,
        'rental_price' => 35000,
        'unit' => 'set',
      ],
      [
        'name' => 'Hardwood Firewood Bundle',
        'category' => 'equipment',
        'stock' => 40,
        'rental_price' => 30000,
        'unit' => 'bundle',
      ],
    ];

    foreach ($facilities as $facility) {
      Facility::updateOrCreate(['name' => $facility['name']], $facility);
    }
  }
}
