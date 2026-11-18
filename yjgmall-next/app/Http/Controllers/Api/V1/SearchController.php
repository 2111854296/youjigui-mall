<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\SupplyResource;
use App\Models\Article;
use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supply;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request)
    {
        $type = $request->input('type', 'sell');
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json(['data' => [], 'meta' => ['type' => $type, 'q' => $q, 'total' => 0]]);
        }

        $like = "%{$q}%";

        $result = match ($type) {
            'buy' => PurchaseResource::collection(
                Purchase::published()
                    ->with(['category', 'user'])
                    ->where(function ($w) use ($like) {
                        $w->where('title', 'like', $like)->orWhere('summary', 'like', $like);
                    })
                    ->paginate(16)
            ),
            'company' => CompanyResource::collection(
                Company::published()
                    ->where(function ($w) use ($like) {
                        $w->where('name', 'like', $like)
                            ->orWhere('business', 'like', $like)
                            ->orWhere('area', 'like', $like);
                    })
                    ->paginate(16)
            ),
            'news' => ArticleResource::collection(
                Article::published()
                    ->where(function ($w) use ($like) {
                        $w->where('title', 'like', $like)->orWhere('summary', 'like', $like);
                    })
                    ->paginate(16)
            ),
            'mall' => ProductResource::collection(
                Product::published()
                    ->with(['category', 'user'])
                    ->where(function ($w) use ($like) {
                        $w->where('title', 'like', $like)->orWhere('summary', 'like', $like);
                    })
                    ->paginate(16)
            ),
            default => SupplyResource::collection(
                Supply::published()
                    ->with(['category', 'user'])
                    ->where(function ($w) use ($like) {
                        $w->where('title', 'like', $like)
                            ->orWhere('brand', 'like', $like)
                            ->orWhere('summary', 'like', $like);
                    })
                    ->paginate(16)
            ),
        };

        return $result->additional(['meta_query' => ['type' => $type, 'q' => $q]]);
    }
}
