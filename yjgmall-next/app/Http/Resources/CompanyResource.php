<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo' => MediaUrl::make($this->logo),
            'contact' => $this->contact,
            'phone' => $this->phone,
            'website' => $this->website,
            'address' => $this->address,
            'area' => $this->area,
            'business' => $this->business,
            'intro' => $this->intro,
            'content' => $this->content,
            'status' => $this->status,
            'is_featured' => (bool) $this->is_featured,
            'is_certified' => (bool) $this->is_certified,
            'template' => $this->template ?: 'default',
            'views' => (int) $this->views,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'supplies' => SupplyResource::collection($this->whenLoaded('supplies')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'news' => $this->whenLoaded('news', fn () => $this->news->map(fn ($n) => [
                'id' => $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'created_at' => $n->created_at?->toDateTimeString(),
            ])),
            'honors' => $this->whenLoaded('honors', fn () => $this->honors->map(fn ($h) => [
                'id' => $h->id,
                'title' => $h->title,
                'image' => MediaUrl::make($h->image),
                'authority' => $h->authority,
                'issued_at' => optional($h->issued_at)?->toDateString(),
            ])),
            'pages' => $this->whenLoaded('pages', fn () => $this->pages->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'content' => $p->content,
            ])),
            'links' => $this->whenLoaded('links', fn () => $this->links->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'url' => $l->url,
            ])),
        ];
    }
}
