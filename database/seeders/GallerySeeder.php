<?php

namespace Database\Seeders;

use App\Models\Gallery;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
  public function run(): void
  {
    $assets = [
      // Hero
      [
        'title' => 'Morning Mist Over Bukit Embun Pine Ridge',
        'category' => 'hero',
        'image_url' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=1920&q=80',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?auto=format&fit=crop&w=640&q=75',
        'photographer_name' => 'Scott Goodwill',
        'source_provider' => 'unsplash',
        'external_id' => 'photo-1504280390367-361c6d9f38f4',
        'is_featured' => true,
      ],
      [
        'title' => 'Golden Hour Canopy Above the Clouds',
        'category' => 'hero',
        'image_url' => 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=1920&q=80',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?auto=format&fit=crop&w=640&q=75',
        'photographer_name' => 'Kevin Ianeselli',
        'source_provider' => 'unsplash',
        'external_id' => 'photo-1478131143081-80f7f84ca84d',
        'is_featured' => true,
      ],

      // Camping Area
      [
        'title' => 'Sunrise Ridge Tent Pitch',
        'category' => 'camping_area',
        'image_url' => 'https://images.unsplash.com/photo-1523987355523-c7b5b0dd90a7?auto=format&fit=crop&w=1400&q=80',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1523987355523-c7b5b0dd90a7?auto=format&fit=crop&w=600&q=75',
        'photographer_name' => 'Pars Sahin',
        'source_provider' => 'unsplash',
        'external_id' => 'photo-1523987355523-c7b5b0dd90a7',
        'is_featured' => true,
      ],
      [
        'title' => 'Pine Grove Shaded Woodland Plots',
        'category' => 'camping_area',
        'image_url' => 'https://images.unsplash.com/photo-1537905569824-f89f14cceb68?auto=format&fit=crop&w=1400&q=80',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1537905569824-f89f14cceb68?auto=format&fit=crop&w=600&q=75',
        'photographer_name' => 'Tommy Lisbin',
        'source_provider' => 'unsplash',
        'external_id' => 'photo-1537905569824-f89f14cceb68',
        'is_featured' => true,
      ],

      // Ambiance
      [
        'title' => 'Evening Campfire & Acoustic Gathering',
        'category' => 'ambiance',
        'image_url' => 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=1400&q=80',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1510312305653-8ed496efae75?auto=format&fit=crop&w=600&q=75',
        'photographer_name' => 'Patrick Hendry',
        'source_provider' => 'unsplash',
        'external_id' => 'photo-1510312305653-8ed496efae75',
        'is_featured' => true,
      ],
      [
        'title' => 'Starlight Plateau Milky Way View',
        'category' => 'ambiance',
        'image_url' => 'https://images.unsplash.com/photo-1445308394109-4ec2920981b1?auto=format&fit=crop&w=1400&q=80',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1445308394109-4ec2920981b1?auto=format&fit=crop&w=600&q=75',
        'photographer_name' => 'Juskteez Vu',
        'source_provider' => 'unsplash',
        'external_id' => 'photo-1445308394109-4ec2920981b1',
        'is_featured' => false,
      ],

      // Facilities
      [
        'title' => 'Warm-Glow Lantern & Weatherproof Dome Gear',
        'category' => 'facilities',
        'image_url' => 'https://images.unsplash.com/photo-1517824806704-9040b037703b?auto=format&fit=crop&w=1400&q=80',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1517824806704-9040b037703b?auto=format&fit=crop&w=600&q=75',
        'photographer_name' => 'Chris Holder',
        'source_provider' => 'unsplash',
        'external_id' => 'photo-1517824806704-9040b037703b',
        'is_featured' => true,
      ],
      [
        'title' => 'Morning Pour-Over Coffee & Camp Kitchen Kit',
        'category' => 'facilities',
        'image_url' => 'https://images.unsplash.com/photo-1496080174650-637e3f22fa03?auto=format&fit=crop&w=1400&q=80',
        'thumbnail_url' => 'https://images.unsplash.com/photo-1496080174650-637e3f22fa03?auto=format&fit=crop&w=600&q=75',
        'photographer_name' => 'Daiga Ellaby',
        'source_provider' => 'unsplash',
        'external_id' => 'photo-1496080174650-637e3f22fa03',
        'is_featured' => false,
      ],
    ];

    foreach ($assets as $asset) {
      Gallery::updateOrCreate(
        ['external_id' => $asset['external_id']],
        $asset
      );
    }
  }
}
