<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    protected $fillable = [
        'user_id',
        'color_theme_id',
    ];

    public function colorTheme()
    {
        return $this->belongsTo(ColorTheme::class, 'color_theme_id');
    }
}
