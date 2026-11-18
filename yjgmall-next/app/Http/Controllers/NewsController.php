<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::published()->latest('published_at');
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->q.'%');
        }
        $items = $query->paginate(20)->withQueryString();

        return view('news.index', compact('items'));
    }

    public function show(Article $article)
    {
        abort_unless($article->status === 'published', 404);
        $article->increment('views');

        return view('news.show', compact('article'));
    }
}
