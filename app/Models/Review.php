<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    

    protected $fillable = ['user_id','product_id','rating','comment'];

    protected $hiden = ['created_at','updated_at'];

    public function productRating()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
