<?php

namespace app\controller\api\v1;

use app\BaseController;
use app\common\ApiResource;
use app\common\SpreadRank;
use app\common\FloorSlots;
use app\model\Ad;
use app\model\Brand;
use app\model\Category;
use app\model\Product;
use app\model\ProductReview;
use think\Response;

class Mall extends BaseController
{
    public function index(): Response
    {
        $req = $this->request;
        $query = Product::publishedQuery()->with(['category', 'user', 'company']);
        $q = trim((string) $req->param('q', ''));
        if ($q !== '') {
            $kw = '%'.$q.'%';
            $catIds = Category::where('name', 'like', $kw)->column('id');
            $childOfMatched = $catIds ? Category::whereIn('parent_id', $catIds)->column('id') : [];
            $allCatIds = array_values(array_unique(array_merge($catIds ?: [], $childOfMatched ?: [])));
            $query->where(function ($w) use ($kw, $allCatIds) {
                $w->whereLike('title', $kw)->whereOr('summary', 'like', $kw);
                if ($allCatIds) {
                    $w->whereOr(function ($inner) use ($allCatIds) {
                        $inner->whereIn('category_id', $allCatIds);
                    });
                }
            });
        }
        if ($catId = (int) $req->param('category')) {
            filter_query_by_category($query, $catId, ['title', 'summary']);
        }
        $tab = (string) $req->param('tab', 'all');
        $sort = (string) $req->param('sort', '');
        if ($q !== '' && !in_array($sort, ['price_asc', 'price_desc', 'sales'], true)) {
            SpreadRank::apply($query, 'mall', $q);
        }
        if ($sort === 'price_asc') {
            $query->where('price', '>', 0)->order('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->order('price', 'desc');
        } elseif ($sort === 'sales') {
            $query->order('sales', 'desc');
        } else {
            match ($tab) {
                'hot' => $query->order('sales', 'desc'),
                'new' => $query->order('id', 'desc'),
                'deal' => $query->where('price', '>', '0')->order('price', 'asc'),
                default => $query->order('id', 'desc'),
            };
        }
        $paginator = $query->paginate(['list_rows' => 20]);
        $result = paginate_laravel($paginator, fn ($p) => ApiResource::product($p));
        $floors = [];
        $tabs = [
            ['key' => 'all', 'label' => '全部'],
            ['key' => 'hot', 'label' => '热销'],
            ['key' => 'new', 'label' => '新品'],
            ['key' => 'deal', 'label' => '特惠'],
        ];
        $cats = root_categories(['sell', 'mall'], 8);
        $categories = array_map(fn ($c) => ApiResource::category($c), $cats->all());
        if ((int) $req->param('page', 1) <= 1 && !$req->param('q') && !$req->param('category') && $tab === 'all' && !$sort) {
            foreach ($cats as $i => $cat) {
                $ids = array_merge([$cat->id], $cat->children ? $cat->children->column('id') : []);
                $items = Product::publishedQuery()->with(['category', 'user', 'company'])->whereIn('category_id', $ids)->order('sales', 'desc')->limit(20)->select();
                $floors[] = [
                    'floor_no' => $i + 1,
                    'category' => ApiResource::category($cat),
                    'children' => floor_children($cat),
                    'products' => array_map(fn ($p) => ApiResource::product($p), $items->all()),
                ];
            }
        }

        $brands = [];
        try {
            $brandRows = Brand::where('status', 'published')->order('sort', 'asc')->order('id', 'desc')->limit(16)->select();
            $brands = array_map(static fn ($b) => [
                'id' => (int) $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'logo' => $b->logo,
            ], $brandRows->all());
        } catch (\Throwable $e) {
            $brands = [];
        }

        $hot = array_map(
            fn ($p) => ApiResource::product($p),
            Product::publishedQuery()->with(['category', 'user', 'company'])->order('sales', 'desc')->limit(10)->select()->all()
        );

        $filterLabel = $req->param('q') ?: '';
        if ($catId = (int) $req->param('category')) {
            $filterLabel = (string) (Category::where('id', $catId)->value('name') ?: '分类商品');
        }

        return json(array_merge($result, [
            'floors'       => $floors,
            'tabs'         => $tabs,
            'categories'   => $categories,
            'brands'       => $brands,
            'hot'          => $hot,
            'filter_label' => $filterLabel ?: '全部商品',
            'active_root_id' => $catId ? category_root_id($catId) : null,
        ]));
    }

    public function samples(): Response
    {
        $req = $this->request;
        $query = Product::publishedQuery()->with(['category', 'user', 'company'])->where('is_sample', 1);
        $q = trim((string) $req->param('q', ''));
        if ($q !== '') {
            $kw = '%'.$q.'%';
            $query->whereLike('title', $kw);
        }
        if ($catId = (int) $req->param('category')) {
            filter_query_by_category($query, $catId, ['title', 'summary']);
        }
        $tab = (string) $req->param('tab', 'all');
        $sort = (string) $req->param('sort', '');
        if ($sort === 'price_asc') {
            $query->where('price', '>', 0)->order('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->order('price', 'desc');
        } elseif ($sort === 'sales') {
            $query->order('sales', 'desc');
        } else {
            match ($tab) {
                'hot' => $query->order('sales', 'desc'),
                'new' => $query->order('id', 'desc'),
                'deal' => $query->where('price', '>', '0')->order('price', 'asc'),
                default => $query->order('id', 'desc'),
            };
        }
        $paginator = $query->paginate(['list_rows' => 20]);
        $result = paginate_laravel($paginator, fn ($p) => ApiResource::product($p));
        $floors = [];
        $sampleCatIds = array_values(array_unique(array_map(
            'intval',
            Product::publishedQuery()->where('is_sample', 1)->where('category_id', '>', 0)->column('category_id')
        )));
        $sampleIdSet = array_flip($sampleCatIds);
        $rootIds = [];
        if ($sampleCatIds) {
            foreach (Category::whereIn('id', $sampleCatIds)->select() as $sampleCat) {
                $rootIds[] = (int) ($sampleCat->parent_id ?: $sampleCat->id);
            }
        }
        $rootIds = array_values(array_unique($rootIds));
        $cats = $rootIds
            ? Category::whereIn('id', $rootIds)->whereNull('parent_id')->where('is_active', 1)->with(['children'])->order('sort', 'asc')->select()
            : Category::where('id', 0)->select();
        $categories = array_map(fn ($c) => ApiResource::category($c), $cats->all());
        if ((int) $req->param('page', 1) <= 1 && !$req->param('q') && !$req->param('category') && $tab === 'all' && !$sort) {
            $adsByBase = $this->sampleFloorAds();
            foreach ($cats as $i => $cat) {
                $ids = array_merge([$cat->id], $cat->children ? $cat->children->column('id') : []);
                $items = Product::publishedQuery()->with(['category', 'user', 'company'])
                    ->where('is_sample', 1)
                    ->whereIn('category_id', $ids)
                    ->order('sales', 'desc')
                    ->limit(20)
                    ->select();
                $spec = FloorSlots::MAP[(string) $cat->name] ?? null;
                $base = $spec ? 'sample_'.$spec[0] : '';
                $ads = $base !== '' ? ($adsByBase[$base] ?? []) : [];
                if ($items->isEmpty() && !$ads) {
                    continue;
                }
                $children = array_values(array_filter(
                    floor_children($cat),
                    static fn ($ch) => isset($sampleIdSet[(int) ($ch['id'] ?? 0)])
                ));
                $floors[] = [
                    'floor_no' => $i + 1,
                    'category' => ApiResource::category($cat),
                    'children' => $children,
                    'products' => array_map(fn ($p) => ApiResource::product($p), $items->all()),
                    'ads' => $ads,
                ];
            }
        }
        $hot = array_map(
            fn ($p) => ApiResource::product($p),
            Product::publishedQuery()->with(['category', 'user', 'company'])->where('is_sample', 1)->order('sales', 'desc')->limit(10)->select()->all()
        );
        $filterLabel = $req->param('q') ?: '';
        if ($catId = (int) $req->param('category')) {
            $filterLabel = (string) (Category::where('id', $catId)->value('name') ?: '分类样品');
        }

        return json(array_merge($result, [
            'floors' => $floors,
            'tabs' => [],
            'categories' => $categories,
            'brands' => [],
            'hot' => $hot,
            'filter_label' => $filterLabel ?: '全部样品',
            'active_root_id' => $catId ? category_root_id($catId) : null,
        ]));
    }

    public function show($id): Response
    {
        $item = Product::publishedQuery()->with(['category', 'user', 'company'])->find($id);
        $ownerPreview = false;
        if (!$item) {
            $owner = optional_request_user();
            if ($owner) {
                $item = Product::with(['category', 'user', 'company'])->where('user_id', $owner->id)->find($id);
                $ownerPreview = (bool) $item;
            }
        }
        if (!$item) {
            return json_error('Not found', 404);
        }
        if (!$ownerPreview) {
            $item->views = (int) $item->views + 1;
            $item->save();
        }
        $avg = ProductReview::where('product_id', $item->id)->where('status', 'published')->avg('rating');
        $count = ProductReview::where('product_id', $item->id)->where('status', 'published')->count();
        $data = ApiResource::product($item);
        if ($ownerPreview) {
            $data['owner_preview'] = true;
        }
        $reviewsMeta = ['avg_rating' => round((float) $avg, 1), 'count' => (int) $count];
        $data['reviews_meta'] = $reviewsMeta;
        $data['shop_products'] = \app\common\ShopShowcase::forProduct($item);

        return json(['data' => $data, 'reviews_meta' => $reviewsMeta]);
    }

    /** 样品专区各分类楼层的 5 个商品位。编码 sample_floor_mono_1 … sample_floor_resin_5。 */
    private function sampleFloorAds(): array
    {
        $now = date('Y-m-d H:i:s');
        $ads = Ad::where('is_active', 1)->whereLike('position', 'sample_floor_%')->order('sort', 'asc')->order('id', 'asc')->select();
        $byBase = [];
        foreach ($ads as $ad) {
            if (!Ad::usableImage($ad->image)) {
                continue;
            }
            if ($ad->starts_at && (string) $ad->starts_at > $now) {
                continue;
            }
            if ($ad->ends_at && (string) $ad->ends_at < $now) {
                continue;
            }
            $pos = (string) $ad->position;
            if (!preg_match('/^(sample_floor_[a-z]+)_(\d)$/', $pos, $m)) {
                continue;
            }
            $slot = (int) $m[2];
            if ($slot < 1 || $slot > 5 || isset($byBase[$m[1]][$slot])) {
                continue;
            }
            $byBase[$m[1]][$slot] = [
                'id' => (int) $ad->id,
                'slot' => $slot,
                'title' => (string) $ad->title,
                'image' => (string) $ad->image,
                'link' => (string) ($ad->link ?? ''),
            ];
        }
        $out = [];
        foreach ($byBase as $base => $slots) {
            ksort($slots);
            $out[$base] = array_values($slots);
        }

        return $out;
    }
}
