<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoostPlan extends Model
{
    protected $fillable = [
        'name',
        'duration',
        'price',
        'status',
        'is_default',
    ];

    /**
     * Get the status of the boost plan.
     *
     * @return string
     */
    public function getStatusAttribute($value)
    {
        return $value === 'active' ? 'active' : 'inactive';
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
