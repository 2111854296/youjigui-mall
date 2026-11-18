<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\Supply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MemberController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $stats = [
            'supplies' => $user->supplies()->count(),
            'purchases' => $user->purchases()->count(),
            'orders' => $user->orders()->count(),
            'cart' => $user->cartItems()->count(),
            'messages' => $user->messages()->where('is_read', false)->count(),
        ];

        return view('member.index', compact('user', 'stats'));
    }

    public function profile()
    {
        return view('member.profile.edit', ['user' => Auth::user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();
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

        return back()->with('success', '资料已更新');
    }

    public function supplies()
    {
        $items = Auth::user()->supplies()->latest()->paginate(15);

        return view('member.sell.index', compact('items'));
    }

    public function createSupply()
    {
        return view('member.sell.form', ['item' => new Supply]);
    }

    public function storeSupply(Request $request)
    {
        $data = $this->validateListing($request);
        $data['user_id'] = Auth::id();
        $data['status'] = 'pending';
        $data['refreshed_at'] = now();
        Supply::create($data);

        return redirect()->route('member.supplies')->with('success', '供应已提交，等待审核');
    }

    public function editSupply(Supply $supply)
    {
        $this->authorizeOwner($supply);

        return view('member.sell.form', ['item' => $supply]);
    }

    public function updateSupply(Request $request, Supply $supply)
    {
        $this->authorizeOwner($supply);
        $supply->update($this->validateListing($request));

        return redirect()->route('member.supplies')->with('success', '已更新');
    }

    public function destroySupply(Supply $supply)
    {
        $this->authorizeOwner($supply);
        $supply->delete();

        return back()->with('success', '已删除');
    }

    public function refreshSupply(Supply $supply)
    {
        $this->authorizeOwner($supply);
        $supply->update(['refreshed_at' => now()]);

        return back()->with('success', '已刷新');
    }

    public function purchases()
    {
        $items = Auth::user()->purchases()->latest()->paginate(15);

        return view('member.buy.index', compact('items'));
    }

    public function createPurchase()
    {
        return view('member.buy.form', ['item' => new Purchase]);
    }

    public function storePurchase(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'budget' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|string|max:50',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        $data['user_id'] = Auth::id();
        $data['status'] = 'pending';
        $data['refreshed_at'] = now();
        Purchase::create($data);

        return redirect()->route('member.purchases')->with('success', '采购已提交，等待审核');
    }

    public function destroyPurchase(Purchase $purchase)
    {
        abort_unless($purchase->user_id === Auth::id(), 403);
        $purchase->delete();

        return back()->with('success', '已删除');
    }

    public function orders()
    {
        $items = Auth::user()->orders()->with('items')->latest()->paginate(15);

        return view('member.orders.index', compact('items'));
    }

    public function payMock(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);
        $order->update([
            'status' => 'paid',
            'payment_method' => 'alipay',
            'payment_trade_no' => 'MOCK'.$order->order_no,
            'paid_at' => now(),
        ]);

        return redirect()->route('member.orders')->with('success', '模拟支付成功');
    }

    public function messages()
    {
        $items = Auth::user()->messages()->latest()->paginate(20);
        Message::where('user_id', Auth::id())->where('is_read', false)->update(['is_read' => true]);

        return view('member.messages', compact('items'));
    }

    private function validateListing(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:120',
            'price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:30',
            'brand' => 'nullable|string|max:50',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'thumb' => 'nullable|string|max:255',
        ]);
    }

    private function authorizeOwner(Supply $supply): void
    {
        abort_unless($supply->user_id === Auth::id(), 403);
    }
}
