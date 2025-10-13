<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AISuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'bride_image',
        'groom_image',
        'bride_skin_tone',
        'bride_color_code',
        'groom_skin_tone',
        'groom_color_code',
        'season_name',
        'season_palette',
        'season_description',
        'season_image'
    ];

    protected $casts = [
        'season_palette' => 'array',
    ];
}
