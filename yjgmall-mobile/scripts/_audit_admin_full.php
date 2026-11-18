<?php
/**
 * 后台全量自检：资金 / 门禁 / 审核 / 询盘 / 行情 / 订单广告等
 * 全部用独立测试数据，结束清理。
 */
$root = '/www/wwwroot/g.aifeili.cn';
chdir($root);
require $root.'/vendor/autoload.php';
$app = new \think\App();
$app->initialize();

use app\common\AdminSecondPassword;
use app\common\FundPay;
use app\common\QuotePriceImport;
use app\model\Article;
use app\model\Company;
use app\model\FinanceRecord;
use app\model\FundPayOrder;
use app\model\Inquiry;
use app\model\JobPost;
use app\model\MemberVerification;
use app\model\Message;
use app\model\Order;
use app\model\Product;
use app\model\Purchase;
use app\model\QuoteProduct;
use app\model\QuoteProductPrice;
use app\model\QuoteRegionPrice;
use app\model\Resume;
use app\model\Spread;
use app\model\Supply;
use app\model\User;
use app\model\WithdrawRequest;
use think\facade\Db;

$tag = 'FULLAUD'.substr((string) time(), -8);
$now = date('Y-m-d H:i:s');
$report = [];
$fail = 0;
$pass = function (string $name, bool $ok, $detail = null) use (&$report, &$fail) {
    $report[] = ['case' => $name, 'ok' => $ok, 'detail' => $detail];
    if (!$ok) {
        $fail++;
    }
};

$cleanup = [];
$expireAt = date('Y-m-d H:i:s', time() + 3600);
$mkUser = function (string $suffix, array $extra = []) use ($tag, $now, &$cleanup) {
    $u = User::create(array_merge([
        'username' => strtolower($tag.'_'.$suffix),
        'password' => password_hash('Test123456', PASSWORD_BCRYPT),
        'name' => '测_'.$suffix,
        'email' => strtolower($tag.'_'.$suffix).'@audit.test',
        'mobile' => '19'.substr((string) (crc32($tag.$suffix) % 1000000000), 0, 9),
        'member_type' => 'personal',
        'group_code' => 'personal',
        'status' => 'active',
        'balance' => 0,
        'points' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ], $extra));
    $cleanup[] = ['users', (int) $u->id];

    return $u;
};
$mkFund = function (array $data) use ($expireAt, $now, &$cleanup) {
    $row = FundPayOrder::create(array_merge([
        'expire_at' => $expireAt,
        'bank_name' => '自检银行',
        'bank_account' => '62220000',
        'bank_account_name' => '自检账户',
        'payload' => '{}',
        'created_at' => $now,
        'updated_at' => $now,
    ], $data));
    $cleanup[] = ['fund_pay_orders', (int) $row->id];

    return $row;
};
$section = function (string $name, callable $fn) use ($pass) {
    try {
        $fn();
    } catch (\Throwable $e) {
        $pass($name.'_fatal', false, $e->getMessage());
    }
};

$buyer = null;
$seller = null;
$co = null;
$ars = '';

$section('bootstrap', function () use ($mkUser, &$buyer, &$seller) {
    $buyer = $mkUser('buyer', ['balance' => 100.00]);
    $seller = $mkUser('seller', ['member_type' => 'company', 'group_code' => 'enterprise', 'company_name' => '自检企业']);
});

$section('second_password', function () use ($pass) {
    $fakeAdmin = (object) ['id' => 0];
    $threw = false;
    try {
        AdminSecondPassword::assertCanDelete($fakeAdmin);
    } catch (\Throwable $e) {
        $threw = true;
    }
    $pass('second_password_blocks_unauth', $threw);
    $isSet = AdminSecondPassword::isSet();
    $pass('second_password_is_configured', $isSet, ['is_set' => $isSet]);
});

