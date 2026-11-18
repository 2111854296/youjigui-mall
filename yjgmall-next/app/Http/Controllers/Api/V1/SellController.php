<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\SupplyResource;
use App\Models\Category;
use App\Models\Supply;
use Illuminate\Http\Request;

class SellController extends Controller
{
    public function index(Request $request)
    {
        $query = Supply::published()->with(['category', 'user']);

        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', $q)
                    ->orWhere('brand', 'like', $q)
                    ->orWhere('summary', 'like', $q);
            });
        }
        if ($request->filled('category')) {
            $catId = $request->integer('category');
            $childIds = Category::query()->where('parent_id', $catId)->pluck('id')->all();
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        if ($request->filled('area')) {
            $query->where('area', 'like', '%'.$request->string('area').'%');
        }
        if ($request->boolean('elite')) {
            $query->where('is_elite', true);
        }
        if ($request->boolean('urgent')) {
            $query->where('is_urgent', true);
        }
        if ($request->boolean('vip')) {
            $query->where('level', '>', 0);
        }
        if ($request->boolean('priced')) {
            $query->whereNotNull('price')->where('price', '>', 0);
        }
        if ($request->filled('days')) {
            $days = max(1, min(365, $request->integer('days')));
            $query->where('refreshed_at', '>=', now()->subDays($days));
        }

        $sort = $request->string('sort', 'refresh');
        match ((string) $sort) {
            'price_asc' => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'hits' => $query->orderByDesc('views'),
            'vip' => $query->orderByDesc('level')->latest('refreshed_at'),
            'amount_desc' => $query->orderByDesc('amount'),
            'amount_asc' => $query->orderBy('amount'),
            'min_desc' => $query->orderByDesc('min_amount'),
            'min_asc' => $query->orderBy('min_amount'),
            default => $query->orderByDesc('level')->latest('refreshed_at'),
        };

        $paginator = $query->paginate(min(50, max(1, $request->integer('per_page', 20))));

        $areas = Supply::published()
            ->whereNotNull('area')
            ->where('area', '!=', '')
            ->distinct()
            ->orderBy('area')
            ->limit(80)
            ->pluck('area')
            ->values();

        $categories = Category::query()->where('module', 'sell')->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])
            ->orderBy('sort')->get();

        $showFloors = ! $request->filled('q')
            && ! $request->filled('category')
            && ! $request->filled('area')
            && ! $request->filled('days')
            && ! $request->boolean('elite')
            && ! $request->boolean('vip')
            && ! $request->boolean('priced')
            && ! $request->boolean('urgent')
            && $request->integer('page', 1) <= 1;

        $floors = collect();
        $elite = collect();
        if ($showFloors) {
            $floors = $categories->values()->map(function (Category $cat, int $index) {
                $ids = array_merge([$cat->id], $cat->children->pluck('id')->all());

                return [
                    'floor_no' => $index + 1,
                    'category' => new CategoryResource($cat),
                    'children' => CategoryResource::collection($cat->children),
                    'items' => SupplyResource::collection(
                        Supply::published()
                            ->with(['category', 'user'])
                            ->whereIn('category_id', $ids)
                            ->orderByDesc('level')
                            ->latest('refreshed_at')
                            ->limit(8)
                            ->get()
                    ),
                ];
            });

            $elite = SupplyResource::collection(
                Supply::published()
                    ->with(['category', 'user'])
                    ->where('is_elite', true)
                    ->orderByDesc('level')
                    ->latest('refreshed_at')
                    ->limit(8)
                    ->get()
            );
        }

        return SupplyResource::collection($paginator)->additional([
            'filters' => [
                'areas' => $areas,
                'sort' => (string) $sort,
                'days' => $request->filled('days') ? $request->integer('days') : null,
                'view' => (string) $request->string('view', 'list'),
            ],
            'categories' => CategoryResource::collection($categories),
            'floors' => $floors,
            'elite' => $elite,
        ]);
    }

    public function show(Supply $supply)
    {
        abort_unless($supply->status === 'approved', 404);
        $supply->increment('views');
        $supply->load(['category', 'user']);

        return new SupplyResource($supply);
    }

    public function compare(Request $request)
    {
        $ids = collect(explode(',', (string) $request->string('ids')))
            ->map(fn ($v) => (int) trim($v))
            ->filter()
            ->unique()
            ->take(5)
            ->values();
        abort_if($ids->isEmpty(), 422, '请选择要对比的供应');

        $items = Supply::published()->with(['category', 'user'])->whereIn('id', $ids)->get();

        return SupplyResource::collection($items);
    }
}
