<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('reservations', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
      $table->foreignId('camping_area_id')->constrained('camping_areas')->restrictOnDelete();
      $table->string('booking_code', 30)->unique(); // Contoh: BE-20261115-XXXX
      $table->date('check_in_date');
      $table->date('check_out_date');
      $table->integer('duration_nights')->default(1);
      $table->integer('guest_count')->default(1);
      $table->decimal('area_price_snapshot', 12, 2);
      $table->decimal('total_price', 12, 2);
      $table->enum('payment_status', ['pending', 'settled', 'expired', 'cancelled'])->default('pending');
      $table->string('snap_token')->nullable();
      $table->timestamp('paid_at')->nullable();
      $table->timestamps();

      $table->index(['check_in_date', 'payment_status']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('reservations');
  }
};
