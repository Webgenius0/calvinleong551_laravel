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
            $table->string('bride_image');    // bride image path
            $table->string('groom_image');    // groom image path
            $table->string('season_image')->nullable();    // season image path
            $table->string('bride_skin_tone')->nullable();
            $table->string('bride_color_code')->nullable();
            $table->string('groom_skin_tone')->nullable();
            $table->string('groom_color_code')->nullable();
            $table->string('season_name')->nullable();
            $table->json('season_palette')->nullable();  // store as array (json)
            $table->text('season_description')->nullable();
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
