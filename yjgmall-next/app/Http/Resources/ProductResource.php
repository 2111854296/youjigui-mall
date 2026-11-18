<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'thumb' => MediaUrl::make($this->thumb),
            'price' => (string) $this->price,
            'stock' => (int) $this->stock,
            'unit' => $this->unit,
            'summary' => $this->summary,
            'content' => $this->content,
            'status' => $this->status,
            'sales' => (int) $this->sales,
            'views' => (int) $this->views,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'company_name' => $this->user?->company_name,
            ]),
        ];
    }
}
