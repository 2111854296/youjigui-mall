<?php
namespace app\controller\api\v1;
use app\BaseController; use app\common\ApiResource;
use app\model\CartItem; use app\model\ChatMessage; use app\model\Message; use app\model\Order; use app\model\OrderItem;
use app\model\Product; use app\model\Purchase; use app\model\Supply;
use think\facade\Db; use think\Response;

class Member extends BaseController
{
    public function dashboard(): Response
    {
        $user = $this->request->user;
        return json_data([
            'user' => $user->toApiArray(),
            'stats' => [
                'supplies' => Supply::where('user_id', $user->id)->count(),
                'purchases' => Purchase::where('user_id', $user->id)->count(),
                'orders' => Order::where('user_id', $user->id)->count(),
                'cart' => CartItem::where('user_id', $user->id)->count(),
                'messages' => Message::where('user_id', $user->id)->where('is_read', 0)->count(),
                'chats' => ChatMessage::where('to_user_id', $user->id)->where('is_read', 0)->count(),
                'deposit' => (string)($user->deposit ?? 0),
                'points' => (int)$user->points,
                'balance' => (string)$user->balance,
            ],
        ]);
    }

    public function updateProfile(): Response
    {
        $user = $this->request->user;
        $data = $this->request->only(['name','email','mobile','company_name','password','password_confirmation']);
        if (empty($data['name']) || empty($data['email'])) return json_error('姓名和邮箱必填', 422);
        if (!empty($data['password'])) {
            if (($data['password_confirmation'] ?? null) !== null && $data['password'] !== $data['password_confirmation']) {
                return json_error('两次密码不一致', 422);
            }
            $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->mobile = $data['mobile'] ?? $user->mobile;
        $user->company_name = $data['company_name'] ?? $user->company_name;
        $user->save();
        return json_data($user->toApiArray());
    }

    public function supplies(): Response
    {
        $paginator = Supply::where('user_id', $this->request->user->id)->with(['category'])->order('id', 'desc')->paginate(['list_rows' => 15]);
        return json(paginate_laravel($paginator, fn($s) => ApiResource::supply($s)));
    }
    public function showSupply($id): Response
    {
        $item = Supply::where('user_id', $this->request->user->id)->with(['category'])->find($id);
        if (!$item) return json_error('Not found', 404);
        return json_data(ApiResource::supply($item, true));
    }
    public function storeSupply(): Response
    {
        if ($e = ApiResource::requireEnterprise()) return $e;
        $data = $this->request->only(['title','thumb','thumb1','thumb2','price','unit','brand','area','summary','content','category_id','min_amount','amount','days','attr1_name','attr1_value','attr2_name','attr2_value','attr3_name','attr3_value','is_urgent','is_elite']);
        if (empty($data['title'])) return json_error('标题必填', 422);
        $data['user_id'] = $this->request->user->id;
        $data['status'] = 'pending';
        $data['refreshed_at'] = date('Y-m-d H:i:s');
        $item = Supply::create($data);
        return json(['data' => ApiResource::supply($item->refresh())], 201);
    }
    public function updateSupply($id): Response
    {
        if ($e = ApiResource::requireEnterprise()) return $e;
        $item = Supply::where('user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->save($this->request->only(['title','thumb','thumb1','thumb2','price','unit','brand','area','summary','content','category_id','min_amount','amount','days','attr1_name','attr1_value','attr2_name','attr2_value','attr3_name','attr3_value','is_urgent','is_elite']));
        return json_data(ApiResource::supply($item->refresh(), true));
    }
    public function destroySupply($id): Response
    {
        if ($e = ApiResource::requireEnterprise()) return $e;
        $item = Supply::where('user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->delete();
        return json_message('ok');
    }
    public function refreshSupply($id): Response
    {
        if ($e = ApiResource::requireEnterprise()) return $e;
        $item = Supply::where('user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->refreshed_at = date('Y-m-d H:i:s');
        $item->save();
        return json_data(ApiResource::supply($item));
    }

    public function purchases(): Response
    {
        $paginator = Purchase::where('user_id', $this->request->user->id)->with(['category'])->order('id', 'desc')->paginate(['list_rows' => 15]);
        return json(paginate_laravel($paginator, fn($p) => ApiResource::purchase($p)));
    }
    public function showPurchase($id): Response
    {
        $item = Purchase::where('user_id', $this->request->user->id)->with(['category'])->find($id);
        if (!$item) return json_error('Not found', 404);
        return json_data(ApiResource::purchase($item));
    }
    public function storePurchase(): Response
    {
        $data = $this->request->only(['title','thumb','budget','quantity','unit','area','days','is_urgent','summary','content','category_id']);
        if (empty($data['title'])) return json_error('标题必填', 422);
        $data['user_id'] = $this->request->user->id;
        $data['status'] = 'pending';
        $data['refreshed_at'] = date('Y-m-d H:i:s');
        $item = Purchase::create($data);
        return json(['data' => ApiResource::purchase($item)], 201);
    }
    public function updatePurchase($id): Response
    {
        $item = Purchase::where('user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->save($this->request->only(['title','thumb','budget','quantity','unit','area','days','is_urgent','summary','content','category_id']));
        return json_data(ApiResource::purchase($item->refresh()));
    }
    public function destroyPurchase($id): Response
    {
        $item = Purchase::where('user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->delete();
        return json_message('ok');
    }

    public function orders(): Response
    {
        $paginator = Order::where('user_id', $this->request->user->id)->with(['items'])->order('id', 'desc')->paginate(['list_rows' => 15]);
        return json(paginate_laravel($paginator, fn($o) => ApiResource::order($o)));
    }
    public function showOrder($id): Response
    {
        $order = Order::with(['items','user'])->find($id);
        if (!$order || !$this->canAccessOrder($order)) return json_error('Forbidden', 403);
        return json_data(ApiResource::order($order));
    }
    public function updateOrderStatus($id): Response
    {
        $order = Order::find($id);
        if (!$order || !$this->canAccessOrder($order)) return json_error('Forbidden', 403);
        $status = $this->request->post('status');
        if (!in_array($status, ['shipped','completed','cancelled','paid'], true)) return json_error('无效状态', 422);
        $order->status = $status;
        $order->save();
        return json_data(ApiResource::order($order->refresh()->load('items')));
    }
    public function sellerOrders(): Response
    {
        if ($e = ApiResource::requireEnterprise()) return $e;
        $userId = $this->request->user->id;
        $productIds = Product::where('user_id', $userId)->column('id');
        $orderIds = $productIds ? OrderItem::whereIn('product_id', $productIds)->column('order_id') : [];
        $paginator = Order::whereIn('id', $orderIds ?: [0])->with(['items','user'])->order('id', 'desc')->paginate(['list_rows' => 15]);
        return json(paginate_laravel($paginator, fn($o) => ApiResource::order($o)));
    }

    public function cart(): Response
    {
        $items = CartItem::where('user_id', $this->request->user->id)->order('id', 'desc')->select();
        $data = [];
        foreach ($items as $c) {
            $p = Product::find($c->product_id);
            $data[] = [
                'id' => (int)$c->id,
                'quantity' => (int)$c->quantity,
                'product' => $p ? ApiResource::product($p) : null,
            ];
        }
        return json_data($data);
    }
    public function addCart($product): Response
    {
        $p = Product::where('status', 'approved')->find($product);
        if (!$p) return json_error('商品不存在', 404);
        $qty = max(1, (int)$this->request->post('quantity', 1));
        $item = CartItem::where('user_id', $this->request->user->id)->where('product_id', $product)->find();
        if ($item) { $item->quantity = (int)$item->quantity + $qty; $item->save(); }
        else { $item = CartItem::create(['user_id' => $this->request->user->id, 'product_id' => $product, 'quantity' => $qty]); }
        return json(['data' => $item->toArray()], 201);
    }
    public function updateCart($id): Response
    {
        $item = CartItem::where('user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->quantity = max(1, (int)$this->request->post('quantity', $item->quantity));
        $item->save();
        return json_data($item->toArray());
    }
    public function removeCart($id): Response
    {
        $item = CartItem::where('user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->delete();
        return json_message('ok');
    }
    public function checkout(): Response
    {
        $user = $this->request->user;
        $cart = CartItem::where('user_id', $user->id)->select();
        if ($cart->isEmpty()) return json_error('购物车为空', 422);
        $data = $this->request->post();
        $amount = 0; $lines = [];
        foreach ($cart as $c) {
            $p = Product::find($c->product_id);
            if (!$p) continue;
            $line = (float)$p->price * (int)$c->quantity;
            $amount += $line;
            $lines[] = ['product_id' => $p->id, 'title' => $p->title, 'price' => $p->price, 'quantity' => $c->quantity];
        }
        $order = Order::create([
            'order_no' => 'YG' . date('YmdHis') . random_int(1000, 9999),
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => 'paid',
            'payment_method' => 'alipay_mock',
            'receiver_name' => $data['receiver_name'] ?? $user->name,
            'receiver_phone' => $data['receiver_phone'] ?? $user->mobile,
            'receiver_address' => $data['receiver_address'] ?? '',
            'remark' => $data['remark'] ?? null,
            'paid_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($lines as $line) {
            OrderItem::create(array_merge($line, ['order_id' => $order->id]));
        }
        CartItem::where('user_id', $user->id)->delete();
        $order = Order::with(['items'])->find($order->id);
        return json(['data' => ApiResource::order($order), 'message' => '下单成功'], 201);
    }

    public function messages(): Response
    {
        $paginator = Message::where('user_id', $this->request->user->id)->order('id', 'desc')->paginate(['list_rows' => 20]);
        return json(paginate_laravel($paginator));
    }
    public function sendMessage(): Response
    {
        $to = (int)$this->request->post('to_user_id');
        $title = $this->request->post('title', '站内信');
        $content = $this->request->post('content', '');
        if (!$to || $content === '') return json_error('参数错误', 422);
        $msg = Message::create([
            'user_id' => $to,
            'from_user_id' => $this->request->user->id,
            'title' => $title,
            'content' => $content,
            'is_read' => 0,
        ]);
        return json(['data' => $msg->toArray()], 201);
    }
    public function readMessage($id): Response
    {
        $msg = Message::where('user_id', $this->request->user->id)->find($id);
        if (!$msg) return json_error('Not found', 404);
        $msg->is_read = 1; $msg->save();
        return json_message('ok');
    }

    private function canAccessOrder($order): bool
    {
        $user = $this->request->user;
        if ((int)$order->user_id === (int)$user->id) return true;
        $productIds = Product::where('user_id', $user->id)->column('id');
        if (!$productIds) return false;
        return OrderItem::where('order_id', $order->id)->whereIn('product_id', $productIds)->count() > 0;
    }
}