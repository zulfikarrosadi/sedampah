<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('reservation_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
      $table->foreignId('facility_id')->constrained('facilities')->restrictOnDelete();
      $table->integer('qty');
      $table->decimal('price_snapshot', 12, 2);
      $table->decimal('subtotal', 12, 2);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('reservation_items');
  }
};
