<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundRequest extends Model
{
    protected $guarded = ['id'];

     protected $hidden = [
        'created_at','updated_at',
    ];

    public function orderItmes()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }

     public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

     // Relationship to seller
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
