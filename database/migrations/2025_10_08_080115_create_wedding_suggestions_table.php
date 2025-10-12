<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wedding_suggestions', function (Blueprint $table) {
            $table->id();
            $table->string('bride_photo_path');
            $table->string('groom_photo_path');
            $table->string('season');
            $table->json('skin_tone_analysis')->nullable();
            $table->json('wedding_palette')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wedding_suggestions');
    }
};
