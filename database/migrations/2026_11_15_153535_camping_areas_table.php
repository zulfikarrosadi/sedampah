<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('camping_areas', function (Blueprint $table) {
      $table->id();
      $table->string('name'); // Contoh: Blok A - View Sunrise
      $table->integer('capacity'); // Kapasitas maksimal tenda/slot per hari
      $table->decimal('price_per_night', 12, 2);
      $table->enum('status', ['available', 'maintenance'])->default('available');
      $table->text('description')->nullable();
      $table->json('layout_coordinates')->nullable(); // Koordinat peta interaktif {x, y}
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('camping_areas');
  }
};
