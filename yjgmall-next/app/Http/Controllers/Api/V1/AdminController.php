<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'data' => [
                'users' => User::query()->count(),
                'users_pending' => User::query()->where(function ($q) {
                    $q->where('status', 'pending')->orWhere('group_code', 'pending');
                })->count(),
                'supplies' => Supply::query()->count(),
                'supplies_pending' => Supply::query()->where('status', 'pending')->count(),
                'purchases' => Purchase::query()->count(),
                'purchases_pending' => Purchase::query()->where('status', 'pending')->count(),
                'products' => Product::query()->count(),
                'orders' => Order::query()->count(),
                'companies' => Company::query()->count(),
                'inquiries' => Inquiry::query()->count(),
            ],
        ]);
    }

    public function users(Request $request)
    {
        $q = User::query()->latest();
        if ($request->filled('q')) {
            $kw = '%'.$request->string('q').'%';
            $q->where(function ($w) use ($kw) {
                $w->where('username', 'like', $kw)
                    ->orWhere('name', 'like', $kw)
                    ->orWhere('email', 'like', $kw)
                    ->orWhere('mobile', 'like', $kw)
                    ->orWhere('company_name', 'like', $kw);
            });
        }
        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        if ($request->filled('member_type')) {
            $q->where('member_type', $request->string('member_type'));
        }

        $items = $q->paginate(20);

        return response()->json([
            'data' => collect($items->items())->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'mobile' => $u->mobile,
                'company_name' => $u->company_name,
                'member_type' => $u->member_type,
                'group_code' => $u->group_code,
                'status' => $u->status,
                'is_admin' => (bool) $u->is_admin,
                'is_certified' => (bool) $u->is_certified,
                'created_at' => optional($u->created_at)?->toDateTimeString(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'status' => 'nullable|in:pending,active,banned',
            'group_code' => 'nullable|in:pending,personal,enterprise,vip,banned,guest',
            'member_type' => 'nullable|in:personal,company',
            'is_certified' => 'nullable|boolean',
            'is_admin' => 'nullable|boolean',
            'level' => 'nullable|integer|min:0|max:10',
        ]);

        // 审核通过时自动对齐会员组
        if (($data['status'] ?? null) === 'active' && empty($data['group_code'])) {
            $data['group_code'] = ($user->member_type === 'company' || ($data['member_type'] ?? '') === 'company')
                ? 'enterprise'
                : 'personal';
        }

        $user->update($data);

        return response()->json(['data' => new UserResource($user->fresh()), 'message' => '已更新']);
    }

    public function supplies(Request $request)
    {
        return $this->paginateModeration(
            Supply::query()->with('user:id,name,username,company_name')->latest(),
            $request,
            fn (Supply $s) => [
                'id' => $s->id,
                'title' => $s->title,
                'status' => $s->status,
                'price' => $s->price,
                'area' => $s->area,
                'user' => $s->user?->only(['id', 'name', 'username', 'company_name']),
                'created_at' => optional($s->created_at)?->toDateTimeString(),
            ]
        );
    }

    public function updateSupply(Request $request, Supply $supply)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,rejected,offline',
        ]);
        $supply->update($data);

        return response()->json(['data' => ['id' => $supply->id, 'status' => $supply->status], 'message' => '已更新']);
    }

    public function purchases(Request $request)
    {
        return $this->paginateModeration(
            Purchase::query()->with('user:id,name,username,company_name')->latest(),
            $request,
            fn (Purchase $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'status' => $p->status,
                'area' => $p->area,
                'user' => $p->user?->only(['id', 'name', 'username', 'company_name']),
                'created_at' => optional($p->created_at)?->toDateTimeString(),
            ]
        );
    }

    public function updatePurchase(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,rejected,offline',
        ]);
        $purchase->update($data);

        return response()->json(['data' => ['id' => $purchase->id, 'status' => $purchase->status], 'message' => '已更新']);
    }

    public function products(Request $request)
    {
        return $this->paginateModeration(
            Product::query()->with('user:id,name,username,company_name')->latest(),
            $request,
            fn (Product $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'status' => $p->status,
                'price' => $p->price,
                'stock' => $p->stock,
                'user' => $p->user?->only(['id', 'name', 'username', 'company_name']),
                'created_at' => optional($p->created_at)?->toDateTimeString(),
            ]
        );
    }

    public function updateProduct(Request $request, Product $product)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,rejected,offline,published',
        ]);
        $product->update($data);

        return response()->json(['data' => ['id' => $product->id, 'status' => $product->status], 'message' => '已更新']);
    }

    public function orders(Request $request)
    {
        $q = Order::query()->with(['user:id,name,username', 'items'])->latest();
        if ($request->filled('status')) {
            $q->where('status', $request->string('status'));
        }
        $items = $q->paginate(20);

        return response()->json([
            'data' => collect($items->items())->map(fn (Order $o) => [
                'id' => $o->id,
                'order_no' => $o->order_no ?? ('#'.$o->id),
                'status' => $o->status,
                'total_amount' => $o->amount,
                'user' => $o->user?->only(['id', 'name', 'username']),
                'items_count' => $o->items?->count() ?? 0,
                'created_at' => optional($o->created_at)?->toDateTimeString(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function updateOrder(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,paid,shipped,completed,cancelled,refunded',
        ]);
        $order->update($data);

        return response()->json(['data' => ['id' => $order->id, 'status' => $order->status], 'message' => '已更新']);
    }

    private function paginateModeration($query, Request $request, callable $map)
    {
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->string('q').'%');
        }
        $items = $query->paginate(20);

        return response()->json([
            'data' => collect($items->items())->map($map)->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
