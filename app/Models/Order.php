<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public function orderItems()
    {
          return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

     public function payment()
    {
        return $this->hasOne(Payment::class, 'order_id', 'id');
    }
}
