<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Purchase;
use Illuminate\Http\Request;

class BuyController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::published()->with(['category', 'user'])->latest('refreshed_at');
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        $items = $query->paginate(16)->withQueryString();
        $categories = Category::where('module', 'buy')->whereNull('parent_id')->orderBy('sort')->get();

        return view('buy.index', compact('items', 'categories'));
    }

    public function show(Purchase $purchase)
    {
        abort_unless($purchase->status === 'approved', 404);
        $purchase->increment('views');
        $purchase->load(['user', 'category']);

        return view('buy.show', compact('purchase'));
    }
}
