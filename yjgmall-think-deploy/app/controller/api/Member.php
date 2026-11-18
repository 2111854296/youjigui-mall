<?php

namespace app\controller\api;

use app\BaseController;
use app\model\CartItem;
use app\model\Message;
use app\model\Order;
use app\model\OrderItem;
use app\model\Product;
use app\model\Purchase;
use app\model\Supply;
use app\model\User;
use think\facade\Db;
use think\Response;

class Member extends BaseController
{
    private function user(): User
    {
        return $this->request->user;
    }

    private function ensureEnterprise(): ?Response
    {
        if (!$this->user()->isEnterpriseMember()) {
            return json_error('仅企业会员可操作', 403);
        }

        return null;
    }

    public function dashboard(): Response
    {
        $user = $this->user();
        $uid = (int) $user->id;

        return json_data([
            'user' => $user->toApiArray(),
            'stats' => [
                'supplies' => (int) Supply::where('user_id', $uid)->count(),
                'purchases' => (int) Purchase::where('user_id', $uid)->count(),
                'orders' => (int) Order::where('user_id', $uid)->count(),
                'cart' => (int) CartItem::where('user_id', $uid)->count(),
                'messages' => (int) Message::where('user_id', $uid)->where('is_read', 0)->count(),
                'chats' => 0,
                'deposit' => (string) ($user->deposit ?? 0),
                'points' => (int) $user->points,
                'balance' => (string) $user->balance,
            ],
        ]);
    }

