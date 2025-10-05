<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = ['id'];


    protected static function booted()
    {
        static::retrieved(function ($product) {
            if ($product->is_boosted && $product->boosted_until < now()) {
                $product->is_boosted = false;
                $product->save();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    public function colors()
    {
        return $this->hasMany(ProductColor::class);
    }
    public function brands()
    {
        return $this->hasMany(ProductBrand::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function boostingPayment()
    {
        return $this->hasMany(BoostingPayment::class);
    }

    public function boostPlan()
    {
        return $this->belongsTo(BoostPlan::class);
    }

    public function rating()
    {
        return $this->hasMany(Review::class);
    }
    public function materials()
    {
        return $this->hasMany(ProductMaterial::class);
    }
    public function brand()
    {
        return $this->hasMany(ProductBrand::class);
    }
    public function condition()
    {
        return $this->hasMany(ProductCondition::class);
    }

    // seller relation

    public function seller()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    // product like

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'product_likes')->withTimestamps();
    }
}
