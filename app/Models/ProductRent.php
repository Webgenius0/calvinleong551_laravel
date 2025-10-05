<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRent extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at', 'expected_return_date', 'returned_product_image'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }
}
