<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $guarded = [];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function subcategoryBrand()
    {
        return $this->hasMany(SubCategoryBrand::class,'sub_category_id');
    }
    public function subcategoryColor()
    {
        return $this->hasMany(SubCategoryColor::class,'sub_category_id');
    }
    public function subcategoryCondition()
    {
        return $this->hasMany(SubCategoryCondition::class,'sub_category_id');
    }
     public function subcategoryMaterial()
    {
        return $this->hasMany(SubCategoryMaterial::class,'sub_category_id');
    }

     public function subcategorySize()
    {
        return $this->hasMany(SubCategorySize::class,'sub_category_id');
    }

    
}
