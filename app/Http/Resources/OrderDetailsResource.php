<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderDetailsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->order_id,
            'amount' => $this->amount,
            'product_detail' => new ProductDetailResource($this->whenLoaded('productDetail')), 
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