    public function updateProfile(): Response
    {
        $user = $this->user();
        $data = $this->request->put() ?: $this->request->post();
        if (empty($data['name']) || empty($data['email'])) {
            return json_error('姓名和邮箱必填', 422);
        }
        if (User::where('email', $data['email'])->where('id', '<>', $user->id)->find()) {
            return json_error('邮箱已存在', 422);
        }
        if (!empty($data['mobile']) && User::where('mobile', $data['mobile'])->where('id', '<>', $user->id)->find()) {
            return json_error('手机号已存在', 422);
        }
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->mobile = $data['mobile'] ?? $user->mobile;
        $user->company_name = $data['company_name'] ?? $user->company_name;
        if (!empty($data['password'])) {
            if (($data['password_confirmation'] ?? null) !== null && $data['password'] !== $data['password_confirmation']) {
                return json_error('两次密码不一致', 422);
            }
            $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $user->save();

        return json_data($user->toApiArray());
    }

    public function supplies(): Response
    {
        $paginator = Supply::where('user_id', $this->user()->id)->order('id', 'desc')
            ->paginate(['list_rows' => 15, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, static fn ($s) => $s->toListArray()));
    }

    public function showSupply($id): Response
    {
        $supply = Supply::with(['category'])->find($id);
        if (!$supply || (int) $supply->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }

        return json_data($supply->toListArray() + ['content' => $supply->content, 'status' => $supply->status]);
    }

    public function storeSupply(): Response
    {
        if ($err = $this->ensureEnterprise()) {
            return $err;
        }
        $data = $this->supplyInput();
        if (empty($data['title'])) {
            return json_error('标题必填', 422);
        }
        $item = Supply::create(array_merge($data, [
            'user_id' => $this->user()->id,
            'status' => 'pending',
            'refreshed_at' => date('Y-m-d H:i:s'),
        ]));

        return json(['data' => $item->toListArray()], 201);
    }

    public function updateSupply($id): Response
    {
        if ($err = $this->ensureEnterprise()) {
            return $err;
        }
        $supply = Supply::find($id);
        if (!$supply || (int) $supply->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }
        $supply->save($this->supplyInput());

        return json_data($supply->fresh()->toListArray());
    }

    public function destroySupply($id): Response
    {
        if ($err = $this->ensureEnterprise()) {
            return $err;
        }
        $supply = Supply::find($id);
        if (!$supply || (int) $supply->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }
        $supply->delete();

        return json_message('ok');
    }

    public function refreshSupply($id): Response
    {
        if ($err = $this->ensureEnterprise()) {
            return $err;
        }
        $supply = Supply::find($id);
        if (!$supply || (int) $supply->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }
        $supply->refreshed_at = date('Y-m-d H:i:s');
        $supply->save();

        return json_data($supply->toListArray());
    }

    public function purchases(): Response
    {
        $paginator = Purchase::where('user_id', $this->user()->id)->order('id', 'desc')
            ->paginate(['list_rows' => 15, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, static fn ($p) => $p->toListArray()));
    }

    public function showPurchase($id): Response
    {
        $purchase = Purchase::with(['category'])->find($id);
        if (!$purchase || (int) $purchase->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }

        return json_data($purchase->toListArray() + ['content' => $purchase->content, 'status' => $purchase->status]);
    }

    public function storePurchase(): Response
    {
        $data = $this->purchaseInput();
        if (empty($data['title'])) {
            return json_error('标题必填', 422);
        }
        $item = Purchase::create(array_merge($data, [
            'user_id' => $this->user()->id,
            'status' => 'pending',
            'refreshed_at' => date('Y-m-d H:i:s'),
        ]));

        return json(['data' => $item->toListArray()], 201);
    }

    public function updatePurchase($id): Response
    {
        $purchase = Purchase::find($id);
        if (!$purchase || (int) $purchase->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }
        $purchase->save($this->purchaseInput());

        return json_data($purchase->fresh()->toListArray());
    }

    public function destroyPurchase($id): Response
    {
        $purchase = Purchase::find($id);
        if (!$purchase || (int) $purchase->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }
        $purchase->delete();

        return json_message('ok');
    }

    public function orders(): Response
    {
        $paginator = Order::with(['items'])->where('user_id', $this->user()->id)->order('id', 'desc')
            ->paginate(['list_rows' => 15, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, static fn ($o) => $o->toListArray()));
    }

    public function showOrder($id): Response
    {
        $order = Order::with(['items', 'user'])->find($id);
        if (!$order || !$this->canAccessOrder($order)) {
            return json_error('Forbidden', 403);
        }

        return json_data($order->toListArray());
    }

    public function updateOrderStatus($id): Response
    {
        $order = Order::find($id);
        if (!$order) {
            return json_error('Not Found', 404);
        }
        $action = (string) $this->request->post('action', '');
        $uid = (int) $this->user()->id;
        $isBuyer = (int) $order->user_id === $uid;
        $isSeller = $this->isOrderSeller($order);

        if ($action === 'ship') {
            if (!$isSeller || !in_array($order->status, ['paid', 'pending'], true)) {
                return json_error('当前状态不可发货', 422);
            }
            $order->status = 'shipped';
        } elseif ($action === 'confirm') {
            if (!$isBuyer || !in_array($order->status, ['shipped', 'paid'], true)) {
                return json_error('当前状态不可确认收货', 422);
            }
            $order->status = 'completed';
        } elseif ($action === 'cancel') {
            if (!($isBuyer || $isSeller) || !in_array($order->status, ['pending', 'paid'], true)) {
                return json_error('当前状态不可取消', 422);
            }
            $order->status = 'cancelled';
        } else {
            return json_error('action 无效', 422);
        }
        $order->save();

        return json_message('ok', 200, ['data' => $order->fresh(['items', 'user'])->toListArray()]);
    }

    public function sellerOrders(): Response
    {
        if ($err = $this->ensureEnterprise()) {
            return $err;
        }
        $productIds = Product::where('user_id', $this->user()->id)->column('id');
        $orderIds = $productIds
            ? OrderItem::whereIn('product_id', $productIds)->column('order_id')
            : [];
        $paginator = Order::with(['items', 'user'])->whereIn('id', $orderIds ?: [0])->order('id', 'desc')
            ->paginate(['list_rows' => 15, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, static fn ($o) => $o->toListArray()));
    }

    public function cart(): Response
    {
        $items = CartItem::with(['product'])->where('user_id', $this->user()->id)->select();
        $data = [];
        foreach ($items as $i) {
            if (!$i->product) {
                continue;
            }
            $data[] = [
                'id' => (int) $i->id,
                'quantity' => (int) $i->quantity,
                'product' => [
                    'id' => (int) $i->product->id,
                    'title' => $i->product->title,
                    'price' => (string) $i->product->price,
                ],
                'subtotal' => (string) ((float) $i->product->price * (int) $i->quantity),
            ];
        }

        return json_data($data);
    }

    public function addCart($productId): Response
    {
        $product = Product::find($productId);
        if (!$product || $product->status !== 'approved') {
            return json_error('Not Found', 404);
        }
        $qty = max(1, (int) $this->request->post('quantity', 1));
        $item = CartItem::where('user_id', $this->user()->id)->where('product_id', $product->id)->find();
        if ($item) {
            $item->quantity = (int) $item->quantity + $qty;
            $item->save();
        } else {
            CartItem::create([
                'user_id' => $this->user()->id,
                'product_id' => $product->id,
                'quantity' => $qty,
            ]);
        }

        return json_message('ok');
    }

    public function updateCart($id): Response
    {
        $item = CartItem::find($id);
        if (!$item || (int) $item->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }
        $qty = (int) $this->request->put('quantity', $this->request->post('quantity', 0));
        if ($qty < 1) {
            return json_error('数量无效', 422);
        }
        $item->quantity = $qty;
        $item->save();

        return json_message('ok');
    }

    public function removeCart($id): Response
    {
        $item = CartItem::find($id);
        if (!$item || (int) $item->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }
        $item->delete();

        return json_message('ok');
    }

    public function checkout(): Response
    {
        $data = $this->request->post();
        foreach (['receiver_name', 'receiver_phone', 'receiver_address'] as $f) {
            if (empty($data[$f])) {
                return json_error("字段 {$f} 必填", 422);
            }
        }
        $cartItems = CartItem::with(['product'])->where('user_id', $this->user()->id)->select();
        if ($cartItems->isEmpty()) {
            return json_error('购物车为空', 422);
        }

        $order = Db::transaction(function () use ($cartItems, $data) {
            $amount = 0;
            foreach ($cartItems as $i) {
                $amount += (float) $i->product->price * (int) $i->quantity;
            }
            $order = Order::create([
                'order_no' => Order::generateNo(),
                'user_id' => $this->user()->id,
                'amount' => $amount,
                'status' => 'pending',
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'receiver_address' => $data['receiver_address'],
                'remark' => $data['remark'] ?? null,
            ]);
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'title' => $item->product->title,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                ]);
            }
            CartItem::where('user_id', $this->user()->id)->delete();
            $order->status = 'paid';
            $order->payment_method = 'alipay';
            $order->payment_trade_no = 'MOCK'.$order->order_no;
            $order->paid_at = date('Y-m-d H:i:s');
            $order->save();

            return $order->together(['items']);
        });

        return json([
            'data' => $order->toListArray(),
            'payment' => ['trade_no' => $order->payment_trade_no, 'method' => 'alipay'],
        ]);
    }

    public function messages(): Response
    {
        $paginator = Message::with(['fromUser'])->where('user_id', $this->user()->id)->order('id', 'desc')
            ->paginate(['list_rows' => 20, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, static function ($m) {
            return [
                'id' => (int) $m->id,
                'title' => $m->title,
                'content' => $m->content,
                'is_read' => (bool) $m->is_read,
                'from_user_id' => $m->from_user_id,
                'from' => $m->fromUser ? [
                    'id' => (int) $m->fromUser->id,
                    'name' => $m->fromUser->name,
                    'username' => $m->fromUser->username,
                ] : null,
                'created_at' => $m->created_at,
            ];
        }));
    }

    public function sendMessage(): Response
    {
        $data = $this->request->post();
        if (empty($data['to']) || empty($data['title']) || empty($data['content'])) {
            return json_error('请填写完整', 422);
        }
        $to = $data['to'];
        $query = User::where(function ($w) use ($to) {
            if (ctype_digit((string) $to)) {
                $w->where('id', (int) $to)->whereOr('username', $to)->whereOr('name', $to);
            } else {
                $w->where('username', $to)->whereOr('name', $to);
            }
        });
        $toUser = $query->find();
        if (!$toUser) {
            return json_error('收件人不存在', 422);
        }
        if ((int) $toUser->id === (int) $this->user()->id) {
            return json_error('不能给自己发信', 422);
        }
        $msg = Message::create([
            'user_id' => $toUser->id,
            'from_user_id' => $this->user()->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'is_read' => 0,
        ]);

        return json(['data' => ['id' => (int) $msg->id], 'message' => '已发送'], 201);
    }

    public function readMessage($id): Response
    {
        $message = Message::find($id);
        if (!$message || (int) $message->user_id !== (int) $this->user()->id) {
            return json_error('Forbidden', 403);
        }
        if (!$message->is_read) {
            $message->is_read = 1;
            $message->save();
        }

        return json_message('ok');
    }

    private function canAccessOrder(Order $order): bool
    {
        return (int) $order->user_id === (int) $this->user()->id || $this->isOrderSeller($order);
    }

    private function isOrderSeller(Order $order): bool
    {
        $productIds = Product::where('user_id', $this->user()->id)->column('id');
        if (!$productIds) {
            return false;
        }

        return (bool) OrderItem::where('order_id', $order->id)->whereIn('product_id', $productIds)->find();
    }

    private function supplyInput(): array
    {
        $keys = ['title', 'price', 'unit', 'min_amount', 'amount', 'days', 'brand', 'area',
            'attr1_name', 'attr1_value', 'attr2_name', 'attr2_value', 'attr3_name', 'attr3_value',
            'summary', 'content', 'category_id', 'thumb', 'thumb1', 'thumb2'];
        $src = $this->request->post() ?: $this->request->put();
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $src)) {
                $out[$k] = $src[$k];
            }
        }

        return $out;
    }

    private function purchaseInput(): array
    {
        $keys = ['title', 'thumb', 'budget', 'quantity', 'unit', 'area', 'days', 'is_urgent', 'summary', 'content', 'category_id'];
        $src = $this->request->post() ?: $this->request->put();
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $src)) {
                $out[$k] = $src[$k];
            }
        }

        return $out;
    }
}
