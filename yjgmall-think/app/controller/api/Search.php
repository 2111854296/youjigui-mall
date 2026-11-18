<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Article;
use app\model\Company;
use app\model\Product;
use app\model\Purchase;
use app\model\Supply;
use think\Response;

class Search extends BaseController
{
    public function index(): Response
    {
        $type = (string) $this->request->param('type', 'sell');
        $q = trim((string) $this->request->param('q', ''));
        if ($q === '') {
            return json(['data' => [], 'meta' => ['type' => $type, 'q' => $q, 'total' => 0]]);
        }

        $like = '%'.$q.'%';
        $page = ['list_rows' => 16, 'page' => max(1, (int) $this->request->param('page/d', 1))];

        $payload = match ($type) {
            'buy' => paginate_laravel(
                Purchase::publishedQuery()->with(['category', 'user'])
                    ->where(function ($w) use ($like) {
                        $w->whereLike('title', $like)->whereOr('summary', 'like', $like);
                    })->paginate($page),
                static fn ($r) => $r->toListArray()
            ),
            'company' => paginate_laravel(
                Company::publishedQuery()
                    ->where(function ($w) use ($like) {
                        $w->whereLike('name', $like)->whereOr('business', 'like', $like)->whereOr('area', 'like', $like);
                    })->paginate($page),
                static fn ($r) => $r->toListArray()
            ),
            'news' => paginate_laravel(
                Article::publishedQuery()
                    ->where(function ($w) use ($like) {
                        $w->whereLike('title', $like)->whereOr('summary', 'like', $like);
                    })->paginate($page),
                static fn ($r) => $r->toListArray()
            ),
            'mall' => paginate_laravel(
                Product::publishedQuery()->with(['category'])
                    ->where(function ($w) use ($like) {
                        $w->whereLike('title', $like)->whereOr('summary', 'like', $like);
                    })->paginate($page),
                static fn ($r) => $r->toListArray()
            ),
            default => paginate_laravel(
                Supply::publishedQuery()->with(['category', 'user'])
                    ->where(function ($w) use ($like) {
                        $w->whereLike('title', $like)->whereOr('brand', 'like', $like)->whereOr('summary', 'like', $like);
                    })->paginate($page),
                static fn ($r) => $r->toListArray()
            ),
        };

        $payload['meta_query'] = ['type' => $type, 'q' => $q];

        return json($payload);
    }
}
