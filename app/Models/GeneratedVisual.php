<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GeneratedVisual extends Model
{
  use HasFactory;

    protected $fillable = [
        'wedding_session_id',
        'type',
        'image_url',
        'color_codes'
    ];

    protected $casts = [
        'color_codes' => 'array'
    ];

    public function weddingSession()
    {
        return $this->belongsTo(WeddingSession::class);
    }
}
