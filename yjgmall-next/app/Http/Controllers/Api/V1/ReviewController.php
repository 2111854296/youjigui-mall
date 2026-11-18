<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Product $product)
    {
        abort_unless($product->status === 'approved', 404);
        $items = ProductReview::published()
            ->where('product_id', $product->id)
            ->with('user:id,name,username')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => collect($items->items())->map(fn (ProductReview $r) => [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'content' => $r->content,
                'user' => $r->user?->only(['id', 'name', 'username']),
                'created_at' => $r->created_at?->toDateTimeString(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
                'avg_rating' => round((float) ProductReview::published()->where('product_id', $product->id)->avg('rating'), 1),
            ],
        ]);
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($product->status === 'approved', 404);
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'content' => 'required|string|max:1000',
        ]);

        $review = ProductReview::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $request->user()->id],
            [...$data, 'status' => 'published']
        );

        return response()->json(['data' => ['id' => $review->id], 'message' => '评价已提交'], 201);
    }

    public function mine(Request $request)
    {
        $items = ProductReview::query()
            ->where('user_id', $request->user()->id)
            ->with('product:id,title')
            ->latest()
            ->get();

        return response()->json([
            'data' => $items->map(fn (ProductReview $r) => [
                'id' => $r->id,
                'rating' => (int) $r->rating,
                'content' => $r->content,
                'product' => $r->product?->only(['id', 'title']),
                'created_at' => $r->created_at?->toDateTimeString(),
            ]),
        ]);
    }
}
