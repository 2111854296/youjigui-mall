<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Article;
use app\model\ArticleTopic;
use app\model\Category;
use think\Response;

class News extends BaseController
{
    public function index(): Response
    {
        $query = Article::publishedQuery()->with(['category'])->order('published_at', 'desc');
        if ($this->request->param('type')) {
            $query->where('type', $this->request->param('type'));
        }
        if ($this->request->param('category') !== null && $this->request->param('category') !== '') {
            $catId = (int) $this->request->param('category');
            $childIds = Category::where('parent_id', $catId)->column('id');
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        if ($this->request->param('q')) {
            $query->whereLike('title', '%'.$this->request->param('q').'%');
        }
        $sort = (string) $this->request->param('sort', 'new');
        if ($sort === 'hot') {
            $query->removeOption('order');
            Article::orderByDisplayViews($query, 'desc');
        } elseif ($sort === 'top') {
            $query->removeOption('order')->order('is_top', 'desc')->order('published_at', 'desc');
        }

        $paginator = $query->paginate(['list_rows' => 20, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        $cats = Category::where('module', 'article')->whereNull('parent_id')->with(['children'])->order('sort', 'asc')->select();
        $categories = [];
        foreach ($cats as $c) {
            $children = [];
            foreach ($c->children ?? [] as $ch) {
                $children[] = ['id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug];
            }
            $categories[] = ['id' => (int) $c->id, 'name' => $c->name, 'slug' => $c->slug, 'children' => $children];
        }

        $payload = paginate_laravel($paginator, static fn ($a) => $a->toListArray());
        $payload['categories'] = $categories;
        $payload['topics'] = ArticleTopic::where('is_active', 1)->order('sort', 'asc')->field(['id', 'name', 'slug', 'summary'])->select()->toArray();

        return json($payload);
    }

    public function rank(): Response
    {
        $items = Article::orderByDisplayViews(Article::publishedQuery(), 'desc')->limit(30)->select();

        return json_data(array_map(static fn ($a) => $a->toListArray(), $items->all()));
    }

    public function topics(): Response
    {
        $items = ArticleTopic::where('is_active', 1)->order('sort', 'asc')->select();
        $data = [];
        foreach ($items as $t) {
            $data[] = [
                'id' => (int) $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'summary' => $t->summary,
                'articles_count' => 0,
            ];
        }

        return json_data($data);
    }

    public function topic($slug): Response
    {
        $topic = ArticleTopic::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->whereOr('id', $slug);
        })->where('is_active', 1)->find();
        if (!$topic) {
            return json_error('Not Found', 404);
        }
        $articleIds = \think\facade\Db::name('article_topic')
            ->where('article_topic_id', $topic->id)
            ->column('article_id');
        $paginator = Article::publishedQuery()
            ->whereIn('id', $articleIds ?: [0])
            ->order('published_at', 'desc')
            ->paginate(['list_rows' => 20, 'page' => max(1, (int) $this->request->param('page/d', 1))]);
        $payload = paginate_laravel($paginator, static fn ($a) => $a->toListArray());
        $payload['topic'] = [
            'id' => (int) $topic->id,
            'name' => $topic->name,
            'slug' => $topic->slug,
            'summary' => $topic->summary,
        ];

        return json($payload);
    }

    public function show($id): Response
    {
        $article = Article::with(['category'])->find($id);
        if (!$article || $article->status !== 'published') {
            return json_error('Not Found', 404);
        }
        $article->views = (int) $article->views + 1;
        $article->save();

        return json_data($article->toListArray() + [
            'content' => $article->content,
            'is_top' => (bool) $article->is_top,
            'status' => $article->status,
            'category' => $article->category ? [
                'id' => (int) $article->category->id,
                'name' => $article->category->name,
                'slug' => $article->category->slug,
            ] : null,
        ]);
    }
}
