<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\QuoteProduct;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $items = QuoteProduct::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderByDesc('quoted_on')
            ->paginate(50);

        return response()->json([
            'data' => $items->getCollection()->map(fn (QuoteProduct $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'unit' => $p->unit,
                'price_min' => $p->price_min !== null ? (string) $p->price_min : null,
                'price_max' => $p->price_max !== null ? (string) $p->price_max : null,
                'price_avg' => $p->price_avg !== null ? (string) $p->price_avg : null,
                'market' => $p->market,
                'quoted_on' => optional($p->quoted_on)?->toDateString(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
