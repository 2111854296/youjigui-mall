<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'thumb' => MediaUrl::make($this->thumb),
            'author' => $this->author,
            'summary' => $this->summary,
            'content' => $this->content,
            'type' => $this->type,
            'status' => $this->status,
            'is_top' => (bool) $this->is_top,
            'views' => (int) $this->views,
            'published_at' => optional($this->published_at)?->toDateTimeString(),
            'category' => $this->whenLoaded('category', fn () => $this->category?->only(['id', 'name', 'slug'])),
            'topics' => $this->whenLoaded('topics', fn () => $this->topics->map->only(['id', 'name', 'slug'])),
        ];
    }
}
