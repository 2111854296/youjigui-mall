<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdBooking;
use App\Models\FinanceRecord;
use App\Models\Spread;
use App\Support\MediaUrl;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function records(Request $request)
    {
        $items = FinanceRecord::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
            'wallet' => [
                'balance' => (string) $request->user()->balance,
                'deposit' => (string) ($request->user()->deposit ?? 0),
                'points' => (int) $request->user()->points,
                'credit' => (int) ($request->user()->credit ?? 0),
            ],
        ]);
    }

    public function charge(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1|max:100000',
        ]);
        $user = $request->user();
        $user->balance = (float) $user->balance + (float) $data['amount'];
        $user->save();

        $record = FinanceRecord::create([
            'user_id' => $user->id,
            'type' => 'charge',
            'amount' => $data['amount'],
            'balance_after' => $user->balance,
            'title' => '账户充值（模拟）',
            'remark' => '本地演示充值，非真实支付',
        ]);

        return response()->json(['data' => $record, 'wallet' => ['balance' => (string) $user->balance]]);
    }

    public function spreads(Request $request)
    {
        $items = Spread::query()->where('user_id', $request->user()->id)->latest()->get();

        return response()->json(['data' => $items]);
    }

    public function storeSpread(Request $request)
    {
        $request->user()->ensureEnterprise();
        $data = $request->validate([
            'module' => 'required|in:sell,buy,company,mall',
            'keyword' => 'required|string|max:50',
            'bid' => 'required|numeric|min:1',
            'days' => 'required|integer|min:1|max:365',
        ]);
        $item = Spread::create([
            ...$data,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $item, 'message' => '推广申请已提交，等待审核'], 201);
    }

    public function bookAd(Request $request)
    {
        $request->user()->ensureEnterprise();
        $data = $request->validate([
            'place_code' => 'required|string|max:40',
            'title' => 'required|string|max:120',
            'image' => 'nullable|string|max:500',
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:30',
            'remark' => 'nullable|string|max:500',
        ]);
        $booking = AdBooking::create([
            ...$data,
            'user_id' => $request->user()->id,
            'status' => 'pending',
        ]);

        return response()->json(['data' => ['id' => $booking->id], 'message' => '广告预定已提交'], 201);
    }

    public function adBookings(Request $request)
    {
        return response()->json([
            'data' => AdBooking::query()
                ->where('user_id', $request->user()->id)
                ->latest()
                ->get()
                ->map(fn (AdBooking $b) => [
                    'id' => $b->id,
                    'place_code' => $b->place_code,
                    'title' => $b->title,
                    'image' => MediaUrl::make($b->image),
                    'contact_name' => $b->contact_name,
                    'contact_phone' => $b->contact_phone,
                    'remark' => $b->remark,
                    'status' => $b->status,
                    'created_at' => $b->created_at?->toDateTimeString(),
                ]),
        ]);
    }
}
