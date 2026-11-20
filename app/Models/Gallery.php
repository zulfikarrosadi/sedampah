<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
  use HasFactory;

  protected $fillable = [
    'title',
    'category',
    'image_url',
    'thumbnail_url',
    'photographer_name',
    'source_provider',
    'external_id',
    'is_featured',
  ];

  protected function casts(): array
  {
    return [
      'is_featured' => 'boolean',
    ];
  }
}
