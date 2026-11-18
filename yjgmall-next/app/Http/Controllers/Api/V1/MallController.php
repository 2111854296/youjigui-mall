<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class MallController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::published()->with('category');
        if ($request->filled('q')) {
            $kw = '%'.$request->string('q').'%';
            $catIds = Category::query()
                ->where('module', 'sell')
                ->where('name', 'like', $kw)
                ->pluck('id')
                ->all();
            $childOfMatched = $catIds
                ? Category::query()->whereIn('parent_id', $catIds)->pluck('id')->all()
                : [];
            $allCatIds = array_values(array_unique(array_merge($catIds, $childOfMatched)));

            $query->where(function ($w) use ($kw, $allCatIds) {
                $w->where('title', 'like', $kw)
                    ->orWhere('summary', 'like', $kw);
                if ($allCatIds) {
                    $w->orWhereIn('category_id', $allCatIds);
                }
            });
        }
        if ($request->filled('category')) {
            $catId = $request->integer('category');
            $childIds = Category::query()->where('parent_id', $catId)->pluck('id')->all();
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        $tab = (string) $request->string('tab', 'all');
        match ($tab) {
            'hot' => $query->orderByDesc('sales'),
            'new' => $query->latest(),
            'deal' => $query->orderBy('price'),
            default => $query->latest(),
        };

        $paginator = $query->paginate(16);

        $filterLabel = null;
        if ($request->filled('category')) {
            $filterLabel = Category::query()->whereKey($request->integer('category'))->value('name');
        } elseif ($request->filled('q')) {
            $filterLabel = (string) $request->string('q');
        }

        $showFloors = ! $request->filled('q')
            && ! $request->filled('category')
            && $tab === 'all'
            && $request->integer('page', 1) <= 1;

        $floors = collect();
        if ($showFloors) {
            $floors = Category::query()
                ->whereNull('parent_id')
                ->where('module', 'sell')
                ->where('is_active', true)
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])
                ->orderBy('sort')
                ->get()
                ->values()
                ->map(function (Category $cat, int $index) {
                    $ids = array_merge([$cat->id], $cat->children->pluck('id')->all());

                    return [
                        'floor_no' => $index + 1,
                        'category' => new CategoryResource($cat),
                        'children' => CategoryResource::collection($cat->children),
                        'products' => ProductResource::collection(
                            Product::published()
                                ->whereIn('category_id', $ids)
                                ->orderByDesc('sales')
                                ->limit(10)
                                ->get()
                        ),
                    ];
                });
        }

        return ProductResource::collection($paginator)->additional([
            'floors' => $floors,
            'tabs' => ['all', 'new', 'hot', 'deal'],
            'filter_label' => $filterLabel,
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'approved', 404);
        $product->increment('views');
        $product->load(['category', 'user']);
        $avg = round((float) \App\Models\ProductReview::published()->where('product_id', $product->id)->avg('rating'), 1);
        $count = \App\Models\ProductReview::published()->where('product_id', $product->id)->count();

        return (new ProductResource($product))->additional([
            'reviews_meta' => ['avg_rating' => $avg, 'count' => $count],
        ]);
    }
}
