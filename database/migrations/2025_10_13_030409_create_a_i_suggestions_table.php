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
        Schema::create('a_i_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('bride_image')->nullable();    // bride image path
            $table->string('groom_image')->nullable();    // groom image path
            $table->string('season_image')->nullable();    // season image path
            $table->string('bride_skin_tone')->nullable();
            $table->json('bride_color_code')->nullable();
            $table->string('groom_skin_tone')->nullable();
            $table->json('groom_color_code')->nullable();
            $table->string('season_name')->nullable();
            $table->json('season_palette')->nullable();  // store as array (json)
            $table->text('season_description')->nullable();
            $table->json('combined_colors')->nullable();  // store as array (json)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_i_suggestions');
    }
};
