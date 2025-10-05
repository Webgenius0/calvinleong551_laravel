<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoostingPayment extends Model
{
    protected $table = 'boosting_payments';
    protected $fillable = [
        'user_id',
        'product_id',
        'boost_plan',
        'boosted_until',
        'payment_id',
        'status',
        'amount',
        'currency',
        'payment_method',
        'transaction_id',
        'metadata',
        'plan_name'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
