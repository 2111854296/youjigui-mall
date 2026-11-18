<?php
namespace app\controller\api\v1;
use app\BaseController; use app\common\ApiResource;
use app\model\CartItem; use app\model\ChatMessage; use app\model\FinanceRecord; use app\model\Message; use app\model\Order; use app\model\OrderItem;
use app\model\Inquiry; use app\model\JobApplication; use app\model\Product; use app\model\Purchase; use app\model\PurchaseQuote; use app\model\Supply; use app\model\User;
use think\facade\Db; use think\Response;

class Member extends BaseController
{
    public function dashboard(): Response
    {
        $user = $this->request->user;
        $uid = (int) $user->id;
        // 仅统计平台已转发的询盘（status=new 仍在官方审核，不进企业待办）
        $inquiryVisible = ['processing', 'reply_pending', 'replied'];
        $inquiryCount = (int) Inquiry::where('to_user_id', $uid)->whereIn('status', $inquiryVisible)->where('is_read', 0)->where(function ($q) {
            $q->whereNull('target_type')->whereOr('target_type', '<>', 'purchase');
        })->count();
        $purchaseInquiries = (int) Inquiry::where('to_user_id', $uid)->whereIn('status', $inquiryVisible)->where('target_type', 'purchase')->where('is_read', 0)->count();
        $pendingPurchases = (int) Purchase::where('user_id', $uid)->where('status', 'pending')->where('is_read', 0)->count();
        $purchaseIds = Purchase::where('user_id', $uid)->column('id');
        $quoteCount = $purchaseIds
            ? (int) PurchaseQuote::whereIn('purchase_id', $purchaseIds)->where('status', 'new')->where('is_read', 0)->count()
            : 0;
        $resumeCount = (int) JobApplication::where('employer_user_id', $uid)->where('status', 'pending')->where(function ($q) {
            $q->where('is_read', 0)->whereOr('is_read', null);
        })->count();
        $mail = ['inquiry' => 0, 'purchase' => 0, 'resume' => 0, 'quote' => 0, 'message' => 0];
        foreach (Message::where('user_id', $uid)->where('is_read', 0)->field('title')->select() as $row) {
            $title = (string) $row->title;
            if (str_contains($title, '询盘')) $mail['inquiry']++;
            elseif (str_contains($title, '报价')) $mail['quote']++;
            elseif (str_contains($title, '简历') || str_contains($title, '投递')) $mail['resume']++;
            elseif (str_contains($title, '求购') || str_contains($title, '采购')) $mail['purchase']++;
            else $mail['message']++;
        }
        $buckets = [
            'inquiry' => ['count' => max($inquiryCount, $mail['inquiry']), 'unit' => ' 条新询盘', 'to' => '/member/inquiries'],
            'purchase' => ['count' => max($purchaseInquiries + $pendingPurchases, $mail['purchase']), 'unit' => ' 条求购', 'to' => '/member/purchases'],
            'resume' => ['count' => max($resumeCount, $mail['resume']), 'unit' => ' 份简历', 'to' => '/member/job-received'],
            'quote' => ['count' => max($quoteCount, $mail['quote']), 'unit' => ' 份报价', 'to' => '/member/quotes'],
            'message' => ['count' => $mail['message'], 'unit' => ' 条未读消息', 'to' => '/member/messages'],
        ];
        $pending = [];
        foreach ($buckets as $key => $bucket) {
            if ($bucket['count'] < 1) continue;
            $pending[] = [
                'key' => $key,
                'count' => $bucket['count'],
                'text' => $bucket['count'] . $bucket['unit'],
                'to' => $bucket['to'],
            ];
        }
        return json_data([
            'user' => $user->toApiArray(),
            'pending' => $pending,
            'stats' => [
                'supplies' => Supply::where('user_id', $user->id)->count(),
                'purchases' => Purchase::where('user_id', $user->id)->count(),
                'orders' => Order::where('user_id', $user->id)->count(),
                'cart' => CartItem::where('user_id', $user->id)->count(),
                'messages' => Message::where('user_id', $user->id)->where('is_read', 0)->count(),
                'chats' => ChatMessage::where('to_user_id', $user->id)->where('is_read', 0)->count(),
                'favored_new' => $user->unseenResumeFavoredCount(),
                'announces_new' => $user->unseenAnnounceCount(),
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
        $data = $this->request->only(['title','thumb','thumb1','thumb2','price','unit','brand','area','summary','content','category_id','min_amount','amount','days','attr1_name','attr1_value','attr2_name','attr2_value','attr3_name','attr3_value']);
        if (empty($data['title'])) return json_error('标题必填', 422);
        $data['user_id'] = $this->request->user->id;
        $data['status'] = 'pending';
        // 精品/加急仅后台可标，防止会员自填后过审带标
        $data['is_urgent'] = 0;
        $data['is_elite'] = 0;
        $data['refreshed_at'] = date('Y-m-d H:i:s');
        $item = Supply::create($data);
        return json(['data' => ApiResource::supply($item->refresh())], 201);
    }
    public function updateSupply($id): Response
    {
        if ($e = ApiResource::requireEnterprise()) return $e;
        $item = Supply::where('user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $data = $this->request->only(['title','thumb','thumb1','thumb2','price','unit','brand','area','summary','content','category_id','min_amount','amount','days','attr1_name','attr1_value','attr2_name','attr2_value','attr3_name','attr3_value']);
        // 改文重新进审，避免已通过内容被悄悄改掉仍挂在前台
        $data['status'] = 'pending';
        unset($data['is_urgent'], $data['is_elite']);
        $item->save($data);
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
        if ((int) $item->is_read !== 1) {
            $item->is_read = 1;
            $item->save();
            mark_related_messages_read((int) $this->request->user->id, [(string) $item->title]);
        }
        return json_data(ApiResource::purchase($item));
    }
    public function storePurchase(): Response
    {
        $data = $this->request->only(['title','thumb','budget','quantity','unit','area','days','summary','content','category_id']);
        if (empty($data['title'])) return json_error('标题必填', 422);
        $data['user_id'] = $this->request->user->id;
        $data['status'] = 'pending';
        $data['is_urgent'] = 0;
        $data['is_read'] = 1;
        $data['refreshed_at'] = date('Y-m-d H:i:s');
        $item = Purchase::create($data);
        return json(['data' => ApiResource::purchase($item)], 201);
    }
    public function updatePurchase($id): Response
    {
        $item = Purchase::where('user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $fields = array_filter(
            $this->request->only(['title','thumb','budget','quantity','unit','area','days','summary','content','category_id']),
            static fn ($v) => $v !== null && $v !== ''
        );
        $status = (string) $this->request->param('status', '');
        // 仅允许自行下架；改内容或主动回审都进 pending
        if ($status === 'offline' && !$fields) {
            $item->status = 'offline';
            $item->save();
            return json_data(ApiResource::purchase($item->refresh()));
        }
        if ($fields) {
            $fields['status'] = 'pending';
            $item->save($fields);
        } elseif ($status === 'pending') {
            $item->status = 'pending';
            $item->save();
        }
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
        $user = $this->request->user;
        $action = (string)$this->request->post('action', '');
        $status = (string)$this->request->post('status', '');
        $map = [
            'confirm' => 'completed',
            'complete' => 'completed',
            'completed' => 'completed',
            'cancel' => 'cancelled',
            'cancelled' => 'cancelled',
            'ship' => 'shipped',
            'shipped' => 'shipped',
            'paid' => 'paid',
            'request_refund' => 'refund_requested',
            'refund_requested' => 'refund_requested',
            'approve_refund' => 'refunded',
            'refunded' => 'refunded',
            'reject_refund' => 'reject_refund',
        ];
        if ($action !== '' && isset($map[$action])) $status = $map[$action];
        elseif ($status !== '' && isset($map[$status])) $status = $map[$status];

        $isBuyer = (int)$order->user_id === (int)$user->id;
        $isSeller = false;
        $productIds = OrderItem::where('order_id', $order->id)->column('product_id');
        if ($productIds) {
            $isSeller = Product::whereIn('id', $productIds)->where('user_id', $user->id)->count() > 0;
        }
        $isAdmin = !empty($user->is_admin);

        $cur = (string)$order->status;
        $reason = trim((string)$this->request->post('refund_reason', ''));

        // 买家：取消未发货、确认收货、申请退款
        if ($status === 'cancelled') {
            if (!$isBuyer && !$isAdmin) return json_error('仅买家可取消', 403);
            if (!in_array($cur, ['pending', 'paid', 'pending_pay'], true)) return json_error('当前状态不可取消', 422);
        } elseif ($status === 'completed') {
            if (!$isBuyer && !$isAdmin) return json_error('仅买家可确认收货', 403);
            if (!in_array($cur, ['paid', 'shipped'], true)) return json_error('当前状态不可确认收货', 422);
        } elseif ($status === 'shipped') {
            if (!$isSeller && !$isAdmin) return json_error('仅卖家可发货', 403);
            if ($cur !== 'paid') return json_error('仅已支付订单可发货', 422);
        } elseif ($status === 'refund_requested') {
            if (!$isBuyer && !$isAdmin) return json_error('仅买家可申请退款', 403);
            if (!in_array($cur, ['paid', 'shipped'], true)) return json_error('当前状态不可申请退款', 422);
            if ($reason !== '') $order->refund_reason = $reason;
        } elseif ($status === 'refunded') {
            if (!$isSeller && !$isAdmin) return json_error('仅卖家/管理员可同意退款', 403);
            if ($cur !== 'refund_requested') return json_error('请先由买家申请退款', 422);
        } elseif ($status === 'reject_refund') {
            if (!$isSeller && !$isAdmin) return json_error('仅卖家/管理员可拒绝退款', 403);
            if ($cur !== 'refund_requested') return json_error('无待处理退款', 422);
            // 有物流单号则回到 shipped，否则 paid
            $status = !empty($order->tracking_no) || $this->request->post('back_to') === 'shipped' ? 'shipped' : 'paid';
            if ($this->request->post('back_to') === 'shipped') $status = 'shipped';
            if ($this->request->post('back_to') === 'paid') $status = 'paid';
            // 默认：若曾发货过用 shipped——用 note 字段无法判断；用 paid 更安全除非前端传 back_to
            if (!$this->request->post('back_to')) {
                $status = 'paid';
            }
        } elseif ($status === 'paid') {
            if (!$isAdmin) return json_error('无效状态', 422);
        } else {
            return json_error('无效状态', 422);
        }

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
            $qty = (int) $c->quantity;
            $unit = $p ? \app\common\PriceTiers::priceFor($p, $qty) : 0;
            $data[] = [
                'id' => (int)$c->id,
                'quantity' => $qty,
                'price' => $unit,
                'subtotal' => round($unit * $qty, 2),
                'product' => $p ? ApiResource::product($p) : null,
            ];
        }
        return json_data($data);
    }
    public function addCart($product): Response
    {
        $p = Product::publishedQuery()->find($product);
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
            $p = Product::where('id', $c->product_id)->where('status', 'approved')->where('is_listed', 1)->find();
            if (!$p) continue;
            $qty = max(1, (int) $c->quantity);
            $stock = (int) ($p->stock ?? 0);
            if ($stock <= 0 || $qty > $stock) {
                return json_error(($p->title ?: '商品').($stock <= 0 ? '已售罄' : '库存不足'), 422);
            }
            $unit = \app\common\PriceTiers::priceFor($p, $qty);
            $line = round($unit * $qty, 2);
            $amount += $line;
            $lines[] = ['product_id' => $p->id, 'title' => $p->title, 'price' => $unit, 'quantity' => $qty];
        }
        if ($amount <= 0 || empty($lines)) {
            return json_error('购物车商品无效', 422);
        }
        $amount = round($amount, 2);
        $result = \app\common\FundPay::createIntent(
            $user,
            \app\common\FundPay::TYPE_MALL,
            $amount,
            '商城订单',
            'mall',
            [
                'lines' => $lines,
                'receiver_name' => $data['receiver_name'] ?? $user->name,
                'receiver_phone' => $data['receiver_phone'] ?? $user->mobile,
                'receiver_address' => $data['receiver_address'] ?? '',
                'remark' => $data['remark'] ?? '',
            ]
        );
        if (empty($result['ok']) || empty($result['order'])) {
            return json_error($result['message'] ?? '下单失败', 422);
        }
        return json([
            'data' => \app\common\FundPay::serialize($result['order']),
            'message' => !empty($result['reused']) ? '已有进行中的付款订单' : '请对公转账并上传付款凭证',
        ], 201);
    }

    public function messages(): Response
    {
        $uid = (int) $this->request->user->id;
        $box = (string) $this->request->get('box', 'inbox');
        $page = max(1, (int) $this->request->get('page', 1));
        $query = Message::with(['fromUser', 'toUser'])->order('id', 'desc');
        if ($box === 'sent') {
            $query->where('from_user_id', $uid);
        } else {
            $query->where('user_id', $uid);
        }
        $paginator = $query->paginate(['list_rows' => 15, 'page' => $page]);
        return json(paginate_laravel($paginator, function ($m) {
            $brief = static function ($u) {
                if (!$u) return null;
                return [
                    'id' => (int) $u->id,
                    'name' => $u->name,
                    'username' => $u->username,
                ];
            };
            $row = $m->toArray();
            $row['action_to'] = $this->messageAction((string) $m->title);
            $row['from'] = $brief($m->fromUser);
            $row['to'] = $brief($m->toUser);
            unset($row['from_user'], $row['to_user']);
            return $row;
        }));
    }
    public function sendMessage(): Response
    {
        $raw = trim((string) $this->request->post('to_user_id', $this->request->post('to', '')));
        $to = 0;
        if ($raw !== '' && ctype_digit($raw)) {
            $to = (int) $raw;
        } elseif ($raw !== '') {
            $found = User::where('username', $raw)->whereOr('name', $raw)->find();
            $to = $found ? (int) $found->id : 0;
        }
        $title = (string) $this->request->post('title', '站内信');
        $content = trim((string) $this->request->post('content', ''));
        if ($content === '') {
            return json_error('请填写回复内容', 422);
        }

        // 仅允许询盘相关回复，且必须进平台审核；禁止会员自由互发
        // to 可空：按「收到询盘」标题匹配询盘记录
        $held = $this->holdInquiryLetterReply((int) $this->request->user->id, $to, $title, $content);
        if ($held !== null) {
            return $held;
        }

        return json_error('会员自由互发已关闭。询盘回复请针对「收到询盘」通知提交，平台审核后转达。', 403);
    }

    /**
     * 企业回复询盘：写入 reply_pending，不直达对方。
     */
    private function holdInquiryLetterReply(int $fromUid, int $toUid, string $title, string $content): ?Response
    {
        if (!str_contains($title, '询盘')) {
            return null;
        }
        $inqTitle = '';
        if (preg_match('/收到询盘[：:]\s*(.+)$/u', $title, $m)) {
            $inqTitle = trim((string) $m[1]);
        } elseif (preg_match('/询盘回复[：:]\s*(.+)$/u', $title, $m)) {
            $inqTitle = trim((string) $m[1]);
        }
        $vis = ['processing', 'reply_pending', 'replied'];
        $item = null;
        if ($inqTitle !== '') {
            $item = Inquiry::where('to_user_id', $fromUid)
                ->where('title', $inqTitle)
                ->whereIn('status', $vis)
                ->order('id', 'desc')
                ->find();
        }
        if (!$item && $toUid > 0) {
            $item = Inquiry::where('to_user_id', $fromUid)
                ->where('user_id', $toUid)
                ->whereIn('status', $vis)
                ->order('id', 'desc')
                ->find();
        }
        if (!$item) {
            // 标题含询盘但未匹配到记录时，仍不放行自由发信
            return json_error('未找到对应询盘，请到「询盘管理」回复。', 422);
        }
        $item->reply = $content;
        $item->status = 'reply_pending';
        $item->replied_at = null;
        $item->save();
        return json([
            'data' => [
                'id' => (int) $item->id,
                'inquiry_id' => (int) $item->id,
                'status' => 'reply_pending',
                'held' => true,
            ],
            'message' => '回复已提交，平台审核通过后将转达询盘方',
        ], 201);
    }
    public function readMessage($id): Response
    {
        $uid = (int) $this->request->user->id;
        $msg = Message::where('user_id', $uid)->find($id);
        if (!$msg) return json_error('Not found', 404);
        $msg->is_read = 1; $msg->save();
        $blob = (string) $msg->title . "\n" . (string) $msg->content;
        foreach (Inquiry::where('to_user_id', $uid)->where('is_read', 0)->select() as $item) {
            $title = trim((string) $item->title);
            if ($title !== '' && str_contains($blob, $title)) {
                $item->is_read = 1;
                $item->save();
            }
        }
        $purchaseIds = Purchase::where('user_id', $uid)->column('id');
        if ($purchaseIds) {
            foreach (PurchaseQuote::whereIn('purchase_id', $purchaseIds)->where('is_read', 0)->with(['purchase'])->select() as $quote) {
                $title = trim((string) ($quote->purchase->title ?? ''));
                if ($title !== '' && str_contains($blob, $title)) {
                    $quote->is_read = 1;
                    $quote->save();
                }
            }
        }
        foreach (Purchase::where('user_id', $uid)->where('is_read', 0)->select() as $item) {
            $title = trim((string) $item->title);
            if ($title !== '' && str_contains($blob, $title)) {
                $item->is_read = 1;
                $item->save();
            }
        }
        return json_message('ok');
    }

    private function messageAction(string $title): ?string
    {
        if (str_contains($title, '询盘')) return '/member/inquiries';
        if (str_contains($title, '报价')) return '/member/quotes';
        if (str_contains($title, '简历') || str_contains($title, '投递')) return '/member/job-received';
        if (str_contains($title, '求购') || str_contains($title, '采购')) return '/member/purchases';
        return null;
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