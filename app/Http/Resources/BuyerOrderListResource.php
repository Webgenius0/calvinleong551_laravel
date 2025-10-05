<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuyerOrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'uid'                => $this->order->uid ?? null,
            'status'             => $this->order->status ?? null,
            'price'              => $this->price,
            'quantity'           => $this->quantity,
            'product_size'       => $this->product_size,
            'item_vat'           => $this->item_vat,
            'product_name'       => $this->product_name ,
            'refund_status'      => $this->refund_status,
            'color'              => $this->buyer_choosed_color,
            'image'              => url($this->image) ?? null
        ];
    }
}
