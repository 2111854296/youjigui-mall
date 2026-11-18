<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'title' => $this->title,
            'thumb' => MediaUrl::make($this->thumb),
            'thumb1' => MediaUrl::make($this->thumb1),
            'thumb2' => MediaUrl::make($this->thumb2),
            'price' => $this->price !== null ? (string) $this->price : null,
            'unit' => $this->unit,
            'min_amount' => $this->min_amount !== null ? (string) $this->min_amount : null,
            'amount' => $this->amount,
            'days' => $this->days,
            'brand' => $this->brand,
            'area' => $this->area,
            'attr1_name' => $this->attr1_name,
            'attr1_value' => $this->attr1_value,
            'attr2_name' => $this->attr2_name,
            'attr2_value' => $this->attr2_value,
            'attr3_name' => $this->attr3_name,
            'attr3_value' => $this->attr3_value,
            'attrs' => array_values(array_filter([
                $this->attr1_name ? ['name' => $this->attr1_name, 'value' => $this->attr1_value] : null,
                $this->attr2_name ? ['name' => $this->attr2_name, 'value' => $this->attr2_value] : null,
                $this->attr3_name ? ['name' => $this->attr3_name, 'value' => $this->attr3_value] : null,
            ])),
            'summary' => $this->summary,
            'content' => $this->content,
            'status' => $this->status,
            'is_urgent' => (bool) $this->is_urgent,
            'is_elite' => (bool) $this->is_elite,
            'level' => (int) $this->level,
            'views' => (int) $this->views,
            'refreshed_at' => optional($this->refreshed_at)?->toDateTimeString(),
            'created_at' => optional($this->created_at)?->toDateTimeString(),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'company_name' => $this->user?->company_name,
                'mobile' => $this->user?->mobile,
                'qq' => $this->user?->qq,
                'is_vip' => $this->user?->isVip(),
                'is_certified' => (bool) $this->user?->is_certified,
            ],
        ];
    }
}
