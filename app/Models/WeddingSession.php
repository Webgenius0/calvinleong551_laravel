<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WeddingSession extends Model
{
   use HasFactory;

    protected $fillable = [
        'bride_image',
        'groom_image',
        'season',
        'color_palette'
    ];

    protected $casts = [
        'color_palette' => 'array'
    ];

    public function generatedVisuals()
    {
        return $this->hasMany(GeneratedVisual::class);
    }
}
