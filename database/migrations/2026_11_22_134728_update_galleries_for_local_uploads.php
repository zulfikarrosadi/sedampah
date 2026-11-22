<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    if (DB::getDriverName() === 'mysql') {
      DB::statement("ALTER TABLE galleries MODIFY COLUMN source_provider ENUM('pexels', 'pixabay', 'unsplash', 'local') NOT NULL DEFAULT 'local'");
    }

    Schema::table('galleries', function (Blueprint $table) {
      $table->string('photographer_name')->default('Bukit Embun Team')->change();
    });
  }

  public function down(): void
  {
    if (DB::getDriverName() === 'mysql') {
      DB::statement("ALTER TABLE galleries MODIFY COLUMN source_provider ENUM('pexels', 'pixabay', 'unsplash') NOT NULL DEFAULT 'unsplash'");
    }
  }
};