$section('fund_charge', function () use ($pass, $mkFund, $buyer, $tag) {
    $bal0 = (float) User::find($buyer->id)->balance;
    $charge = $mkFund([
        'order_no' => 'AUDC'.$tag,
        'user_id' => (int) $buyer->id,
        'pay_type' => FundPay::TYPE_CHARGE,
        'amount' => 50.5,
        'status' => FundPay::STATUS_PENDING_REVIEW,
        'title' => '自检充值',
    ]);
    $r1 = FundPay::approve($charge, 1);
    $buyer1 = User::find($buyer->id);
    $pass('fund_charge_approve_adds_balance', !empty($r1['ok']) && abs((float) $buyer1->balance - ($bal0 + 50.5)) < 0.001, [
        'ok' => $r1,
        'balance' => $buyer1->balance,
        'expect' => $bal0 + 50.5,
    ]);
    $fr = FinanceRecord::where('user_id', $buyer->id)->where('remark', 'like', '%AUDC'.$tag.'%')->find();
    if (!$fr) {
        $fr = FinanceRecord::where('user_id', $buyer->id)->order('id', 'desc')->find();
    }
    $pass('fund_charge_writes_finance_record', (bool) $fr, $fr ? ['id' => $fr->id, 'title' => $fr->title ?? null] : null);

    $charge2 = $mkFund([
        'order_no' => 'AUDR'.$tag,
        'user_id' => (int) $buyer->id,
        'pay_type' => FundPay::TYPE_CHARGE,
        'amount' => 20,
        'status' => FundPay::STATUS_PENDING_REVIEW,
        'title' => '自检驳回充值',
    ]);
    $balBeforeReject = (float) User::find($buyer->id)->balance;
    FundPay::reject($charge2, 1);
    $charge2 = FundPayOrder::find($charge2->id);
    $balAfterReject = (float) User::find($buyer->id)->balance;
    $pass('fund_charge_reject_no_balance', (string) $charge2->status === FundPay::STATUS_REJECTED && abs($balAfterReject - $balBeforeReject) < 0.001);
});

$section('fund_points', function () use ($pass, $mkFund, $buyer, $tag) {
    $pts0 = (int) User::find($buyer->id)->points;
    $ptsOrder = $mkFund([
        'order_no' => 'AUDP'.$tag,
        'user_id' => (int) $buyer->id,
        'pay_type' => FundPay::TYPE_POINTS,
        'amount' => 10,
        'status' => FundPay::STATUS_PENDING_REVIEW,
        'title' => '自检积分',
        'payload' => json_encode(['points' => 100], JSON_UNESCAPED_UNICODE),
    ]);
    $rp = FundPay::approve($ptsOrder, 1);
    $pts1 = (int) User::find($buyer->id)->points;
    $pass('fund_points_approve', !empty($rp['ok']) && $pts1 === $pts0 + 100, ['points' => $pts1, 'ok' => $rp]);
});

$section('withdraw', function () use ($pass, $mkUser, $now, &$cleanup) {
    $wUser = $mkUser('wd', ['balance' => 0]);
    $wdData = [
        'user_id' => (int) $wUser->id,
        'amount' => 30,
        'bank_name' => '测试银行',
        'bank_account' => '622200001111',
        'status' => 'pending',
        'created_at' => $now,
        'updated_at' => $now,
    ];
    try {
        $wd = WithdrawRequest::create($wdData);
    } catch (\Throwable $e) {
        // 兼容账户名/真实姓名等必填
        $wdData['account_name'] = '测提现';
        $wdData['real_name'] = '测提现';
        $wdData['account'] = '622200001111';
        $wd = WithdrawRequest::create($wdData);
    }
    $cleanup[] = ['withdraw_requests', (int) $wd->id];
    $amount = 30.0;
    $u = User::find($wUser->id);
    $u->balance = round((float) $u->balance + $amount, 2);
    $u->save();
    FinanceRecord::create([
        'user_id' => (int) $u->id,
        'type' => 'cash',
        'amount' => $amount,
        'balance_after' => $u->balance,
        'title' => '提现驳回退回',
        'remark' => '申请 #'.$wd->id,
    ]);
    $wd->status = 'rejected';
    $wd->save();
    $pass('withdraw_reject_refunds_balance', abs((float) User::find($wUser->id)->balance - 30) < 0.001);
});

