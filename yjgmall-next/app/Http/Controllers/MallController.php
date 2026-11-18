<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Services\Payment\AlipayGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MallController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::published()->with('category')->latest();
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->q.'%');
        }
        $items = $query->paginate(16)->withQueryString();

        return view('mall.index', compact('items'));
    }

    public function show(Product $product)
    {
        abort_unless($product->status === 'approved', 404);
        $product->increment('views');

        return view('mall.show', compact('product'));
    }

    public function addCart(Request $request, Product $product)
    {
        abort_unless($product->status === 'approved', 404);
        $qty = max(1, (int) $request->input('quantity', 1));
        $item = CartItem::firstOrNew([
            'user_id' => Auth::id(),
            'product_id' => $product->id,
        ]);
        $item->quantity = ($item->exists ? $item->quantity : 0) + $qty;
        $item->save();

        return redirect()->route('mall.cart')->with('success', '已加入购物车');
    }

    public function cart()
    {
        $items = CartItem::with('product')->where('user_id', Auth::id())->get();

        return view('mall.cart', compact('items'));
    }

    public function checkout(Request $request)
    {
        $data = $request->validate([
            'receiver_name' => 'required|string|max:50',
            'receiver_phone' => 'required|string|max:20',
            'receiver_address' => 'required|string|max:255',
            'remark' => 'nullable|string|max:500',
        ]);

        $cartItems = CartItem::with('product')->where('user_id', Auth::id())->get();
        abort_if($cartItems->isEmpty(), 400, '购物车为空');

        $order = DB::transaction(function () use ($cartItems, $data) {
            $amount = $cartItems->sum(fn ($i) => $i->product->price * $i->quantity);
            $order = Order::create([
                'order_no' => Order::generateNo(),
                'user_id' => Auth::id(),
                'amount' => $amount,
                'status' => 'pending',
                'receiver_name' => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'receiver_address' => $data['receiver_address'],
                'remark' => $data['remark'] ?? null,
            ]);
            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'title' => $item->product->title,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                ]);
            }
            CartItem::where('user_id', Auth::id())->delete();

            return $order;
        });

        $gateway = new AlipayGateway;
        $pay = $gateway->pay(['id' => $order->id, 'order_no' => $order->order_no, 'amount' => $order->amount]);

        return redirect($pay['redirect_url']);
    }
}
