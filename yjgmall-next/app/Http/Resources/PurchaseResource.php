<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'thumb' => MediaUrl::make($this->thumb),
            'budget' => $this->budget !== null ? (string) $this->budget : null,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'area' => $this->area,
            'days' => $this->days,
            'summary' => $this->summary,
            'content' => $this->content,
            'status' => $this->status,
            'is_urgent' => (bool) $this->is_urgent,
            'level' => (int) $this->level,
            'views' => (int) $this->views,
            'refreshed_at' => optional($this->refreshed_at)?->toDateTimeString(),
            'quotes_count' => $this->whenCounted('quotes'),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'company_name' => $this->user?->company_name,
                'mobile' => $this->user?->mobile,
            ],
        ];
    }
}
