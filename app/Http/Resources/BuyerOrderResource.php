<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuyerOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'uid'         => $this->uid,
            'status'      => $this->status,
            'price'       => $this->price,
            'date'        => $this->created_at->format('jS F'),
            'order_items' => BuyerOrderListResource::collection($this->orderItems),
        ];
    }
}
