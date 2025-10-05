<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerOrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
        'id'            => $this->id,
        'order_id'      => $this->order_id,
        'product_id'    => $this->product_id,
        'seller_id'     => $this->seller_id,
        'product_name'  => $this->product_name,
        'product_color'  => $this->product_color,
        'product_size'  => $this->product_size,
        'admin_amount'  => $this->admin_amount,
        'seller_amount'  => $this->seller_amount,
        'quantity'      => $this->quantity,
        'total_price'   => $this->price,
        'item_price'    => $this->item_price,
        'seller_amount' => $this->seller_amount,
        'admin_amount'  => $this->admin_amount,
        // no created_at, updated_at
    ];

    }
}
