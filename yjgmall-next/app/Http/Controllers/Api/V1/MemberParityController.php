<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArticleResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\Article;
use App\Models\AskTicket;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\FinanceRecord;
use App\Models\MailSubscription;
use App\Models\Product;
use App\Models\TradeAlert;
use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MemberParityController extends Controller
{
    public static function groupLabels(): array
    {
        return [
            'admin' => '管理员',
            'banned' => '禁止访问',
            'guest' => '游客',
            'pending' => '待审核会员',
            'personal' => '个人会员',
            'enterprise' => '企业会员',
            'vip' => 'VIP渠道会员',
        ];
    }

    public function profileDeep(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => (new UserResource($user))->resolve(),
            'groups' => self::groupLabels(),
            'group_label' => self::groupLabels()[$user->group_code ?? 'personal'] ?? '个人会员',
        ]);
    }

    public function updateProfileDeep(Request $request)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:50',
            'gender' => 'nullable|in:male,female,1,2',
            'qq' => 'nullable|string|max:30',
            'area' => 'nullable|string|max:50',
            'province' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:50',
            'district' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:100',
            'company_type' => 'nullable|string|max:50',
            'sound' => 'nullable|integer|min:0|max:3',
            'online_status' => 'nullable|in:online,invisible,offline',
            'avatar' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:100',
            'mobile' => 'nullable|string|max:20',
        ]);
        if (isset($data['gender'])) {
            $data['gender'] = match ((string) $data['gender']) {
                '1', 'male' => 'male',
                '2', 'female' => 'female',
                default => $data['gender'],
            };
        }
        $request->user()->update($data);

        return $this->profileDeep($request);
    }

    public function updatePasswords(Request $request)
    {
        $data = $request->validate([
            'password' => 'nullable|string|min:6|confirmed',
            'current_password' => 'nullable|string',
            'pay_password' => 'nullable|string|min:6|confirmed',
            'current_pay_password' => 'nullable|string',
        ]);
        $user = $request->user();

        if (! empty($data['password'])) {
            if (! Hash::check((string) ($data['current_password'] ?? ''), $user->password)) {
                throw ValidationException::withMessages(['current_password' => '现有登录密码不正确']);
            }
            $user->password = $data['password'];
        }

        if (! empty($data['pay_password'])) {
            $currentPay = $user->pay_password ?: $user->password;
            if (! empty($user->pay_password) || $request->filled('current_pay_password')) {
                if (! Hash::check((string) ($data['current_pay_password'] ?? ''), $currentPay)) {
                    throw ValidationException::withMessages(['current_pay_password' => '现有支付密码不正确']);
                }
            }
            $user->pay_password = Hash::make($data['pay_password']);
        }

        $user->save();

        return response()->json(['message' => '密码已更新']);
    }

    public function validations(Request $request)
    {
        $u = $request->user();

        return response()->json([
            'data' => [
                'mobile_verified' => (bool) $u->mobile_verified,
                'bank_verified' => (bool) $u->bank_verified,
                'truename_verified' => (bool) $u->truename_verified,
                'mobile' => $u->mobile,
                'truename' => $u->truename,
                'bank_name' => $u->bank_name,
                'bank_account' => $u->bank_account,
            ],
        ]);
    }

    public function submitValidation(Request $request)
    {
        $type = (string) $request->input('type');
        $user = $request->user();

        if ($type === 'mobile') {
            $data = $request->validate(['mobile' => 'required|string|max:20']);
            $user->update(['mobile' => $data['mobile'], 'mobile_verified' => true]);
        } elseif ($type === 'truename') {
            $data = $request->validate(['truename' => 'required|string|max:50']);
            $user->update(['truename' => $data['truename'], 'truename_verified' => true, 'is_certified' => true]);
        } elseif ($type === 'bank') {
            $data = $request->validate([
                'bank_name' => 'required|string|max:80',
                'bank_account' => 'required|string|max:80',
                'truename' => 'nullable|string|max:50',
            ]);
            $user->update([
                ...$data,
                'bank_verified' => true,
            ]);
        } else {
            abort(422, '未知认证类型');
        }

        return $this->validations($request);
    }

    public function oauth(Request $request)
    {
        $u = $request->user();

        return response()->json([
            'data' => [
                'qq' => $u->oauth_qq,
                'weibo' => $u->oauth_weibo,
            ],
            'message' => '演示环境可手动绑定标识，真实 OAuth 需配置开放平台',
        ]);
    }

    public function bindOauth(Request $request)
    {
        $data = $request->validate([
            'provider' => 'required|in:qq,weibo',
            'openid' => 'required|string|max:100',
        ]);
        $field = $data['provider'] === 'qq' ? 'oauth_qq' : 'oauth_weibo';
        $request->user()->update([$field => $data['openid']]);

        return $this->oauth($request);
    }

    public function chats(Request $request)
    {
        $items = ChatSession::query()
            ->where('user_id', $request->user()->id)
            ->with('peer:id,name,username,avatar,online_status')
            ->orderByDesc('last_message_at')
            ->get();

        return response()->json([
            'data' => $items->map(fn (ChatSession $s) => [
                'id' => $s->id,
                'peer' => $s->peer,
                'last_message_at' => optional($s->last_message_at)?->toDateTimeString(),
            ]),
        ]);
    }

    public function startChat(Request $request)
    {
        $data = $request->validate(['peer_id' => 'required|exists:users,id']);
        abort_if((int) $data['peer_id'] === (int) $request->user()->id, 422, '不能与自己交谈');

        $session = ChatSession::firstOrCreate([
            'user_id' => $request->user()->id,
            'peer_id' => $data['peer_id'],
        ]);
        ChatSession::firstOrCreate([
            'user_id' => $data['peer_id'],
            'peer_id' => $request->user()->id,
        ]);

        return response()->json(['data' => ['id' => $session->id]], 201);
    }

    public function chatMessages(Request $request, ChatSession $chat)
    {
        abort_unless($chat->user_id === $request->user()->id, 403);
        $msgs = ChatMessage::query()
            ->where(function ($q) use ($chat) {
                $q->where(function ($qq) use ($chat) {
                    $qq->where('from_user_id', $chat->user_id)->where('to_user_id', $chat->peer_id);
                })->orWhere(function ($qq) use ($chat) {
                    $qq->where('from_user_id', $chat->peer_id)->where('to_user_id', $chat->user_id);
                });
            })
            ->latest()
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        ChatMessage::query()
            ->where('to_user_id', $request->user()->id)
            ->where('from_user_id', $chat->peer_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'data' => $msgs->map(fn (ChatMessage $m) => [
                'id' => $m->id,
                'from_user_id' => $m->from_user_id,
                'content' => $m->content,
                'is_read' => (bool) $m->is_read,
                'created_at' => $m->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    public function sendChat(Request $request, ChatSession $chat)
    {
        abort_unless($chat->user_id === $request->user()->id, 403);
        $data = $request->validate(['content' => 'required|string|max:2000']);
        $msg = ChatMessage::create([
            'chat_session_id' => $chat->id,
            'from_user_id' => $request->user()->id,
            'to_user_id' => $chat->peer_id,
            'content' => $data['content'],
        ]);
        $chat->update(['last_message_at' => now()]);
        ChatSession::query()
            ->where('user_id', $chat->peer_id)
            ->where('peer_id', $chat->user_id)
            ->update(['last_message_at' => now()]);

        return response()->json(['data' => ['id' => $msg->id]], 201);
    }

    public function alerts(Request $request)
    {
        return response()->json([
            'data' => TradeAlert::query()->where('user_id', $request->user()->id)->latest()->get(),
        ]);
    }

    public function storeAlert(Request $request)
    {
        $data = $request->validate([
            'module' => 'required|in:sell,buy,mall,company',
            'keyword' => 'required|string|max:50',
            'area' => 'nullable|string|max:50',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0',
        ]);
        $item = TradeAlert::create([...$data, 'user_id' => $request->user()->id, 'is_active' => true]);

        return response()->json(['data' => $item], 201);
    }

    public function destroyAlert(Request $request, TradeAlert $alert)
    {
        abort_unless($alert->user_id === $request->user()->id, 403);
        $alert->delete();

        return response()->json(['message' => 'ok']);
    }

    public function mailSub(Request $request)
    {
        $item = MailSubscription::query()->where('user_id', $request->user()->id)->first();

        return response()->json(['data' => $item]);
    }

    public function saveMailSub(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'topics' => 'nullable|array',
            'is_active' => 'boolean',
        ]);
        $item = MailSubscription::updateOrCreate(
            ['user_id' => $request->user()->id],
            [...$data, 'is_active' => $data['is_active'] ?? true]
        );

        return response()->json(['data' => $item]);
    }

    public function withdraws(Request $request)
    {
        return response()->json([
            'data' => WithdrawRequest::query()->where('user_id', $request->user()->id)->latest()->get(),
            'wallet' => [
                'balance' => (string) $request->user()->balance,
                'deposit' => (string) ($request->user()->deposit ?? 0),
            ],
        ]);
    }

    public function storeWithdraw(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'bank_name' => 'required|string|max:80',
            'bank_account' => 'required|string|max:80',
            'account_name' => 'required|string|max:50',
            'pay_password' => 'nullable|string',
        ]);
        $user = $request->user();
        if ($user->pay_password) {
            if (! Hash::check((string) ($data['pay_password'] ?? ''), $user->pay_password)) {
                throw ValidationException::withMessages(['pay_password' => '支付密码不正确']);
            }
        }
        abort_if((float) $user->balance < (float) $data['amount'], 422, '余额不足');

        $user->balance = (float) $user->balance - (float) $data['amount'];
        $user->save();
        FinanceRecord::create([
            'user_id' => $user->id,
            'type' => 'cash',
            'amount' => -1 * (float) $data['amount'],
            'balance_after' => $user->balance,
            'title' => '资金提现申请',
            'remark' => '待审核',
        ]);
        $item = WithdrawRequest::create([
            'user_id' => $user->id,
            'amount' => $data['amount'],
            'bank_name' => $data['bank_name'],
            'bank_account' => $data['bank_account'],
            'account_name' => $data['account_name'],
            'status' => 'pending',
        ]);

        return response()->json(['data' => ['id' => $item->id], 'message' => '提现申请已提交'], 201);
    }

    public function addDeposit(Request $request)
    {
        $data = $request->validate(['amount' => 'required|numeric|min:1|max:100000']);
        $user = $request->user();
        abort_if((float) $user->balance < (float) $data['amount'], 422, '余额不足，请先充值');
        $user->balance = (float) $user->balance - (float) $data['amount'];
        $user->deposit = (float) ($user->deposit ?? 0) + (float) $data['amount'];
        $user->save();
        FinanceRecord::create([
            'user_id' => $user->id,
            'type' => 'deposit',
            'amount' => -1 * (float) $data['amount'],
            'balance_after' => $user->balance,
            'title' => '缴纳保证金',
        ]);

        return response()->json([
            'message' => '保证金已增加',
            'wallet' => ['balance' => (string) $user->balance, 'deposit' => (string) $user->deposit],
        ]);
    }

    public function buyPoints(Request $request)
    {
        $data = $request->validate([
            'points' => 'required|integer|min:10|max:100000',
            'rate' => 'nullable|numeric|min:0.01',
        ]);
        $rate = (float) ($data['rate'] ?? 0.1);
        $cost = round($data['points'] * $rate, 2);
        $user = $request->user();
        abort_if((float) $user->balance < $cost, 422, '余额不足');
        $user->balance = (float) $user->balance - $cost;
        $user->points = (int) $user->points + (int) $data['points'];
        $user->save();
        FinanceRecord::create([
            'user_id' => $user->id,
            'type' => 'credit',
            'amount' => -$cost,
            'balance_after' => $user->balance,
            'title' => '购买积分 '.$data['points'],
        ]);

        return response()->json([
            'message' => '积分购买成功',
            'wallet' => ['balance' => (string) $user->balance, 'points' => (int) $user->points],
        ]);
    }

    public function products(Request $request)
    {
        $request->user()->ensureEnterprise();

        return ProductResource::collection($request->user()->products()->latest()->paginate(15));
    }

    public function showProduct(Request $request, Product $product)
    {
        $request->user()->ensureEnterprise();
        abort_unless($product->user_id === $request->user()->id, 403);

        return new ProductResource($product);
    }

    public function storeProduct(Request $request)
    {
        $request->user()->ensureEnterprise();
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'thumb' => 'nullable|string|max:500',
        ]);
        $item = $request->user()->products()->create([
            ...$data,
            'status' => 'pending',
            'stock' => $data['stock'] ?? 0,
        ]);

        return (new ProductResource($item))->response()->setStatusCode(201);
    }

    public function updateProduct(Request $request, Product $product)
    {
        $request->user()->ensureEnterprise();
        abort_unless($product->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'price' => 'required|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
            'thumb' => 'nullable|string|max:500',
        ]);
        $product->update($data);

        return new ProductResource($product->fresh());
    }

    public function destroyProduct(Request $request, Product $product)
    {
        $request->user()->ensureEnterprise();
        abort_unless($product->user_id === $request->user()->id, 403);
        $product->delete();

        return response()->json(['message' => 'ok']);
    }

    public function articles(Request $request)
    {
        $request->user()->ensureEnterprise();

        return ArticleResource::collection(
            Article::query()->where('user_id', $request->user()->id)->latest()->paginate(15)
        );
    }

    public function showArticle(Request $request, Article $article)
    {
        $request->user()->ensureEnterprise();
        abort_unless($article->user_id === $request->user()->id, 403);

        return new ArticleResource($article);
    }

    public function storeArticle(Request $request)
    {
        $request->user()->ensureEnterprise();
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'thumb' => 'nullable|string|max:500',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'type' => 'nullable|string|max:30',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        $item = Article::create([
            ...$data,
            'user_id' => $request->user()->id,
            'type' => $data['type'] ?? 'news',
            'status' => 'draft',
            'published_at' => null,
        ]);

        return (new ArticleResource($item))->response()->setStatusCode(201);
    }

    public function updateArticle(Request $request, Article $article)
    {
        $request->user()->ensureEnterprise();
        abort_unless($article->user_id === $request->user()->id, 403);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'thumb' => 'nullable|string|max:500',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'type' => 'nullable|string|max:30',
            'category_id' => 'nullable|exists:categories,id',
        ]);
        $article->update($data);

        return new ArticleResource($article->fresh());
    }

    public function destroyArticle(Request $request, Article $article)
    {
        $request->user()->ensureEnterprise();
        abort_unless($article->user_id === $request->user()->id, 403);
        $article->delete();

        return response()->json(['message' => 'ok']);
    }

    public function asks(Request $request)
    {
        return response()->json([
            'data' => AskTicket::query()->where('user_id', $request->user()->id)->latest()->get(),
        ]);
    }

    public function storeAsk(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'content' => 'required|string|max:5000',
            'contact_name' => 'nullable|string|max:50',
            'contact_phone' => 'nullable|string|max:30',
        ]);
        $item = AskTicket::create([
            ...$data,
            'user_id' => $request->user()->id,
            'status' => 'new',
        ]);

        return response()->json(['data' => ['id' => $item->id]], 201);
    }
}
