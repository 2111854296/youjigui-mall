<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Supply;
use Illuminate\Http\Request;

class SellController extends Controller
{
    public function index(Request $request)
    {
        $query = Supply::published()->with(['category', 'user'])->latest('refreshed_at');

        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->q.'%');
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $items = $query->paginate(16)->withQueryString();
        $categories = Category::where('module', 'sell')->whereNull('parent_id')->with('children')->orderBy('sort')->get();

        return view('sell.index', compact('items', 'categories'));
    }

    public function show(Supply $supply)
    {
        abort_unless($supply->status === 'approved', 404);
        $supply->increment('views');
        $supply->load(['user', 'category']);
        $related = Supply::published()->where('category_id', $supply->category_id)->where('id', '!=', $supply->id)->limit(6)->get();

        return view('sell.show', compact('supply', 'related'));
    }
}
