<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('facilities', function (Blueprint $table) {
      $table->id();
      $table->string('name'); // Contoh: Tenda Dome Kapasitas 4 Orang
      $table->enum('category', ['tenda', 'perlengkapan_tidur', 'alat_masak', 'penerangan', 'lainnya']);
      $table->integer('stock');
      $table->decimal('rental_price', 12, 2);
      $table->string('unit', 20)->default('unit'); // unit, set, pcs
      $table->text('description')->nullable();
      $table->string('image_path')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('facilities');
  }
};
