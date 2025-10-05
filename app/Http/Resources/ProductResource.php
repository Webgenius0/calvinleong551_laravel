<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => md5($this->id),
            'slug'  =>$this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'subcategory' => $this->subcategory ? [
                'id' => $this->subcategory->id,
                'name' => $this->subcategory->name,
                
            ] : null,
            'images' => $this->images->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => asset($image->image), // Assuming 'image' is the path
                   
                ];
            }),
            'sizes' => $this->sizes,
            'colors' => $this->colors,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            
        ];
    }
}
