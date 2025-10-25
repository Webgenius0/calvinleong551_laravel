<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AISuggestion extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'bride_colors' => 'array',
        'groom_colors' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function colorThemes()
    {
        return $this->hasMany(ColorTheme::class, 'ai_suggestion_id');
    }

    public $timestamps = true;
    
}
