<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ApiResource;
use app\model\Category;
use app\model\Supply;
use think\Response;

class Sell extends BaseController
{
    public function index(): Response
    {
        $req = $this->request;
        $query = Supply::publishedQuery()->with(['category', 'user']);
        if ($q = $req->param('q')) {
            $kw = '%' . $q . '%';
            $query->where(function ($w) use ($kw) {
                $w->whereLike('title', $kw)->whereOr('brand', 'like', $kw)->whereOr('summary', 'like', $kw);
            });
        }
        if ($catId = (int) $req->param('category')) {
            $childIds = Category::where('parent_id', $catId)->column('id');
            $query->whereIn('category_id', array_merge([$catId], $childIds));
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
        match ($sort) {
            'price_asc' => $query->order('price', 'asc'),
            'price_desc' => $query->order('price', 'desc'),
            'hits' => $query->order('views', 'desc'),
            'vip' => $query->order('level', 'desc')->order('refreshed_at', 'desc'),
            'amount_desc' => $query->order('amount', 'desc'),
            'amount_asc' => $query->order('amount', 'asc'),
            'min_desc' => $query->order('min_amount', 'desc'),
            'min_asc' => $query->order('min_amount', 'asc'),
            default => $query->order('level', 'desc')->order('refreshed_at', 'desc'),
        };
        $per = min(50, max(1, (int) $req->param('per_page', 20)));
        $paginator = $query->paginate(['list_rows' => $per]);
        $result = paginate_laravel($paginator, fn ($s) => ApiResource::supply($s));

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
                $items = Supply::publishedQuery()->with(['category', 'user'])->whereIn('category_id', $ids)
                    ->order('level', 'desc')->order('refreshed_at', 'desc')->limit(8)->select();
                $floors[] = [
                    'floor_no' => $i,
                    'category' => ApiResource::category($cat),
                    'children' => array_map(fn ($ch) => ['id' => (int)$ch->id, 'name' => $ch->name, 'slug' => $ch->slug], $cat->children ? $cat->children->all() : []),
                    'items' => array_map(fn ($s) => ApiResource::supply($s), $items->all()),
                ];
            }
            $eliteRows = Supply::publishedQuery()->with(['category', 'user'])->where('is_elite', 1)
                ->order('level', 'desc')->order('refreshed_at', 'desc')->limit(8)->select();
            $elite = array_map(fn ($s) => ApiResource::supply($s), $eliteRows->all());
        }

        return json(array_merge($result, [
            'areas' => array_values($areas),
            'categories' => $catArr,
            'floors' => $floors,
            'elite' => $elite,
        ]));
    }

    public function show($id): Response
    {
        $item = Supply::publishedQuery()->with(['category', 'user'])->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->views = (int)$item->views + 1;
        $item->save();
        return json_data(ApiResource::supply($item, true));
    }

    public function compare(): Response
    {
        $ids = array_filter(array_map('intval', explode(',', (string)$this->request->param('ids', ''))));
        $ids = array_slice($ids, 0, 5);
        if (!$ids) return json_data([]);
        $rows = Supply::publishedQuery()->with(['category', 'user'])->whereIn('id', $ids)->select();
        return json_data(array_map(fn ($s) => ApiResource::supply($s, true), $rows->all()));
    }
}