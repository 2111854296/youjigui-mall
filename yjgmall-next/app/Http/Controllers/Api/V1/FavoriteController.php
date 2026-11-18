<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\QuoteProduct;
use App\Models\Supply;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    private array $map = [
        'supply' => Supply::class,
        'purchase' => Purchase::class,
        'product' => Product::class,
        'quote' => QuoteProduct::class,
    ];

    public function index(Request $request)
    {
        $items = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $items->getCollection()->map(function (Favorite $f) {
                $model = $f->favoritable;
                $type = array_search($f->favoritable_type, $this->map, true) ?: class_basename($f->favoritable_type);

                return [
                    'id' => $f->id,
                    'type' => $type,
                    'target_id' => $f->favoritable_id,
                    'title' => $model?->title ?? $model?->name ?? ('#'.$f->favoritable_id),
                    'created_at' => $f->created_at?->toDateTimeString(),
                ];
            }),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:supply,purchase,product,quote',
            'id' => 'required|integer',
        ]);
        $class = $this->map[$data['type']];
        $target = $class::query()->findOrFail($data['id']);

        $fav = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'favoritable_type' => $class,
            'favoritable_id' => $target->id,
        ]);

        return response()->json(['data' => ['id' => $fav->id], 'message' => '已收藏'], 201);
    }

    public function destroy(Request $request, Favorite $favorite)
    {
        abort_unless($favorite->user_id === $request->user()->id, 403);
        $favorite->delete();

        return response()->json(['message' => 'ok']);
    }
}
