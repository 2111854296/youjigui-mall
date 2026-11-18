<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::published()->orderByDesc('is_featured')->orderBy('sort')->latest();
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q').'%');
        }
        $paginator = $query->paginate(20);

        return response()->json([
            'data' => collect($paginator->items())->map(fn (Brand $b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'logo' => $b->logo,
                'website' => $b->website,
                'country' => $b->country,
                'intro' => $b->intro,
                'is_featured' => (bool) $b->is_featured,
                'views' => (int) $b->views,
            ]),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(string $brand)
    {
        $model = Brand::query()->where('slug', $brand)->orWhere('id', $brand)->firstOrFail();
        abort_unless($model->status === 'published', 404);
        $model->increment('views');

        return response()->json([
            'data' => [
                'id' => $model->id,
                'name' => $model->name,
                'slug' => $model->slug,
                'logo' => $model->logo,
                'website' => $model->website,
                'country' => $model->country,
                'intro' => $model->intro,
                'content' => $model->content,
                'is_featured' => (bool) $model->is_featured,
                'views' => (int) $model->views,
            ],
        ]);
    }
}
