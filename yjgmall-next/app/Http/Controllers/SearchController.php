<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supply;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type', 'sell');
        $q = trim((string) $request->input('q', ''));
        $items = collect();

        if ($q !== '') {
            $items = match ($type) {
                'buy' => Purchase::published()->where('title', 'like', "%{$q}%")->paginate(16)->withQueryString(),
                'company' => Company::published()->where('name', 'like', "%{$q}%")->paginate(16)->withQueryString(),
                'news' => Article::published()->where('title', 'like', "%{$q}%")->paginate(16)->withQueryString(),
                'mall' => Product::published()->where('title', 'like', "%{$q}%")->paginate(16)->withQueryString(),
                default => Supply::published()->where('title', 'like', "%{$q}%")->paginate(16)->withQueryString(),
            };
        }

        return view('search.index', compact('type', 'q', 'items'));
    }
}
