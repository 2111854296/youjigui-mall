<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PurchaseResource;
use App\Models\Category;
use App\Models\Message;
use App\Models\Purchase;
use App\Models\PurchaseQuote;
use Illuminate\Http\Request;

class BuyController extends Controller
{
    public function index(Request $request)
    {
        $query = Purchase::published()->with(['category', 'user'])->withCount('quotes');

        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->string('q').'%');
        }
        if ($request->filled('category')) {
            $catId = $request->integer('category');
            $childIds = Category::query()->where('parent_id', $catId)->pluck('id')->all();
            $query->whereIn('category_id', array_merge([$catId], $childIds));
        }
        if ($request->filled('area')) {
            $query->where('area', 'like', '%'.$request->string('area').'%');
        }
        if ($request->boolean('urgent')) {
            $query->where('is_urgent', true);
        }

        $sort = (string) $request->string('sort', 'refresh');
        match ($sort) {
            'budget_desc' => $query->orderByDesc('budget'),
            'hits' => $query->orderByDesc('views'),
            default => $query->orderByDesc('is_urgent')->orderByDesc('level')->latest('refreshed_at'),
        };

        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('module', 'sell')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])
            ->orderBy('sort')
            ->get();

        $showFloors = ! $request->filled('q')
            && ! $request->filled('category')
            && ! $request->filled('area')
            && ! $request->boolean('urgent')
            && $request->integer('page', 1) <= 1;

        $floors = collect();
        $urgent = collect();
        if ($showFloors) {
            $floors = $categories->values()->map(function (Category $cat, int $index) {
                $ids = array_merge([$cat->id], $cat->children->pluck('id')->all());

                return [
                    'floor_no' => $index + 1,
                    'category' => new CategoryResource($cat),
                    'children' => CategoryResource::collection($cat->children),
                    'items' => PurchaseResource::collection(
                        Purchase::published()
                            ->with(['category', 'user'])
                            ->whereIn('category_id', $ids)
                            ->orderByDesc('is_urgent')
                            ->latest('refreshed_at')
                            ->limit(8)
                            ->get()
                    ),
                ];
            });

            $urgent = PurchaseResource::collection(
                Purchase::published()
                    ->with(['category', 'user'])
                    ->where('is_urgent', true)
                    ->latest('refreshed_at')
                    ->limit(8)
                    ->get()
            );
        }

        return PurchaseResource::collection($query->paginate(20))->additional([
            'filters' => [
                'areas' => Purchase::published()->whereNotNull('area')->distinct()->orderBy('area')->limit(50)->pluck('area'),
            ],
            'categories' => CategoryResource::collection($categories),
            'floors' => $floors,
            'urgent' => $urgent,
        ]);
    }

    public function show(Purchase $purchase)
    {
        abort_unless($purchase->status === 'approved', 404);
        $purchase->increment('views');
        $purchase->load(['category', 'user'])->loadCount('quotes');

        return new PurchaseResource($purchase);
    }

    public function quote(Request $request, Purchase $purchase)
    {
        abort_unless($purchase->status === 'approved', 404);
        $data = $request->validate([
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:30',
            'quantity' => 'nullable|string|max:50',
            'content' => 'required|string|max:2000',
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:30',
        ]);

        $quote = PurchaseQuote::create([
            ...$data,
            'purchase_id' => $purchase->id,
            'user_id' => $request->user()?->id,
            'status' => 'new',
        ]);

        Message::create([
            'user_id' => $purchase->user_id,
            'from_user_id' => $request->user()?->id,
            'title' => '收到采购报价：'.$purchase->title,
            'content' => "报价人：{$data['contact_name']} / {$data['contact_phone']}\n价格：".($data['price'] ?? '面议')."\n".$data['content'],
        ]);

        return response()->json(['data' => ['id' => $quote->id], 'message' => '报价已提交'], 201);
    }

    public function myQuotes(Request $request)
    {
        $items = PurchaseQuote::query()
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with(['purchase:id,title,user_id', 'user:id,name,username,company_name,mobile'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $items->getCollection()->map(fn (PurchaseQuote $q) => [
                'id' => $q->id,
                'price' => $q->price !== null ? (string) $q->price : null,
                'unit' => $q->unit,
                'quantity' => $q->quantity,
                'content' => $q->content,
                'contact_name' => $q->contact_name,
                'contact_phone' => $q->contact_phone,
                'status' => $q->status,
                'created_at' => optional($q->created_at)?->toDateTimeString(),
                'purchase' => $q->purchase ? [
                    'id' => $q->purchase->id,
                    'title' => $q->purchase->title,
                ] : null,
                'user' => $q->user ? [
                    'id' => $q->user->id,
                    'name' => $q->user->name,
                    'username' => $q->user->username,
                    'company_name' => $q->user->company_name,
                ] : null,
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function updateQuote(Request $request, PurchaseQuote $quote)
    {
        $quote->load('purchase');
        abort_unless($quote->purchase && (int) $quote->purchase->user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'status' => 'required|in:new,accepted,rejected,done',
        ]);

        $quote->update(['status' => $data['status']]);

        if ($quote->user_id) {
            $label = match ($data['status']) {
                'accepted' => '已采纳',
                'rejected' => '已婉拒',
                'done' => '已完成',
                default => '已更新',
            };
            Message::create([
                'user_id' => $quote->user_id,
                'from_user_id' => $request->user()->id,
                'title' => '报价'.$label.'：'.$quote->purchase->title,
                'content' => '您对采购「'.$quote->purchase->title.'」的报价状态变更为：'.$label,
            ]);
        }

        return response()->json(['message' => 'ok', 'data' => ['id' => $quote->id, 'status' => $quote->status]]);
    }
}
