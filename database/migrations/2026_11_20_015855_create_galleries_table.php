<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('galleries', function (Blueprint $table) {
      $table->id();
      $table->string('title');
      $table->enum('category', ['hero', 'camping_area', 'ambiance', 'facilities']);
      $table->string('image_url');
      $table->string('thumbnail_url');
      $table->string('photographer_name');
      $table->enum('source_provider', ['pexels', 'pixabay', 'unsplash'])->default('unsplash');
      $table->string('external_id', 100)->nullable();
      $table->boolean('is_featured')->default(false);
      $table->timestamps();

      $table->index(['category', 'is_featured']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('galleries');
  }
};
