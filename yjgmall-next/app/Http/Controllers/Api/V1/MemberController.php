<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\SupplyResource;
use App\Http\Resources\UserResource;
use App\Models\CartItem;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supply;
use App\Models\User;
use App\Services\Payment\AlipayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        return [
            'data' => [
                'user' => new UserResource($user),
                'stats' => [
                    'supplies' => $user->supplies()->count(),
                    'purchases' => $user->purchases()->count(),
                    'orders' => $user->orders()->count(),
                    'cart' => $user->cartItems()->count(),
                    'messages' => $user->messages()->where('is_read', false)->count(),
                    'chats' => \App\Models\ChatMessage::query()
                        ->where('to_user_id', $user->id)
                        ->where('is_read', false)
                        ->count(),
                    'deposit' => (string) ($user->deposit ?? 0),
                    'points' => (int) $user->points,
                    'balance' => (string) $user->balance,
                ],
            ],
        ];
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'mobile' => 'nullable|string|max:20|unique:users,mobile,'.$user->id,
            'company_name' => 'nullable|string|max:100',
            'password' => 'nullable|confirmed|min:6',
        ]);

        $user->fill(collect($data)->except('password')->all());
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return ['data' => new UserResource($user)];
    }

    public function supplies(Request $request)
    {
        return SupplyResource::collection($request->user()->supplies()->latest()->paginate(15));
    }

    public function showSupply(Request $request, Supply $supply)
    {
        abort_unless($supply->user_id === $request->user()->id, 403);

        return new SupplyResource($supply->load('category'));
    }

    public function storeSupply(Request $request)
    {
        $request->user()->ensureEnterprise();
        $data = $this->validateSupply($request);
        $item = $request->user()->supplies()->create([
            ...$data,
            'status' => 'pending',
            'refreshed_at' => now(),
        ]);

        return (new SupplyResource($item))->response()->setStatusCode(201);
    }

    public function updateSupply(Request $request, Supply $supply)
    {
        $request->user()->ensureEnterprise();
        abort_unless($supply->user_id === $request->user()->id, 403);
        $supply->update($this->validateSupply($request));

        return new SupplyResource($supply->fresh());
    }

    public function destroySupply(Request $request, Supply $supply)
    {
        $request->user()->ensureEnterprise();
        abort_unless($supply->user_id === $request->user()->id, 403);
        $supply->delete();

        return response()->json(['message' => 'ok']);
    }

    public function refreshSupply(Request $request, Supply $supply)
    {
        $request->user()->ensureEnterprise();
        abort_unless($supply->user_id === $request->user()->id, 403);
        $supply->update(['refreshed_at' => now()]);

        return new SupplyResource($supply);
    }

    public function purchases(Request $request)
    {
        return PurchaseResource::collection($request->user()->purchases()->latest()->paginate(15));
    }

    public function showPurchase(Request $request, Purchase $purchase)
    {
        abort_unless($purchase->user_id === $request->user()->id, 403);

        return new PurchaseResource($purchase->load('category'));
    }

    public function storePurchase(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'thumb' => 'nullable|string|max:500',
            'budget' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:30',
            'area' => 'nullable|string|max:50',
            'days' => 'nullable|integer|min:0',
            'is_urgent' => 'boolean',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        $item = $request->user()->purchases()->create([
            ...$data,
            'status' => 'pending',
            'refreshed_at' => now(),
        ]);

        return (new PurchaseResource($item))->response()->setStatusCode(201);
    }

    public function updatePurchase(Request $request, Purchase $purchase)
    {
        abort_unless($purchase->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'thumb' => 'nullable|string|max:500',
            'budget' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|string|max:50',
            'unit' => 'nullable|string|max:30',
            'area' => 'nullable|string|max:50',
            'days' => 'nullable|integer|min:0',
            'is_urgent' => 'boolean',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        $purchase->update($data);

        return new PurchaseResource($purchase->fresh());
    }

    public function destroyPurchase(Request $request, Purchase $purchase)
    {
        abort_unless($purchase->user_id === $request->user()->id, 403);
        $purchase->delete();

        return response()->json(['message' => 'ok']);
    }

    public function orders(Request $request)
    {
        return OrderResource::collection($request->user()->orders()->with('items')->latest()->paginate(15));
    }

    public function showOrder(Request $request, Order $order)
    {
        abort_unless($this->canAccessOrder($request, $order), 403);
        $order->load(['items', 'user']);

        return new OrderResource($order);
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'action' => 'required|in:ship,confirm,cancel',
        ]);

        $uid = (int) $request->user()->id;
        $isBuyer = (int) $order->user_id === $uid;
        $isSeller = $this->isOrderSeller($request, $order);

        if ($data['action'] === 'ship') {
            abort_unless($isSeller, 403);
            abort_unless(in_array($order->status, ['paid', 'pending'], true), 422, '当前状态不可发货');
            $order->update(['status' => 'shipped']);
        } elseif ($data['action'] === 'confirm') {
            abort_unless($isBuyer, 403);
            abort_unless(in_array($order->status, ['shipped', 'paid'], true), 422, '当前状态不可确认收货');
            $order->update(['status' => 'completed']);
        } else {
            abort_unless($isBuyer || $isSeller, 403);
            abort_unless(in_array($order->status, ['pending', 'paid'], true), 422, '当前状态不可取消');
            $order->update(['status' => 'cancelled']);
        }

        return response()->json([
            'message' => 'ok',
            'data' => new OrderResource($order->fresh()->load(['items', 'user'])),
        ]);
    }

    public function sellerOrders(Request $request)
    {
        $request->user()->ensureEnterprise();
        $productIds = $request->user()->products()->pluck('id');
        $orders = Order::query()
            ->whereHas('items', fn ($q) => $q->whereIn('product_id', $productIds))
            ->with('items', 'user')
            ->latest()
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    public function cart(Request $request)
    {
        $items = CartItem::with('product')->where('user_id', $request->user()->id)->get();

        return [
            'data' => $items->map(fn ($i) => [
                'id' => $i->id,
                'quantity' => $i->quantity,
                'product' => [
                    'id' => $i->product->id,
                    'title' => $i->product->title,
                    'price' => (string) $i->product->price,
                ],
                'subtotal' => (string) ($i->product->price * $i->quantity),
            ]),
        ];
    }

    public function addCart(Request $request, Product $product)
    {
        abort_unless($product->status === 'approved', 404);
        $qty = max(1, $request->integer('quantity', 1));
        $item = CartItem::firstOrNew([
            'user_id' => $request->user()->id,
            'product_id' => $product->id,
        ]);
        $item->quantity = ($item->exists ? $item->quantity : 0) + $qty;
        $item->save();

        return response()->json(['message' => 'ok']);
    }

    public function updateCart(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:9999',
        ]);
        $cartItem->update(['quantity' => $data['quantity']]);

        return response()->json(['message' => 'ok']);
    }

    public function removeCart(Request $request, CartItem $cartItem)
    {
        abort_unless($cartItem->user_id === $request->user()->id, 403);
        $cartItem->delete();

        return response()->json(['message' => 'ok']);
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'receiver_name' => 'required|string|max:50',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string|max:255',
            'remark' => 'nullable|string|max:500',
        ]);

        $cartItems = CartItem::with('product')->where('user_id', $request->user()->id)->get();
        abort_if($cartItems->isEmpty(), 422, '购物车为空');

        $order = DB::transaction(function () use ($cartItems, $data, $request) {
            $amount = $cartItems->sum(fn ($i) => $i->product->price * $i->quantity);
            $order = Order::create([
                'order_no' => Order::generateNo(),
                'user_id' => $request->user()->id,
                'amount' => $amount,
                'status' => 'pending',
                ...$data,
            ]);
            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'title' => $item->product->title,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                ]);
            }
            CartItem::where('user_id', $request->user()->id)->delete();

            return $order->load('items');
        });

        $pay = (new AlipayGateway)->pay(['id' => $order->id, 'order_no' => $order->order_no]);

        // Auto mock pay for API convenience in local
        $order->update([
            'status' => 'paid',
            'payment_method' => 'alipay',
            'payment_trade_no' => $pay['trade_no'] ?? ('MOCK'.$order->order_no),
            'paid_at' => now(),
        ]);

        return ['data' => new OrderResource($order->fresh('items')), 'payment' => $pay];
    }

    public function messages(Request $request)
    {
        $items = $request->user()->messages()->with('fromUser:id,name,username')->latest()->paginate(20);

        return response()->json([
            'data' => $items->getCollection()->map(fn ($m) => [
                'id' => $m->id,
                'title' => $m->title,
                'content' => $m->content,
                'is_read' => (bool) $m->is_read,
                'from_user_id' => $m->from_user_id,
                'from' => $m->fromUser ? [
                    'id' => $m->fromUser->id,
                    'name' => $m->fromUser->name,
                    'username' => $m->fromUser->username,
                ] : null,
                'created_at' => $m->created_at?->toDateTimeString(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function sendMessage(Request $request)
    {
        $data = $request->validate([
            'to' => 'required|string|max:80',
            'title' => 'required|string|max:120',
            'content' => 'required|string|max:2000',
        ]);

        $toQuery = User::query();
        if (ctype_digit($data['to'])) {
            $toQuery->where(function ($q) use ($data) {
                $q->where('id', (int) $data['to'])
                    ->orWhere('username', $data['to'])
                    ->orWhere('name', $data['to']);
            });
        } else {
            $toQuery->where(function ($q) use ($data) {
                $q->where('username', $data['to'])->orWhere('name', $data['to']);
            });
        }
        $to = $toQuery->first();

        abort_if(! $to, 422, '收件人不存在');
        abort_if((int) $to->id === (int) $request->user()->id, 422, '不能给自己发信');

        $msg = Message::create([
            'user_id' => $to->id,
            'from_user_id' => $request->user()->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'is_read' => false,
        ]);

        return response()->json(['data' => ['id' => $msg->id], 'message' => '已发送'], 201);
    }

    public function readMessage(Request $request, Message $message)
    {
        abort_unless((int) $message->user_id === (int) $request->user()->id, 403);
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json(['message' => 'ok']);
    }

    private function canAccessOrder(Request $request, Order $order): bool
    {
        return (int) $order->user_id === (int) $request->user()->id || $this->isOrderSeller($request, $order);
    }

    private function isOrderSeller(Request $request, Order $order): bool
    {
        $productIds = $request->user()->products()->pluck('id');
        if ($productIds->isEmpty()) {
            return false;
        }

        return $order->items()->whereIn('product_id', $productIds)->exists();
    }

    private function validateSupply(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:120',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:30',
            'min_amount' => 'nullable|numeric|min:0',
            'amount' => 'nullable|string|max:50',
            'days' => 'nullable|integer|min:0',
            'brand' => 'nullable|string|max:50',
            'area' => 'nullable|string|max:50',
            'attr1_name' => 'nullable|string|max:30',
            'attr1_value' => 'nullable|string|max:100',
            'attr2_name' => 'nullable|string|max:30',
            'attr2_value' => 'nullable|string|max:100',
            'attr3_name' => 'nullable|string|max:30',
            'attr3_value' => 'nullable|string|max:100',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'thumb' => 'nullable|string|max:500',
            'thumb1' => 'nullable|string|max:500',
            'thumb2' => 'nullable|string|max:500',
        ]);
    }
}
