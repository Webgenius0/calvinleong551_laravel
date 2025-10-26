<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class ColorTheme extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'color_codes' => 'array',
    ];

    // Remove formatted_images from appends
    // protected $appends = ['formatted_images'];

    public function aiSuggestion()
    {
        return $this->belongsTo(AISuggestion::class);
    }

    /**
     * Get the images attribute as an array.
     */
    public function getImagesAttribute($value)
    {
        $data = $this->parseImagesData($value);

        // Normalize URLs
        return collect($data)->map(function ($img) {
            if (is_array($img) && isset($img['url']) && !str_starts_with($img['url'], 'http')) {
                $img['url'] = url($img['url']);
            }
            return $img;
        })->toArray();
    }

    private function parseImagesData($value)
    {
        if (is_null($value)) {
            return [];
        }

        $data = $value;

        if (is_string($data)) {
            while (is_string($data)) {
                $decoded = json_decode($data, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data = $decoded;
                } else {
                    break;
                }
            }
        }

        return is_array($data) ? $data : [];
    }
}