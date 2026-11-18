<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Category;
use app\model\Message;
use app\model\Purchase;
use app\model\PurchaseQuote;
use think\Response;

class Buy extends BaseController
{
    public function index(): Response
    {
        $query = Purchase::publishedQuery()->with(['category', 'user']);

        $q = trim((string) $this->request->param('q', ''));
        if ($q !== '') {
            $query->whereLike('title', '%'.$q.'%');
        }
        if ($this->request->param('category') !== null && $this->request->param('category') !== '') {
            $catId = (int) $this->request->param('category');
            $childIds = Category::where('parent_id', $catId)->column('id');
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        if ($this->request->param('area')) {
            $query->whereLike('area', '%'.$this->request->param('area').'%');
        }
        if ($this->request->param('urgent')) {
            $query->where('is_urgent', 1);
        }

        $sort = (string) $this->request->param('sort', 'refresh');
        match ($sort) {
            'budget_desc' => $query->order('budget', 'desc'),
            'hits' => $query->order('views', 'desc'),
            default => $query->order('is_urgent', 'desc')->order('level', 'desc')->order('refreshed_at', 'desc'),
        };

        $cats = Category::whereNull('parent_id')->where('module', 'sell')->where('is_active', 1)
            ->with(['children'])->order('sort', 'asc')->select();
        $categories = [];
        foreach ($cats as $c) {
            $children = [];
            foreach ($c->children ?? [] as $ch) {
                $children[] = ['id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug];
            }
            $categories[] = ['id' => (int) $c->id, 'name' => $c->name, 'slug' => $c->slug, 'children' => $children];
        }

        $showFloors = $q === ''
            && !$this->request->param('category')
            && !$this->request->param('area')
            && !$this->request->param('urgent')
            && (int) $this->request->param('page/d', 1) <= 1;

        $floors = [];
        $urgent = [];
        if ($showFloors) {
            foreach ($cats as $index => $cat) {
                $ids = array_merge([(int) $cat->id], array_map('intval', array_column(($cat->children ? $cat->children->toArray() : []), 'id')));
                $items = Purchase::publishedQuery()->with(['category', 'user'])
                    ->whereIn('category_id', $ids)
                    ->order('is_urgent', 'desc')->order('refreshed_at', 'desc')
                    ->limit(8)->select();
                $floors[] = [
                    'floor_no' => $index + 1,
                    'category' => ['id' => (int) $cat->id, 'name' => $cat->name, 'slug' => $cat->slug],
                    'children' => array_map(static fn ($ch) => ['id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug], $cat->children ? $cat->children->all() : []),
                    'items' => array_map(static fn ($p) => $p->toListArray(), $items->all()),
                ];
            }
            $urgentRows = Purchase::publishedQuery()->with(['category', 'user'])
                ->where('is_urgent', 1)->order('refreshed_at', 'desc')->limit(8)->select();
            $urgent = array_map(static fn ($p) => $p->toListArray(), $urgentRows->all());
        }

        $paginator = $query->paginate(['list_rows' => 20, 'page' => max(1, (int) $this->request->param('page/d', 1))]);
        $payload = paginate_laravel($paginator, static fn ($p) => $p->toListArray());
        $payload['filters'] = [
            'areas' => array_values(Purchase::publishedQuery()->whereNotNull('area')->distinct(true)->order('area', 'asc')->limit(50)->column('area')),
        ];
        $payload['categories'] = $categories;
        $payload['floors'] = $floors;
        $payload['urgent'] = $urgent;

        return json($payload);
    }

    public function show($id): Response
    {
        $purchase = Purchase::find($id);
        if (!$purchase || $purchase->status !== 'approved') {
            return json_error('Not Found', 404);
        }
        $purchase->views = (int) $purchase->views + 1;
        $purchase->save();
        $purchase->together(['category', 'user']);

        return json_data($purchase->toListArray() + [
            'content' => $purchase->content,
            'unit' => $purchase->unit,
            'is_urgent' => (bool) $purchase->is_urgent,
            'status' => $purchase->status,
            'views' => (int) $purchase->views,
        ]);
    }

    public function quote($id): Response
    {
        $purchase = Purchase::find($id);
        if (!$purchase || $purchase->status !== 'approved') {
            return json_error('Not Found', 404);
        }
        $data = $this->request->post();
        if (empty($data['content']) || empty($data['contact_name']) || empty($data['contact_phone'])) {
            return json_error('请填写完整报价信息', 422);
        }
        $user = $this->request->user ?? null;
        $quote = PurchaseQuote::create([
            'purchase_id' => $purchase->id,
            'user_id' => $user->id ?? null,
            'price' => $data['price'] ?? null,
            'unit' => $data['unit'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'content' => $data['content'],
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'status' => 'new',
        ]);
        Message::create([
            'user_id' => $purchase->user_id,
            'from_user_id' => $user->id ?? null,
            'title' => '收到采购报价：'.$purchase->title,
            'content' => "报价人：{$data['contact_name']} / {$data['contact_phone']}\n价格：".($data['price'] ?? '面议')."\n".$data['content'],
        ]);

        return json(['data' => ['id' => (int) $quote->id], 'message' => '报价已提交'], 201);
    }

    public function myQuotes(): Response
    {
        $uid = (int) $this->request->user->id;
        $purchaseIds = Purchase::where('user_id', $uid)->column('id');
        $query = PurchaseQuote::whereIn('purchase_id', $purchaseIds ?: [0])
            ->with(['purchase', 'user'])
            ->order('id', 'desc');
        $paginator = $query->paginate(['list_rows' => 20, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, static function ($q) {
            return [
                'id' => (int) $q->id,
                'price' => $q->price !== null ? (string) $q->price : null,
                'unit' => $q->unit,
                'quantity' => $q->quantity,
                'content' => $q->content,
                'contact_name' => $q->contact_name,
                'contact_phone' => $q->contact_phone,
                'status' => $q->status,
                'created_at' => $q->created_at,
                'purchase' => $q->purchase ? ['id' => (int) $q->purchase->id, 'title' => $q->purchase->title] : null,
                'user' => $q->user ? [
                    'id' => (int) $q->user->id,
                    'name' => $q->user->name,
                    'username' => $q->user->username,
                    'company_name' => $q->user->company_name,
                ] : null,
            ];
        }));
    }

    public function updateQuote($id): Response
    {
        $quote = PurchaseQuote::with(['purchase'])->find($id);
        if (!$quote || !$quote->purchase || (int) $quote->purchase->user_id !== (int) $this->request->user->id) {
            return json_error('Forbidden', 403);
        }
        $status = (string) $this->request->post('status', '');
        if (!in_array($status, ['new', 'accepted', 'rejected', 'done'], true)) {
            return json_error('status 无效', 422);
        }
        $quote->status = $status;
        $quote->save();

        return json_message('ok', 200, ['data' => ['id' => (int) $quote->id, 'status' => $quote->status]]);
    }
}
