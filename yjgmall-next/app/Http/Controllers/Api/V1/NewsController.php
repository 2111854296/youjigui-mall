<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\CategoryResource;
use App\Models\Article;
use App\Models\ArticleTopic;
use App\Models\Category;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::published()->with('category')->latest('published_at');
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('category')) {
            $catId = $request->integer('category');
            $childIds = Category::query()->where('parent_id', $catId)->pluck('id')->all();
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        if ($request->filled('topic')) {
            $raw = (string) $request->string('topic');
            $topic = ArticleTopic::query()
                ->where('slug', $raw)
                ->when(ctype_digit($raw), fn ($q) => $q->orWhere('id', (int) $raw))
                ->first();
            if ($topic) {
                $query->whereHas('topics', fn ($q) => $q->where('article_topics.id', $topic->id));
            }
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->string('q').'%');
        }
        $sort = (string) $request->string('sort', 'new');
        if ($sort === 'hot') {
            $query->reorder()->orderByDesc('views');
        } elseif ($sort === 'top') {
            $query->reorder()->orderByDesc('is_top')->latest('published_at');
        }

        return ArticleResource::collection($query->paginate(20))->additional([
            'categories' => CategoryResource::collection(
                Category::query()->where('module', 'article')->whereNull('parent_id')
                    ->with('children')->orderBy('sort')->get()
            ),
            'topics' => ArticleTopic::query()->where('is_active', true)->orderBy('sort')->get(['id', 'name', 'slug', 'summary']),
        ]);
    }

    public function rank()
    {
        $items = Article::published()->orderByDesc('views')->limit(30)->get();

        return ArticleResource::collection($items);
    }

    public function topics()
    {
        $items = ArticleTopic::query()->where('is_active', true)->orderBy('sort')
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->get();

        return response()->json([
            'data' => $items->map(fn (ArticleTopic $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'summary' => $t->summary,
                'articles_count' => (int) $t->articles_count,
            ]),
        ]);
    }

    public function topic(string $slug)
    {
        $topic = ArticleTopic::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
        $articles = $topic->articles()->published()->latest('published_at')->paginate(20);

        return ArticleResource::collection($articles)->additional([
            'topic' => [
                'id' => $topic->id,
                'name' => $topic->name,
                'slug' => $topic->slug,
                'summary' => $topic->summary,
            ],
        ]);
    }

    public function show(Article $article)
    {
        abort_unless($article->status === 'published', 404);
        $article->increment('views');
        $article->load(['category', 'topics']);

        return new ArticleResource($article);
    }
}
