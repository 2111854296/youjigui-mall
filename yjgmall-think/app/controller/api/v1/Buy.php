<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ApiResource;
use app\common\SpreadRank;
use app\model\Category;
use app\model\Message;
use app\model\Purchase;
use app\model\PurchaseQuote;
use think\Response;

class Buy extends BaseController
{
    public function index(): Response
    {
        $req = $this->request;
        $query = Purchase::publishedQuery()->with(['category', 'user']);
        $q = trim((string) $req->param('q', ''));
        if ($q !== '') {
            $kw = '%' . $q . '%';
            $query->whereLike('title', $kw);
        }
        if ($catId = (int)$req->param('category')) {
            $query->whereIn('category_id', category_ids_with_children($catId));
        }
        if ($area = $req->param('area')) $query->whereLike('area', '%' . $area . '%');
        if ($req->param('urgent')) $query->where('is_urgent', 1);
        $sort = (string)$req->param('sort', 'refresh');
        if ($q !== '' && ($sort === '' || $sort === 'refresh')) {
            SpreadRank::apply($query, 'buy', $q);
        }
        match ($sort) {
            'budget_desc' => $query->order('budget', 'desc'),
            'budget_asc' => $query->order('budget', 'asc'),
            'hits' => $query->order('views', 'desc'),
            default => $query->order('refreshed_at', 'desc'),
        };
        $paginator = $query->paginate(['list_rows' => 20]);
        $result = paginate_laravel($paginator, fn ($p) => ApiResource::purchase($p));

        $categories = Category::where('module', 'buy')->whereNull('parent_id')->where('is_active', 1)
            ->with(['children'])->order('sort', 'asc')->select();
        if ($categories->isEmpty()) {
            $categories = Category::where('module', 'sell')->whereNull('parent_id')->where('is_active', 1)
                ->with(['children'])->order('sort', 'asc')->select();
        }
        $floors = [];
        $urgent = [];
        $showFloors = !$req->param('q') && !$req->param('category') && !$req->param('area')
            && !$req->param('urgent') && (int)$req->param('page', 1) <= 1;
        if ($showFloors) {
            $i = 0;
            foreach ($categories as $cat) {
                $i++;
                $ids = array_merge([$cat->id], $cat->children ? $cat->children->column('id') : []);
                $items = Purchase::publishedQuery()->with(['category','user'])->whereIn('category_id', $ids)
                    ->order('refreshed_at', 'desc')->limit(32)->select();
                $floors[] = [
                    'floor_no' => $i,
                    'category' => ApiResource::category($cat),
                    'children' => floor_children($cat),
                    'items' => array_map(fn ($p) => ApiResource::purchase($p), $items->all()),
                ];
            }
            $urgentRows = Purchase::publishedQuery()->with(['category','user'])->where('is_urgent', 1)
                ->order('refreshed_at', 'desc')->limit(8)->select();
            $urgent = array_map(fn ($p) => ApiResource::purchase($p), $urgentRows->all());
        }

        $areas = Purchase::publishedQuery()->whereNotNull('area')->where('area', '<>', '')
            ->group('area')->order('area', 'asc')->limit(50)->column('area');

        return json(array_merge($result, [
            'filters' => [
                'areas' => array_values($areas),
            ],
            'categories' => array_map(fn ($c) => ApiResource::category($c), $categories->all()),
            'floors' => $floors,
            'urgent' => $urgent,
        ]));
    }

    public function show($id): Response
    {
        $item = Purchase::publishedQuery()->with(['category', 'user'])->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->views = (int)$item->views + 1;
        $item->save();
        $item->quotes_count = PurchaseQuote::where('purchase_id', $item->id)->count();
        return json_data(ApiResource::purchase($item));
    }

    public function quote($id): Response
    {
        $purchase = Purchase::publishedQuery()->find($id);
        if (!$purchase) return json_error('Not found', 404);
        $data = $this->request->post();
        $user = $this->request->user ?? null;
        $contactName = $data['contact_name'] ?? $data['contact'] ?? ($user->name ?? null);
        $contactPhone = $data['contact_phone'] ?? $data['phone'] ?? ($user->mobile ?? null);
        $quote = PurchaseQuote::create([
            'purchase_id' => $purchase->id,
            'user_id' => $user->id ?? null,
            'price' => $data['price'] ?? null,
            'unit' => $data['unit'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'content' => $data['content'] ?? '',
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'status' => 'new',
        ]);
        if ($purchase->user_id) {
            Message::create([
                'user_id' => $purchase->user_id,
                'from_user_id' => $user->id ?? null,
                'title' => '收到新报价：' . $purchase->title,
                'content' => $data['content'] ?? '',
                'is_read' => 0,
            ]);
        }
        return json(['data' => $quote->toArray(), 'message' => '报价已提交'], 201);
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
                'is_read' => (int) ($q->is_read ?? 0),
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
        $user = $this->request->user;
        $quote = PurchaseQuote::find($id);
        if (!$quote) return json_error('Not found', 404);
        $purchase = Purchase::find($quote->purchase_id);
        if (!$purchase || (int)$purchase->user_id !== (int)$user->id) return json_error('Forbidden', 403);
        $status = $this->request->post('status');
        if (!in_array($status, ['new','accepted','rejected','done'], true)) return json_error('无效状态', 422);
        $quote->status = $status;
        $quote->is_read = 1;
        $quote->save();
        return json_data($quote->toArray());
    }

    public function readQuote($id): Response
    {
        $user = $this->request->user;
        $quote = PurchaseQuote::find($id);
        if (!$quote) return json_error('Not found', 404);
        $purchase = Purchase::find($quote->purchase_id);
        if (!$purchase || (int) $purchase->user_id !== (int) $user->id) return json_error('Forbidden', 403);
        if ((int) $quote->is_read !== 1) {
            $quote->is_read = 1;
            $quote->save();
            mark_related_messages_read((int) $user->id, [(string) $purchase->title]);
        }
        return json_message('ok');
    }
}