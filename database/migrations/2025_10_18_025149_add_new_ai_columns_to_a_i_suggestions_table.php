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
        Schema::table('a_i_suggestions', function (Blueprint $table) {
            $table->string('groom_edited_image')->nullable()->after('groom_image');
            $table->string('bride_edited_image')->nullable()->after('bride_image');
            $table->string('season_theme_image')->nullable()->after('groom_edited_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('a_i_suggestions', function (Blueprint $table) {
            $table->dropColumn(['groom_edited_image', 'season_theme_image', 'bride_edited_image']);
        });
    }
};
