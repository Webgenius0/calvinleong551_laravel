<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ColorTheme extends Model
{
   use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'color_codes' => 'array',
        'images' => 'array',
    ];

    public function aiSuggestion()
    {
        return $this->belongsTo(AISuggestion::class);
    }
}
