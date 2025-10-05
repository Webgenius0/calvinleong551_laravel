<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddToCart extends Model
{
    protected $table = "add_to_carts";

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
        
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cartItem()
    {
        return $this->hasMany(CartItem::class, 'add_to_cart_id','id');
               
    }
}
