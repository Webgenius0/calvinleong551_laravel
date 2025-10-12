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
        Schema::create('generated_visuals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_session_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['bridal', 'bridesmaids', 'groomsmen', 'venue', 'group']);
            $table->string('image_url');
            $table->json('color_codes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_visuals');
    }
};
