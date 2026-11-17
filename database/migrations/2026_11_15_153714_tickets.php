<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('tickets', function (Blueprint $table) {
      $table->id();
      $table->foreignId('reservation_id')->unique()->constrained('reservations')->cascadeOnDelete();
      $table->string('qr_code_hash', 64)->unique(); // SHA-256 Token
      $table->boolean('is_checked_in')->default(false);
      $table->timestamp('checked_in_at')->nullable();
      $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tickets');
  }
};
