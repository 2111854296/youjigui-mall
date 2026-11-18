<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ApiResource;
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
        if ($q = $req->param('q')) {
            $kw = '%' . $q . '%';
            $query->whereLike('title', $kw);
        }
        if ($catId = (int)$req->param('category')) {
            $childIds = Category::where('parent_id', $catId)->column('id');
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        if ($area = $req->param('area')) $query->whereLike('area', '%' . $area . '%');
        if ($req->param('urgent')) $query->where('is_urgent', 1);
        $sort = (string)$req->param('sort', 'refresh');
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
                    ->order('refreshed_at', 'desc')->limit(8)->select();
                $floors[] = [
                    'floor_no' => $i,
                    'category' => ApiResource::category($cat),
                    'items' => array_map(fn ($p) => ApiResource::purchase($p), $items->all()),
                ];
            }
            $urgentRows = Purchase::publishedQuery()->with(['category','user'])->where('is_urgent', 1)
                ->order('refreshed_at', 'desc')->limit(8)->select();
            $urgent = array_map(fn ($p) => ApiResource::purchase($p), $urgentRows->all());
        }

        return json(array_merge($result, [
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
        $quote = PurchaseQuote::create([
            'purchase_id' => $purchase->id,
            'user_id' => $user->id ?? null,
            'company_name' => $data['company_name'] ?? ($user->company_name ?? null),
            'contact' => $data['contact'] ?? ($user->name ?? null),
            'phone' => $data['phone'] ?? ($user->mobile ?? null),
            'price' => $data['price'] ?? null,
            'content' => $data['content'] ?? '',
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
        $user = $this->request->user;
        $purchaseIds = Purchase::where('user_id', $user->id)->column('id');
        $paginator = PurchaseQuote::whereIn('purchase_id', $purchaseIds ?: [0])
            ->order('id', 'desc')->paginate(['list_rows' => 20]);
        return json(paginate_laravel($paginator));
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
        $quote->save();
        return json_data($quote->toArray());
    }
}