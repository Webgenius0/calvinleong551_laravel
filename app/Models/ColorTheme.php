<?php

namespace App\Models;

use Illuminate\Support\Facades\URL;
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

    // Either remove this line or keep it only if you use the method below
    protected $appends = ['formatted_images'];

    public function aiSuggestion()
    {
        return $this->belongsTo(AISuggestion::class);
    }

    public function getImagesAttribute($value)
    {
        $images = is_array($value) ? $value : json_decode($value, true);

        if (!is_array($images)) {
            return [];
        }

        foreach ($images as &$img) {
            if (isset($img['url']) && !str_starts_with($img['url'], 'http')) {
                $img['url'] = url($img['url']);
            }
        }

        return $images;
    }

    // ✅ Add this to fix the error
    public function getFormattedImagesAttribute()
    {
        return $this->images;
    }
}
