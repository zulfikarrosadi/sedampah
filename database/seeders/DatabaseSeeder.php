<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  public function run(): void
  {
    $this->call([
      UserSeeder::class,
      CampingAreaSeeder::class,
      FacilitySeeder::class,
      GallerySeeder::class,
    ]);
  }
}
