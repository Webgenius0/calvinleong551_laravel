<?php

namespace App\Models;

use Illuminate\Support\Facades\URL;
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
    

    protected $appends = [
        'bride_image_url',
        'groom_image_url',
        'season_image_url',
        'season_theme_image_url',
        'bride_edited_image_url',
        'groom_edited_image_url',
    ];

    // 👰 Bride image full URL
    public function getBrideImageUrlAttribute()
    {
        return $this->bride_image ? URL::to($this->bride_image) : null;
    }

    // 🤵 Groom image full URL
    public function getGroomImageUrlAttribute()
    {
        return $this->groom_image ? URL::to($this->groom_image) : null;
    }

    // 🎨 Season image full URL
    public function getSeasonImageUrlAttribute()
    {
        return $this->season_image ? URL::to($this->season_image) : null;
    }

    // 🌸 Season theme image full URL
    public function getSeasonThemeImageUrlAttribute()
    {
        return $this->season_theme_image ? URL::to($this->season_theme_image) : null;
    }

    // 🧑‍🎨 Edited versions
    public function getBrideEditedImageUrlAttribute()
    {
        return $this->bride_edited_image ? URL::to($this->bride_edited_image) : null;
    }

    public function getGroomEditedImageUrlAttribute()
    {
        return $this->groom_edited_image ? URL::to($this->groom_edited_image) : null;
    }

  
}
