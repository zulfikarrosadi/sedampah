<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    // Make primary camping_area_id and area_price_snapshot nullable on reservations
    // while keeping them populated with the primary/first plot for backward compatibility
    Schema::table('reservations', function (Blueprint $table) {
      $table->foreignId('camping_area_id')->nullable()->change();
      $table->decimal('area_price_snapshot', 12, 2)->nullable()->change();
    });

    Schema::create('reservation_areas', function (Blueprint $table) {
      $table->id();
      $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
      $table->foreignId('camping_area_id')->constrained('camping_areas')->restrictOnDelete();
      $table->decimal('area_price_snapshot', 12, 2);
      $table->decimal('subtotal', 12, 2);
      $table->timestamps();

      $table->unique(['reservation_id', 'camping_area_id'], 'uq_reservation_area');
      $table->index('camping_area_id', 'idx_res_areas_area_id');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('reservation_areas');

    Schema::table('reservations', function (Blueprint $table) {
      $table->foreignId('camping_area_id')->nullable(false)->change();
      $table->decimal('area_price_snapshot', 12, 2)->nullable(false)->change();
    });
  }
};