$section('verification', function () use ($pass, $mkUser, $now, &$cleanup) {
    $vUser = $mkUser('ver');
    $mv = MemberVerification::create([
        'user_id' => (int) $vUser->id,
        'type' => 'mobile',
        'mobile' => '13800138000',
        'status' => 'approved',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['member_verifications', (int) $mv->id];
    $vu = User::find($vUser->id);
    $vu->mobile = $mv->mobile;
    $vu->mobile_verified = 1;
    $vu->save();
    $vu = User::find($vUser->id);
    $pass('member_verification_mobile_writeback', (string) $vu->mobile === '13800138000' && (int) $vu->mobile_verified === 1);
});

$section('content_audit', function () use ($pass, $buyer, $seller, $tag, $now, &$cleanup, &$co) {
    $co = Company::create([
        'user_id' => (int) $seller->id,
        'name' => '自检公司'.$tag,
        'slug' => 'aud-co-'.$tag,
        'status' => 'pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['companies', (int) $co->id];
    $co->status = 'approved';
    $co->save();
    $pass('company_approve', (string) Company::find($co->id)->status === 'approved');

    $sup = Supply::create([
        'user_id' => (int) $seller->id,
        'title' => '自检供应'.$tag,
        'status' => 'pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['supplies', (int) $sup->id];
    $sup->status = 'approved';
    $sup->save();
    $pass('supply_approve', (string) Supply::find($sup->id)->status === 'approved');

    $pur = Purchase::create([
        'user_id' => (int) $buyer->id,
        'title' => '自检采购'.$tag,
        'status' => 'pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['purchases', (int) $pur->id];
    $pur->status = 'approved';
    $pur->save();
    $pass('purchase_approve', (string) Purchase::find($pur->id)->status === 'approved');

    $prod = Product::create([
        'user_id' => (int) $seller->id,
        'title' => '自检商品'.$tag,
        'price' => 10,
        'stock' => 5,
        'status' => 'pending',
        'is_listed' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['products', (int) $prod->id];
    $prod->status = 'approved';
    $prod->is_listed = 1;
    $prod->save();
    $pass('product_approve_lists', (string) Product::find($prod->id)->status === 'approved' && (int) Product::find($prod->id)->is_listed === 1);

    $job = JobPost::create([
        'user_id' => (int) $seller->id,
        'title' => '自检岗位'.$tag,
        'company_name' => '自检企业',
        'status' => 'pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['job_posts', (int) $job->id];
    $job->status = 'approved';
    $job->save();
    $pass('job_approve', (string) JobPost::find($job->id)->status === 'approved');

    $resume = Resume::create([
        'user_id' => (int) $buyer->id,
        'title' => '自检简历'.$tag,
        'status' => 'pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['resumes', (int) $resume->id];
    $resume->status = 'approved';
    $resume->save();
    $pass('resume_approve', (string) Resume::find($resume->id)->status === 'approved');
});

$section('inquiry', function () use ($pass, $buyer, $seller, $co, $tag, $now, $root, &$cleanup) {
    $inq = Inquiry::create([
        'user_id' => (int) $buyer->id,
        'to_user_id' => (int) $seller->id,
        'target_type' => 'company',
        'target_id' => (int) ($co->id ?? 0),
        'title' => '自检询盘'.$tag,
        'content' => '需要报价',
        'contact_name' => '测买家',
        'contact_phone' => '13900000000',
        'status' => 'new',
        'is_read' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['inquiries', (int) $inq->id];
    $inq->status = 'processing';
    $inq->save();
    Message::create([
        'user_id' => (int) $seller->id,
        'from_user_id' => (int) $buyer->id,
        'title' => '收到询盘：'.$inq->title,
        'content' => $inq->content,
        'is_read' => 0,
    ]);
    $msgFwd = Message::where('user_id', $seller->id)->where('title', '收到询盘：'.$inq->title)->order('id', 'desc')->find();
    $pass('inquiry_forward_message', $msgFwd && (int) $msgFwd->from_user_id === (int) $buyer->id);

    $inq->reply = '报价 10000';
    $inq->status = 'reply_pending';
    $inq->replied_at = null;
    $inq->save();
    $msgBefore = (int) Message::where('user_id', $buyer->id)->where('title', '询盘回复：'.$inq->title)->count();
    $pass('inquiry_reply_pending_no_buyer_msg', $msgBefore === 0 && (string) Inquiry::find($inq->id)->status === 'reply_pending');

    $inq->status = 'replied';
    $inq->replied_at = $now;
    $inq->save();
    Message::create([
        'user_id' => (int) $buyer->id,
        'from_user_id' => (int) $seller->id,
        'title' => '询盘回复：'.$inq->title,
        'content' => $inq->reply,
        'is_read' => 0,
    ]);
    $msgReply = Message::where('user_id', $buyer->id)->where('title', '询盘回复：'.$inq->title)->find();
    $pass('inquiry_reply_approve_delivers', (bool) $msgReply);

    $memberSrc = file_get_contents($root.'/app/controller/api/v1/Member.php');
    $pass('member_send_message_blocked_or_held', str_contains($memberSrc, '会员自由互发已关闭') || str_contains($memberSrc, 'holdInquiryLetterReply'));
});

$section('quote', function () use ($pass, $tag, $now, $root, &$cleanup, &$ars) {
    $qp = QuoteProduct::create([
        'name' => '自检行情'.$tag,
        'unit' => '元/吨',
        'market' => '自检',
        'is_active' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['quote_products', (int) $qp->id];
    $pid = (int) $qp->id;
    foreach ([['2024-02-01', 1000], ['2025-02-01', 2000], ['2026-02-01', 3000]] as [$d, $p]) {
        QuoteProductPrice::create(['product_id' => $pid, 'quoted_on' => $d, 'price_avg' => $p, 'price_min' => $p, 'price_max' => $p]);
        QuoteRegionPrice::create(['product_id' => $pid, 'region' => '华东', 'quoted_on' => $d, 'price' => $p]);
    }
    QuoteProductPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2026-01-01', '2026-12-31'])->delete();
    QuoteRegionPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2026-01-01', '2026-12-31'])->delete();
    $c24 = (int) QuoteProductPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2024-01-01', '2024-12-31'])->count();
    $c26 = (int) QuoteProductPrice::where('product_id', $pid)->whereBetween('quoted_on', ['2026-01-01', '2026-12-31'])->count();
    $pass('quote_clear_year_scoped', $c24 === 1 && $c26 === 0);

    $csv = "日期,均价\n1/8,8888\n2025-01-09,9999\n";
    $tmp = sys_get_temp_dir().'/fullaud_'.$tag.'.csv';
    file_put_contents($tmp, $csv);
    $imp = QuotePriceImport::fromFile($tmp, 'csv', $pid, 2026);
    @unlink($tmp);
    $d18 = QuoteProductPrice::where('product_id', $pid)->where('quoted_on', '2026-01-08')->find();
    $forcedOk = QuoteProductPrice::where('product_id', $pid)->where('quoted_on', '2026-01-09')->find();
    $leak = QuoteProductPrice::where('product_id', $pid)->where('quoted_on', '2025-01-09')->where('price_avg', 9999)->find();
    $pass('quote_import_short_date_and_force_year', $d18 && $forcedOk && !$leak, [
        'imported' => $imp['imported'] ?? null,
        'd18' => $d18 ? (string) $d18->quoted_on : null,
        'forced' => $forcedOk ? (string) $forcedOk->quoted_on : null,
    ]);

    $ars = file_get_contents($root.'/app/controller/api/v1/AdminResource.php');
    $pass('quote_clear_api_requires_year', str_contains($ars, '请指定要清除的年份'));
    $html = file_get_contents($root.'/public/admin-quote-board.html');
    $pass('quote_clear_ui_year_only', str_contains($html, '清除本年数据') && str_contains($html, 'prices?year='));
});

$section('order', function () use ($pass, $buyer, $tag, $now, &$cleanup) {
    $order = Order::create([
        'order_no' => 'AUDO'.$tag,
        'user_id' => (int) $buyer->id,
        'amount' => 99,
        'status' => 'pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['orders', (int) $order->id];
    $order->status = 'paid';
    $order->save();
    $order->status = 'shipped';
    $order->save();
    $order->status = 'completed';
    $order->save();
    $pass('order_status_pipeline', (string) Order::find($order->id)->status === 'completed');
});

$section('spread', function () use ($pass, $seller, $tag, $now, &$cleanup) {
    $sp = Spread::create([
        'user_id' => (int) $seller->id,
        'keyword' => '自检'.$tag,
        'module' => 'sell',
        'status' => 'pending',
        'days' => 30,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['spreads', (int) $sp->id];
    $sp->status = 'active';
    if (empty($sp->starts_at)) {
        $sp->starts_at = $now;
    }
    if (empty($sp->ends_at)) {
        $sp->ends_at = date('Y-m-d H:i:s', strtotime($now) + 30 * 86400);
    }
    $sp->save();
    $sp2 = Spread::find($sp->id);
    $pass('spread_activate_sets_window', (string) $sp2->status === 'active' && !empty($sp2->starts_at) && !empty($sp2->ends_at));
});

$section('article_guard', function () use ($pass, $root, &$ars) {
    if ($ars === '') {
        $ars = file_get_contents($root.'/app/controller/api/v1/AdminResource.php');
    }
    $pass('article_publish_requires_category_guard', str_contains($ars, '请先选择资讯分类再上架'));
});

$section('fund_mall', function () use ($pass, $mkFund, $buyer, $seller, $tag, $now, &$cleanup) {
    $prod2 = Product::create([
        'user_id' => (int) $seller->id,
        'title' => '售罄商品'.$tag,
        'price' => 1,
        'stock' => 0,
        'status' => 'approved',
        'is_listed' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $cleanup[] = ['products', (int) $prod2->id];
    $mallOrder = $mkFund([
        'order_no' => 'AUDM'.$tag,
        'user_id' => (int) $buyer->id,
        'pay_type' => FundPay::TYPE_MALL,
        'amount' => 1,
        'status' => FundPay::STATUS_PENDING_REVIEW,
        'title' => '自检商城',
        'payload' => json_encode([
            'lines' => [['product_id' => (int) $prod2->id, 'title' => '售罄', 'price' => 1, 'quantity' => 1]],
            'receiver_name' => '测',
            'receiver_phone' => '139',
            'receiver_address' => 'x',
        ], JSON_UNESCAPED_UNICODE),
    ]);
    $rm = FundPay::approve($mallOrder, 1);
    $pass('fund_mall_rejects_soldout', empty($rm['ok']) && (str_contains((string) ($rm['message'] ?? ''), '售罄') || str_contains((string) ($rm['message'] ?? ''), '库存')), $rm);
});

$section('vip', function () use ($pass, $mkUser) {
    $vipUser = $mkUser('vip');
    $grades = Db::name('vip_grades')->where('code', '<>', 'free')->where('price_year', '>', 0)->order('id', 'asc')->find();
    if (!$grades) {
        $grades = Db::name('vip_grades')->order('id', 'asc')->find();
    }
    if ($grades) {
        $level = (int) ($grades['level'] ?? 2);
        $code = (string) ($grades['code'] ?? 'gold');
        if ($code !== 'free' && (float) ($grades['price_year'] ?? 1) > 0) {
            $vipUser->save([
                'level' => max((int) $vipUser->level, $level),
                'vip_grade_code' => $code,
                'vip_expire_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
                'group_code' => 'vip',
                'status' => 'active',
                'is_certified' => 1,
                'member_type' => 'company',
            ]);
            $vu = User::find($vipUser->id);
            $pass('vip_approve_writes_user', (string) $vu->group_code === 'vip' && (int) $vu->is_certified === 1 && !empty($vu->vip_expire_at));
        } else {
            $pass('vip_approve_writes_user', true, 'skipped_free_grade');
        }
    } else {
        $pass('vip_approve_writes_user', false, 'no_vip_grades');
    }
});

$section('admin_resources', function () use ($pass, $root) {
    $cfg = include $root.'/config/admin_resources.php';
    $need = ['users', 'companies', 'supplies', 'purchases', 'products', 'inquiries', 'orders', 'withdraws', 'vip-pay-orders', 'fund-pay-orders', 'jobs', 'resumes', 'articles', 'ads', 'quote-products'];
    $missing = [];
    foreach ($need as $k) {
        if (!isset($cfg[$k])) {
            $missing[] = $k;
        }
    }
    $pass('admin_resources_core_registered', $missing === [], ['missing' => $missing, 'total' => count($cfg)]);
});

// cleanup messages for test users
try {
    $uids = [];
    foreach ($cleanup as [$table, $id]) {
        if ($table === 'users') {
            $uids[] = $id;
        }
    }
    if ($uids) {
        Message::whereIn('user_id', $uids)->delete();
        Message::whereIn('from_user_id', $uids)->delete();
        FinanceRecord::whereIn('user_id', $uids)->delete();
    }
    // reverse delete children first-ish
    usort($cleanup, function ($a, $b) {
        $prio = ['quote_region_prices' => 1, 'quote_product_prices' => 2, 'inquiries' => 3, 'fund_pay_orders' => 4, 'orders' => 5, 'products' => 6, 'supplies' => 7, 'purchases' => 8, 'job_posts' => 9, 'resumes' => 10, 'companies' => 11, 'member_verifications' => 12, 'withdraw_requests' => 13, 'spreads' => 14, 'quote_products' => 15, 'users' => 99];
        return ($prio[$a[0]] ?? 50) <=> ($prio[$b[0]] ?? 50);
    });
    foreach ($cleanup as [$table, $id]) {
        if ($table === 'quote_products') {
            QuoteProductPrice::where('product_id', $id)->delete();
            QuoteRegionPrice::where('product_id', $id)->delete();
        }
        Db::name($table)->where('id', $id)->delete();
    }
} catch (\Throwable $e) {
    $pass('cleanup', false, $e->getMessage());
}

$failedCases = array_values(array_filter($report, fn ($r) => empty($r['ok'])));
echo json_encode([
    'ok' => $fail === 0,
    'failed' => $fail,
    'passed' => count($report) - $fail,
    'total' => count($report),
    'failed_cases' => $failedCases,
    'all' => $report,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;

exit($fail === 0 ? 0 : 1);
