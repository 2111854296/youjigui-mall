<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Category;
use app\model\Product;
use app\model\ProductReview;
use think\Response;

class Mall extends BaseController
{
    public function index(): Response
    {
        $query = Product::publishedQuery()->with(['category']);
        $q = trim((string) $this->request->param('q', ''));
        if ($q !== '') {
            $kw = '%'.$q.'%';
            $catIds = Category::where('module', 'sell')->whereLike('name', $kw)->column('id');
            $childOfMatched = $catIds ? Category::whereIn('parent_id', $catIds)->column('id') : [];
            $allCatIds = array_values(array_unique(array_merge($catIds, $childOfMatched)));
            $query->where(function ($w) use ($kw, $allCatIds) {
                $w->whereLike('title', $kw)->whereOr('summary', 'like', $kw);
                if ($allCatIds) {
                    $w->whereOr('category_id', 'in', $allCatIds);
                }
            });
        }
        if ($this->request->param('category') !== null && $this->request->param('category') !== '') {
            $catId = (int) $this->request->param('category');
            $childIds = Category::where('parent_id', $catId)->column('id');
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }

        $tab = (string) $this->request->param('tab', 'all');
        match ($tab) {
            'hot' => $query->order('sales', 'desc'),
            'new' => $query->order('id', 'desc'),
            'deal' => $query->order('price', 'asc'),
            default => $query->order('id', 'desc'),
        };

        $paginator = $query->paginate(['list_rows' => 16, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        $filterLabel = null;
        if ($this->request->param('category')) {
            $filterLabel = Category::where('id', (int) $this->request->param('category'))->value('name');
        } elseif ($q !== '') {
            $filterLabel = $q;
        }

        $showFloors = $q === '' && !$this->request->param('category') && $tab === 'all' && (int) $this->request->param('page/d', 1) <= 1;
        $floors = [];
        if ($showFloors) {
            $cats = Category::whereNull('parent_id')->where('module', 'sell')->where('is_active', 1)
                ->with(['children'])->order('sort', 'asc')->select();
            foreach ($cats as $index => $cat) {
                $ids = array_merge([(int) $cat->id], array_map('intval', array_column(($cat->children ? $cat->children->toArray() : []), 'id')));
                $products = Product::publishedQuery()->whereIn('category_id', $ids)->order('sales', 'desc')->limit(10)->select();
                $children = [];
                foreach ($cat->children ?? [] as $ch) {
                    $children[] = ['id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug];
                }
                $floors[] = [
                    'floor_no' => $index + 1,
                    'category' => ['id' => (int) $cat->id, 'name' => $cat->name, 'slug' => $cat->slug],
                    'children' => $children,
                    'products' => array_map(static fn ($p) => $p->toListArray(), $products->all()),
                ];
            }
        }

        $payload = paginate_laravel($paginator, static fn ($p) => $p->toListArray());
        $payload['floors'] = $floors;
        $payload['tabs'] = ['all', 'new', 'hot', 'deal'];
        $payload['filter_label'] = $filterLabel;

        return json($payload);
    }

    public function show($id): Response
    {
        $product = Product::with(['category', 'user'])->find($id);
        if (!$product || $product->status !== 'approved') {
            return json_error('Not Found', 404);
        }
        $product->views = (int) $product->views + 1;
        $product->save();

        $avg = round((float) ProductReview::where('status', 'published')->where('product_id', $product->id)->avg('rating'), 1);
        $count = (int) ProductReview::where('status', 'published')->where('product_id', $product->id)->count();

        return json([
            'data' => $product->toListArray() + [
                'content' => $product->content,
                'stock' => (int) ($product->stock ?? 0),
                'views' => (int) $product->views,
                'status' => $product->status,
                'user' => $product->user ? [
                    'id' => (int) $product->user->id,
                    'name' => $product->user->name,
                    'username' => $product->user->username,
                    'company_name' => $product->user->company_name,
                ] : null,
                'category' => $product->category ? [
                    'id' => (int) $product->category->id,
                    'name' => $product->category->name,
                    'slug' => $product->category->slug,
                ] : null,
            ],
            'reviews_meta' => ['avg_rating' => $avg, 'count' => $count],
        ]);
    }
}
