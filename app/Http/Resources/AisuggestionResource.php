<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AisuggestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
          return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'color_codes' => is_string($this->color_codes)
                ? json_decode($this->color_codes, true)
                : $this->color_codes,
            'images' => is_string($this->images)
                ? json_decode($this->images, true)
                : $this->images,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
