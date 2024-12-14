<?php

// App\Http\Resources\ProductResource.php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sold' => $this->sold,
            'rate' => $this->rate,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'product_details' => ProductDetailResource::collection($this->whenLoaded('productDetails')),
            'images' => ImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
