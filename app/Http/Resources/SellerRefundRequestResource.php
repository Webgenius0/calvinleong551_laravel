<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SellerRefundRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
         $orderItem = $this->whenLoaded('orderItmes');

    return array_merge([
        'refund_id' => $this->id, 
        'order_id' => $orderItem->order_id,
        'product_id' => $orderItem->product_id,     
        'order_item_id' => $this->order_item_id,
        'buyer_id' => $this->buyer_id,
        'seller_id' => $this->seller_id,
        'buyer_name' => $this->buyer_name ?? null,
        'status' => $this->status,
        'reason' => $this->reason,
        'issue_image' => url($this->image) ?? null,
    ], $orderItem ? [
              
        'quantity' => $orderItem->quantity,
        'product_name' => $orderItem->product_name,
        'product_color' => $orderItem->product_color,
        'buyer_choosed_color' => $orderItem->buyer_choosed_color,
        'product_brand' => $orderItem->product_brand,
        'product_size' => $orderItem->product_size,
        'product_material' => $orderItem->product_material,
        'product_condition' => $orderItem->product_condition,
        'price' => $orderItem->price,
        'item_price' => $orderItem->item_price,
        'item_vat' => $orderItem->item_vat,
        'product_image' => url($orderItem->image),      
    ] : []);
}
}
