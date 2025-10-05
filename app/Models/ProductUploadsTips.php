<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductUploadsTips extends Model
{
    protected $table = 'product_uploads_tips';

    protected $fillable = [
        'title',
        'sub_title',
        'image',
        'status',
    ];

    public $timestamps = true;
}
