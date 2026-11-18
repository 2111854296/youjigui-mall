<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Company;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supply;

class HomeController extends Controller
{
    public function index()
    {
        $banners = Banner::query()->where('is_active', true)->where('position', 'home')->orderBy('sort')->get();
        $rootCategories = Category::query()
            ->whereNull('parent_id')
            ->where('module', 'sell')
            ->where('is_active', true)
            ->with('children')
            ->orderBy('sort')
            ->get();

        $supplies = Supply::published()->latest('refreshed_at')->limit(10)->get();
        $purchases = Purchase::published()->latest('refreshed_at')->limit(10)->get();
        $products = Product::published()->latest()->limit(8)->get();
        $articles = Article::published()->latest('published_at')->limit(10)->get();
        $quotes = Article::published()->where('type', 'quote')->latest('published_at')->limit(6)->get();
        $companies = Company::published()->where('is_featured', true)->limit(6)->get();

        return view('home.index', compact(
            'banners', 'rootCategories', 'supplies', 'purchases', 'products', 'articles', 'quotes', 'companies'
        ));
    }
}
