<?php

namespace App\Http\Resources;

use App\Models\ProductDetail;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'product_detail' => [
                'id'    => $this->productDetail->id,
                'product_id' => $this->productDetail->product_id, 
                'price' => $this->productDetail->price ?? null,
                'size' => $this->productDetail->size ?? null,
                'stock' => $this->productDetail->stock ?? null,
            ],
            'product' =>[
                "id" => $this->productDetail->product->id,
                "name" => $this->productDetail->product->name,
                "description" => $this->productDetail->product->description,
                "sold" => $this->productDetail->product->sold,
                "rate" => $this->productDetail->product->rate,
                "category" => $this->productDetail->product->category,

            ] ,
            'first_image' => $this->productDetail->product->images->first()->image_link ?? null, 
            'amount' => $this->amount,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
