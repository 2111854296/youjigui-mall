<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CompanyResource;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::published()->with('category')->latest();
        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($builder) use ($q) {
                $builder->where('name', 'like', $q)
                    ->orWhere('business', 'like', $q)
                    ->orWhere('area', 'like', $q);
            });
        }
        if ($request->filled('category')) {
            $catId = $request->integer('category');
            $childIds = Category::query()->where('parent_id', $catId)->pluck('id')->all();
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        if ($request->boolean('featured')) {
            $query->where(function ($builder) {
                $builder->where('is_featured', true)->orWhere('is_certified', true);
            });
        }

        $featured = CompanyResource::collection(
            Company::published()
                ->where(function ($builder) {
                    $builder->where('is_featured', true)->orWhere('is_certified', true);
                })
                ->latest()
                ->limit(12)
                ->get()
        );

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
                $name = $cat->name;

                $companies = Company::published()
                    ->where(function ($builder) use ($ids, $name) {
                        $builder->whereIn('category_id', $ids)
                            ->orWhere('business', 'like', '%'.$name.'%');
                    })
                    ->latest()
                    ->limit(8)
                    ->get();

                return [
                    'floor_no' => $index + 1,
                    'category' => new CategoryResource($cat),
                    'children' => CategoryResource::collection($cat->children),
                    'companies' => CompanyResource::collection($companies),
                ];
            });

        return CompanyResource::collection($query->paginate(16))->additional([
            'featured' => $featured,
            'floors' => $floors,
            'categories' => CategoryResource::collection(
                Category::query()->where('module', 'sell')->whereNull('parent_id')
                    ->with('children')->orderBy('sort')->get()
            ),
        ]);
    }

    public function show(string $company)
    {
        $model = Company::query()->where('slug', $company)->orWhere('id', $company)->firstOrFail();
        abort_unless($model->status === 'approved', 404);
        $model->increment('views');
        $model->load([
            'supplies' => fn ($q) => $q->published()->latest('refreshed_at')->limit(10),
            'products' => fn ($q) => $q->published()->latest()->limit(8),
            'news' => fn ($q) => $q->where('status', 'published')->latest()->limit(10),
            'honors' => fn ($q) => $q->latest()->limit(10),
            'pages' => fn ($q) => $q->where('status', 'published')->orderBy('sort')->limit(10),
            'links' => fn ($q) => $q->orderBy('sort')->limit(20),
        ]);

        return new CompanyResource($model);
    }
}
