<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Message;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supply;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:supply,purchase,product,company,guestbook',
            'id' => 'nullable|integer',
            'contact_name' => 'required|string|max:50',
            'contact_phone' => 'required|string|max:30',
            'contact_email' => 'nullable|email',
            'title' => 'required|string|max:120',
            'content' => 'required|string|max:2000',
        ]);

        if ($data['type'] === 'guestbook') {
            $inquiry = Inquiry::create([
                'user_id' => $request->user()?->id,
                'to_user_id' => null,
                'target_type' => 'guestbook',
                'target_id' => 0,
                'contact_name' => $data['contact_name'],
                'contact_phone' => $data['contact_phone'],
                'contact_email' => $data['contact_email'] ?? null,
                'title' => $data['title'],
                'content' => $data['content'],
                'status' => 'new',
            ]);

            return response()->json(['data' => ['id' => $inquiry->id], 'message' => '留言已提交'], 201);
        }

        $request->validate(['id' => 'required|integer']);

        $map = [
            'supply' => Supply::class,
            'purchase' => Purchase::class,
            'product' => Product::class,
        ];

        $toUserId = null;
        $targetType = $data['type'];
        if (isset($map[$data['type']])) {
            $target = $map[$data['type']]::query()->findOrFail($data['id']);
            $toUserId = $target->user_id;
            $targetType = $map[$data['type']];
        } elseif ($data['type'] === 'company') {
            $company = \App\Models\Company::query()->findOrFail($data['id']);
            $toUserId = $company->user_id;
            $targetType = \App\Models\Company::class;
        }

        $inquiry = Inquiry::create([
            'user_id' => $request->user()?->id,
            'to_user_id' => $toUserId,
            'target_type' => $targetType,
            'target_id' => $data['id'],
            'contact_name' => $data['contact_name'],
            'contact_phone' => $data['contact_phone'],
            'contact_email' => $data['contact_email'] ?? null,
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => 'new',
        ]);

        if ($toUserId) {
            Message::create([
                'user_id' => $toUserId,
                'from_user_id' => $request->user()?->id,
                'title' => '收到询盘：'.$data['title'],
                'content' => "联系人：{$data['contact_name']} / {$data['contact_phone']}\n".$data['content'],
            ]);
        }

        return response()->json(['data' => ['id' => $inquiry->id], 'message' => '询盘已发送'], 201);
    }

    public function index(Request $request)
    {
        $items = Inquiry::query()
            ->where(function ($q) use ($request) {
                $q->where('user_id', $request->user()->id)
                    ->orWhere('to_user_id', $request->user()->id);
            })
            ->latest()
            ->paginate(20);

        $uid = (int) $request->user()->id;

        return response()->json([
            'data' => collect($items->items())->map(fn (Inquiry $i) => [
                'id' => $i->id,
                'title' => $i->title,
                'content' => $i->content,
                'reply' => $i->reply,
                'replied_at' => optional($i->replied_at)?->toDateTimeString(),
                'contact_name' => $i->contact_name,
                'contact_phone' => $i->contact_phone,
                'contact_email' => $i->contact_email,
                'status' => $i->status,
                'direction' => (int) $i->to_user_id === $uid ? 'inbox' : 'outbox',
                'created_at' => optional($i->created_at)?->toDateTimeString(),
            ]),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function reply(Request $request, Inquiry $inquiry)
    {
        abort_unless((int) $inquiry->to_user_id === (int) $request->user()->id, 403);

        $data = $request->validate([
            'reply' => 'required|string|max:2000',
            'status' => 'nullable|in:replied,closed',
        ]);

        $inquiry->update([
            'reply' => $data['reply'],
            'status' => $data['status'] ?? 'replied',
            'replied_at' => now(),
        ]);

        if ($inquiry->user_id) {
            Message::create([
                'user_id' => $inquiry->user_id,
                'from_user_id' => $request->user()->id,
                'title' => '询盘回复：'.$inquiry->title,
                'content' => $data['reply'],
            ]);
        }

        return response()->json(['message' => '已回复']);
    }

    public function updateStatus(Request $request, Inquiry $inquiry)
    {
        $uid = (int) $request->user()->id;
        abort_unless((int) $inquiry->user_id === $uid || (int) $inquiry->to_user_id === $uid, 403);

        $data = $request->validate([
            'status' => 'required|in:new,replied,closed',
        ]);
        $inquiry->update(['status' => $data['status']]);

        return response()->json(['message' => 'ok']);
    }
}
