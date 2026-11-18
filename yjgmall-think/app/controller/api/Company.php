<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Category;
use app\model\CompanyHonor;
use app\model\CompanyLink;
use app\model\CompanyNews;
use app\model\CompanyPage;
use app\model\Product;
use app\model\Supply;
use think\Response;

class Company extends BaseController
{
    public function index(): Response
    {
        $query = \app\model\Company::publishedQuery()->with(['category'])->order('id', 'desc');
        $q = trim((string) $this->request->param('q', ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like) {
                $w->whereLike('name', $like)
                    ->whereOr('business', 'like', $like)
                    ->whereOr('area', 'like', $like);
            });
        }
        if ($this->request->param('category') !== null && $this->request->param('category') !== '') {
            $catId = (int) $this->request->param('category');
            $childIds = Category::where('parent_id', $catId)->column('id');
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        if ($this->request->param('featured')) {
            $query->where(function ($w) {
                $w->where('is_featured', 1)->whereOr('is_certified', 1);
            });
        }

        $featuredRows = \app\model\Company::publishedQuery()
            ->where(function ($w) {
                $w->where('is_featured', 1)->whereOr('is_certified', 1);
            })
            ->order('id', 'desc')->limit(12)->select();
        $featured = array_map(static fn ($c) => $c->toListArray(), $featuredRows->all());

        $cats = Category::whereNull('parent_id')->where('module', 'sell')->where('is_active', 1)
            ->with(['children'])->order('sort', 'asc')->select();
        $floors = [];
        $categories = [];
        foreach ($cats as $index => $cat) {
            $ids = array_merge([(int) $cat->id], array_map('intval', array_column(($cat->children ? $cat->children->toArray() : []), 'id')));
            $name = $cat->name;
            $companies = \app\model\Company::publishedQuery()
                ->where(function ($w) use ($ids, $name) {
                    $w->whereIn('category_id', $ids)->whereOr('business', 'like', '%'.$name.'%');
                })
                ->order('id', 'desc')->limit(8)->select();
            $children = [];
            foreach ($cat->children ?? [] as $ch) {
                $children[] = ['id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug];
            }
            $floors[] = [
                'floor_no' => $index + 1,
                'category' => ['id' => (int) $cat->id, 'name' => $cat->name, 'slug' => $cat->slug],
                'children' => $children,
                'companies' => array_map(static fn ($c) => $c->toListArray(), $companies->all()),
            ];
            $categories[] = ['id' => (int) $cat->id, 'name' => $cat->name, 'slug' => $cat->slug, 'children' => $children];
        }

        $paginator = $query->paginate(['list_rows' => 16, 'page' => max(1, (int) $this->request->param('page/d', 1))]);
        $payload = paginate_laravel($paginator, static fn ($c) => $c->toListArray());
        $payload['featured'] = $featured;
        $payload['floors'] = $floors;
        $payload['categories'] = $categories;

        return json($payload);
    }

    public function show($company): Response
    {
        // note: controller class name conflicts with model — always use FQCN for model
        $model = \app\model\Company::where('slug', $company)->whereOr('id', $company)->find();
        if (!$model || $model->status !== 'approved') {
            return json_error('Not Found', 404);
        }
        $model->views = (int) $model->views + 1;
        $model->save();

        $supplies = Supply::publishedQuery()->where('user_id', $model->user_id)->order('refreshed_at', 'desc')->limit(10)->select();
        $products = Product::publishedQuery()->where('user_id', $model->user_id)->order('id', 'desc')->limit(8)->select();

        $data = $model->toListArray() + [
            'contact' => $model->contact,
            'phone' => $model->phone,
            'address' => $model->address,
            'views' => (int) $model->views,
            'content' => $model->content ?? $model->intro,
            'supplies' => array_map(static fn ($s) => $s->toListArray(), $supplies->all()),
            'products' => array_map(static fn ($p) => $p->toListArray(), $products->all()),
            'news' => CompanyNews::where('company_id', $model->id)->where('status', 'published')->order('id', 'desc')->limit(10)->select()->toArray(),
            'honors' => CompanyHonor::where('company_id', $model->id)->where('status', 'approved')->order('id', 'desc')->limit(10)->select()->toArray(),
            'pages' => CompanyPage::where('company_id', $model->id)->where('status', 'published')->order('sort', 'asc')->limit(10)->select()->toArray(),
            'links' => CompanyLink::where('company_id', $model->id)->order('sort', 'asc')->limit(20)->select()->toArray(),
        ];

        return json_data($data);
    }
}
