<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ApiResource;
use app\common\SpreadRank;
use app\model\Ad;
use app\model\Category;
use app\model\Supply;
use think\Response;

class Sell extends BaseController
{
    public function index(): Response
    {
        $req = $this->request;
        $query = Supply::publishedQuery()->with(['category', 'user']);
        $q = trim((string) $req->param('q', ''));
        if ($q !== '') {
            $kw = '%' . $q . '%';
            $query->where(function ($w) use ($kw) {
                $w->whereLike('title', $kw)->whereOr('brand', 'like', $kw)->whereOr('summary', 'like', $kw);
            });
        }
        if ($catId = (int) $req->param('category')) {
            filter_query_by_category($query, $catId, ['title', 'summary', 'brand']);
        }
        if ($area = $req->param('area')) {
            $query->whereLike('area', '%' . $area . '%');
        }
        if ($req->param('elite')) $query->where('is_elite', 1);
        if ($req->param('urgent')) $query->where('is_urgent', 1);
        if ($req->param('vip')) $query->where('level', '>', 0);
        if ($req->param('priced')) $query->whereNotNull('price')->where('price', '>', 0);
        if ($days = (int) $req->param('days')) {
            $days = max(1, min(365, $days));
            $query->where('refreshed_at', '>=', date('Y-m-d H:i:s', strtotime("-{$days} days")));
        }
        $sort = (string) $req->param('sort', 'refresh');
        $per = min(50, max(1, (int) $req->param('per_page', 20)));
        $page = max(1, (int) $req->param('page', 1));
        if ($sort === '' || $sort === 'refresh') {
            if ($q !== '') {
                SpreadRank::apply($query, 'sell', $q);
            }
            $result = supply_ranked_sell_page($query, $page, $per);
        } else {
            match ($sort) {
                'price_asc' => $query->order('price', 'asc'),
                'price_desc' => $query->order('price', 'desc'),
                'hits' => $query->order('views', 'desc'),
                'vip' => supply_natural_order($query),
                'amount_desc' => $query->order('amount', 'desc'),
                'amount_asc' => $query->order('amount', 'asc'),
                'min_desc' => $query->order('min_amount', 'desc'),
                'min_asc' => $query->order('min_amount', 'asc'),
                default => supply_natural_order($query),
            };
            $paginator = $query->paginate(['list_rows' => $per, 'page' => $page]);
            $result = paginate_laravel($paginator, fn ($s) => ApiResource::supply($s));
        }

        $areas = Supply::publishedQuery()->whereNotNull('area')->where('area', '<>', '')
            ->group('area')->order('area', 'asc')->limit(80)->column('area');
        $categories = Category::where('module', 'sell')->whereNull('parent_id')->where('is_active', 1)
            ->with(['children'])->order('sort', 'asc')->select();
        $catArr = [];
        foreach ($categories as $c) {
            $catArr[] = ApiResource::category($c);
        }

        $showFloors = !$req->param('q') && !$req->param('category') && !$req->param('area')
            && !$req->param('days') && !$req->param('elite') && !$req->param('vip')
            && !$req->param('priced') && !$req->param('urgent') && (int) $req->param('page', 1) <= 1;

        $floors = [];
        $elite = [];
        if ($showFloors) {
            $i = 0;
            foreach ($categories as $cat) {
                $i++;
                $ids = array_merge([$cat->id], $cat->children ? $cat->children->column('id') : []);
                $floorQuery = Supply::publishedQuery()->with(['category', 'user'])->whereIn('category_id', $ids);
                supply_natural_order($floorQuery);
                $items = $floorQuery->limit(8)->select();
                $spec = \app\common\FloorSlots::MAP[(string) $cat->name] ?? null;
                $floors[] = [
                    'floor_no' => $i,
                    'category' => ApiResource::category($cat),
                    'children' => floor_children($cat),
                    'items' => array_map(fn ($s) => ApiResource::supply($s), $items->all()),
                    'elite_ads' => $spec ? $this->eliteAdsFor($spec[2]) : [],
                ];
            }
            $eliteQuery = Supply::publishedQuery()->with(['category', 'user'])->where('is_elite', 1);
            supply_natural_order($eliteQuery);
            $eliteRows = $eliteQuery->limit(8)->select();
            $elite = array_map(fn ($s) => ApiResource::supply($s), $eliteRows->all());
        }

        $activeCat = (int) $req->param('category');

        return json(array_merge($result, [
            'filters' => [
                'areas' => array_values($areas),
                'sort' => $sort,
                'days' => $req->param('days') ? (int) $req->param('days') : null,
                'view' => (string) $req->param('view', 'list'),
            ],
            'areas' => array_values($areas),
            'categories' => $catArr,
            'floors' => $floors,
            'elite' => $elite,
            'elite_ads' => $showFloors ? $this->eliteImageAds() : [],
            'active_root_id' => $activeCat ? category_root_id($activeCat) : null,
        ]));
    }

