<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Category;
use app\model\Supply;
use think\Response;

class Sell extends BaseController
{
    private function pageRows(int $default = 20, int $max = 50): array
    {
        $per = min($max, max(1, (int) $this->request->param('per_page/d', $default)));
        $page = max(1, (int) $this->request->param('page/d', 1));

        return ['list_rows' => $per, 'page' => $page];
    }

    private function catTree(string $module = 'sell'): array
    {
        $cats = Category::where('module', $module)->whereNull('parent_id')
            ->with(['children'])
            ->order('sort', 'asc')
            ->select();
        $out = [];
        foreach ($cats as $c) {
            $children = [];
            foreach ($c->children ?? [] as $ch) {
                $children[] = ['id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug];
            }
            $out[] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'children' => $children,
            ];
        }

        return [$cats, $out];
    }

    public function index(): Response
    {
        $query = Supply::publishedQuery()->with(['category', 'user']);

        $q = trim((string) $this->request->param('q', ''));
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like) {
                $w->whereLike('title', $like)
                    ->whereOr('brand', 'like', $like)
                    ->whereOr('summary', 'like', $like);
            });
        }
        if ($this->request->param('category') !== null && $this->request->param('category') !== '') {
            $catId = (int) $this->request->param('category');
            $childIds = Category::where('parent_id', $catId)->column('id');
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        if ($this->request->param('area')) {
            $query->whereLike('area', '%'.$this->request->param('area').'%');
        }
        if ($this->request->param('elite')) {
            $query->where('is_elite', 1);
        }
        if ($this->request->param('urgent')) {
            $query->where('is_urgent', 1);
        }
        if ($this->request->param('vip')) {
            $query->where('level', '>', 0);
        }
        if ($this->request->param('priced')) {
            $query->whereNotNull('price')->where('price', '>', 0);
        }
        if ($this->request->param('days')) {
            $days = max(1, min(365, (int) $this->request->param('days')));
            $query->where('refreshed_at', '>=', date('Y-m-d H:i:s', time() - $days * 86400));
        }

        $sort = (string) $this->request->param('sort', 'refresh');
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

        $paginator = $query->paginate($this->pageRows());
        [$catModels, $categories] = $this->catTree('sell');

        $areas = Supply::publishedQuery()
            ->whereNotNull('area')
            ->where('area', '<>', '')
            ->distinct(true)
            ->order('area', 'asc')
            ->limit(80)
            ->column('area');

        $showFloors = $q === ''
            && !$this->request->param('category')
            && !$this->request->param('area')
            && !$this->request->param('days')
            && !$this->request->param('elite')
            && !$this->request->param('vip')
            && !$this->request->param('priced')
            && !$this->request->param('urgent')
            && (int) $this->request->param('page/d', 1) <= 1;

        $floors = [];
        $elite = [];
        if ($showFloors) {
            foreach ($catModels as $index => $cat) {
                $ids = array_merge([(int) $cat->id], array_map('intval', array_column(($cat->children ? $cat->children->toArray() : []), 'id')));
                $items = Supply::publishedQuery()->with(['category', 'user'])
                    ->whereIn('category_id', $ids)
                    ->order('level', 'desc')->order('refreshed_at', 'desc')
                    ->limit(8)->select();
                $floors[] = [
                    'floor_no' => $index + 1,
                    'category' => ['id' => (int) $cat->id, 'name' => $cat->name, 'slug' => $cat->slug],
                    'children' => array_map(static fn ($ch) => ['id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug], $cat->children ? $cat->children->all() : []),
                    'items' => array_map(static fn ($s) => $s->toListArray(), $items->all()),
                ];
            }
            $eliteRows = Supply::publishedQuery()->with(['category', 'user'])
                ->where('is_elite', 1)->order('level', 'desc')->order('refreshed_at', 'desc')->limit(8)->select();
            $elite = array_map(static fn ($s) => $s->toListArray(), $eliteRows->all());
        }

        $payload = paginate_laravel($paginator, static fn ($s) => $s->toListArray());
        $payload['filters'] = [
            'areas' => array_values($areas),
            'sort' => $sort,
            'days' => $this->request->param('days') ? (int) $this->request->param('days') : null,
            'view' => (string) $this->request->param('view', 'list'),
        ];
        $payload['categories'] = $categories;
        $payload['floors'] = $floors;
        $payload['elite'] = $elite;

        return json($payload);
    }

    public function show($id): Response
    {
        $supply = Supply::find($id);
        if (!$supply || $supply->status !== 'approved') {
            return json_error('Not Found', 404);
        }
        $supply->views = (int) $supply->views + 1;
        $supply->save();
        $supply->together(['category', 'user']);

        return json_data($supply->toListArray() + [
            'content' => $supply->content,
            'thumb' => $supply->thumb,
            'thumb1' => $supply->thumb1,
            'thumb2' => $supply->thumb2,
            'brand' => $supply->brand,
            'area' => $supply->area,
            'min_amount' => $supply->min_amount,
            'amount' => $supply->amount,
            'status' => $supply->status,
        ]);
    }

    public function compare(): Response
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $this->request->param('ids', ''))))));
        $ids = array_slice($ids, 0, 5);
        if (!$ids) {
            return json_error('请选择要对比的供应', 422);
        }
        $items = Supply::publishedQuery()->with(['category', 'user'])->whereIn('id', $ids)->select();

        return json_data(array_map(static fn ($s) => $s->toListArray(), $items->all()));
    }
}
