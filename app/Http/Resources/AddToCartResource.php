<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddToCartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'cart_id'            => $this->id,
            'total_price'   => $this->total_price,
            'vat_total'     => $this->vat ?? 0,
            'total_quantity' => $this->quantity ?? 0,
            'cart_items'    => $this->cartItem->map(function ($item) {
                return [
                    'id'            => $item->id,
                    'product_id'    => $item->product_id,
                    'product_name'  => $item->title ?? '',
                    'image'         => $item->product_image ? url($item->product_image) : null,
                    'quantity'      => $item->quantity,
                    'price'         => $item->price,
                    'vat'           => $item->vat ?? 0,
                    'total_price'   => $item->price * $item->quantity + ($item->vat ?? 0),
                    'category'      => $item->category_name ?? '',
                    'sub_category'  => $item->sub_category_name ?? '',
                    'product_size'  => $item->product_size ?? '',
                    'product_color' => $item->product_color ?? '',
                ];
            })->all(), // convert collection to array
        ];
    }
}