    public function show($id): Response
    {
        $item = Supply::publishedQuery()->with(['category', 'user'])->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->views = (int)$item->views + 1;
        $item->save();
        $data = ApiResource::supply($item, true);
        $seller = $item->user ?? null;
        $data['shop_products'] = \app\common\ShopShowcase::forSeller(
            (int) ($item->user_id ?? 0),
            0,
            $seller,
            \app\common\ShopShowcase::companyIdFor(null, $seller) ?: null,
        );

        return json_data($data);
    }

    public function compare(): Response
    {
        $ids = array_filter(array_map('intval', explode(',', (string)$this->request->param('ids', ''))));
        $ids = array_slice($ids, 0, 5);
        if (!$ids) return json_data([]);
        $rows = Supply::publishedQuery()->with(['category', 'user'])->whereIn('id', $ids)->select();
        return json_data(array_map(fn ($s) => ApiResource::supply($s, true), $rows->all()));
    }

    /** 产品分类「推荐供应」4 张广告图。编码 sell_elite_1 … sell_elite_4，也认 sell_elite + 排序。 */
    private function eliteImageAds(): array
    {
        $codes = ['sell_elite'];
        for ($i = 1; $i <= 4; $i++) {
            $codes[] = 'sell_elite_' . $i;
        }
        $now = date('Y-m-d H:i:s');
        $ads = Ad::where('is_active', 1)->whereIn('position', $codes)->order('sort', 'asc')->order('id', 'asc')->select();
        $bySlot = [];
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
            if (preg_match('/_(\d)$/', $pos, $m)) {
                $slot = (int) $m[1];
            } else {
                $slot = max(1, min(4, (int) ($ad->sort ?: 1)));
            }
            if ($slot < 1 || $slot > 4 || isset($bySlot[$slot])) {
                continue;
            }
            $bySlot[$slot] = [
                'id' => (int) $ad->id,
                'slot' => $slot,
                'title' => (string) $ad->title,
                'image' => (string) $ad->image,
                'link' => (string) ($ad->link ?? ''),
            ];
        }
        ksort($bySlot);

        return array_values($bySlot);
    }

    /** 产品分类某一大类的推荐供应广告，编码 elite_mono_1 … elite_mono_5。 */
    private function eliteAdsFor(string $base): array
    {
        $codes = [];
        for ($i = 1; $i <= 5; $i++) {
            $codes[] = \app\common\FloorSlots::eliteCode($base, $i);
        }
        $now = date('Y-m-d H:i:s');
        $ads = Ad::where('is_active', 1)->whereIn('position', $codes)->order('sort', 'asc')->order('id', 'asc')->select();
        $bySlot = [];
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
            $parsed = \app\common\FloorSlots::parseFloor((string) $ad->position);
            $slot = $parsed['slot'];
            if ($slot < 1 || $slot > 5 || isset($bySlot[$slot])) {
                continue;
            }
            $bySlot[$slot] = [
                'id' => (int) $ad->id,
                'slot' => $slot,
                'title' => (string) $ad->title,
                'image' => (string) $ad->image,
                'link' => (string) ($ad->link ?? ''),
            ];
        }
        ksort($bySlot);

        return array_values($bySlot);
    }
}