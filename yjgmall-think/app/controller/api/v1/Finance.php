<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ApiResource;
use app\common\FundPay;
use app\model\AdBooking;
use app\model\FinanceRecord;
use app\model\FundPayOrder;
use app\model\Spread;
use think\Response;

class Finance extends BaseController {
  public function records(): Response {
    $paginator=FinanceRecord::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(array_merge(paginate_laravel($paginator, function ($r) {
      $a = $r->toArray();
      $a['balance_after'] = $a['balance_after'] ?? $a['balance'] ?? null;
      $a['title'] = $a['title'] ?? $a['remark'] ?? '';
      return $a;
    }), ['wallet' => user_wallet($this->request->user)]));
  }

  /** 创建对公充值订单（不再直接加余额） */
  public function charge(): Response {
    $user = $this->request->user;
    $amount = (float) $this->request->post('amount', 0);
    $remark = $this->request->post('remark');
    $result = FundPay::createCharge($user, $amount, $remark ? (string) $remark : null);
    if (empty($result['ok'])) {
      return json_error($result['message'] ?? '下单失败', 422);
    }
    /** @var FundPayOrder $order */
    $order = $result['order'];
    $reused = !empty($result['reused']);

    return json([
      'data' => FundPay::serialize($order),
      'message' => $reused ? '已有进行中的充值订单，已为您打开' : '请在时限内对公转账并上传凭证',
      'reused' => $reused,
    ], $reused ? 200 : 201);
  }

  public function fundOrders(): Response {
    $rows = FundPayOrder::where('user_id', $this->request->user->id)
      ->order('id', 'desc')
      ->limit(30)
      ->select();
    $list = [];
    foreach ($rows as $row) {
      $list[] = FundPay::serialize($row, false);
    }

    return json_data($list);
  }

  public function fundOrder($id): Response {
    $order = FundPayOrder::where('id', (int) $id)->where('user_id', $this->request->user->id)->find();
    if (!$order) {
      $order = FundPayOrder::where('order_no', (string) $id)->where('user_id', $this->request->user->id)->find();
    }
    if (!$order) {
      return json_error('订单不存在', 404);
    }

    return json_data(FundPay::serialize($order));
  }

  public function submitFundVoucher($id): Response {
    $order = FundPayOrder::where('id', (int) $id)->where('user_id', $this->request->user->id)->find();
    if (!$order) {
      return json_error('订单不存在', 404);
    }
    $url = (string) $this->request->post('voucher_url', '');
    $result = FundPay::submitVoucher($order, $url);
    if (empty($result['ok'])) {
      return json_error($result['message'] ?? '提交失败', 422);
    }

    return json(['data' => FundPay::serialize($result['order']), 'message' => '凭证已提交，等待财务审核入账']);
  }

  public function cancelFundOrder($id): Response {
    $order = FundPayOrder::where('id', (int) $id)->where('user_id', $this->request->user->id)->find();
    if (!$order) {
      return json_error('订单不存在', 404);
    }
    $result = FundPay::cancel($order);
    if (empty($result['ok'])) {
      return json_error($result['message'] ?? '取消失败', 422);
    }

    return json(['data' => FundPay::serialize($result['order']), 'message' => '订单已取消']);
  }

  public function spreads(): Response {
    $paginator=Spread::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
  public function storeSpread(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $keyword=trim((string)$this->request->post('keyword', $this->request->post('title', '')));
    if($keyword==='') return json_error('请填写关键词',422);
    $bid=(float)$this->request->post('bid', 0);
    if($bid<1) return json_error('出价至少为 1',422);
    $days=max(1, min(365, (int)$this->request->post('days', 30)));
    $module=(string)$this->request->post('module','sell');
    if(!in_array($module,['sell','buy','company','mall'],true)) $module='sell';
    $row=Spread::create([
      'user_id'=>$this->request->user->id,
      'title'=>mb_substr($keyword,0,64),
      'keyword'=>mb_substr($keyword,0,64),
      'module'=>$module,
      'bid'=>round($bid,2),
      'days'=>$days,
      'target_id'=>$this->request->post('target_id') ?: null,
      'status'=>'pending',
    ]);
    return json(['data'=>$row->toArray(),'message'=>'推广申请已提交，审核通过后按出价参与搜索排序'],201);
  }
  public function adBookings(): Response {
    $paginator=AdBooking::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
  public function bookAd(): Response {
    if ($e = ApiResource::requireEnterprise()) return $e;
    $user = $this->request->user;
    $title = trim((string) $this->request->post('title', ''));
    $place = trim((string) $this->request->post('place_code', ''));
    $phone = trim((string) $this->request->post('contact_phone', ''));
    $contact = trim((string) $this->request->post('contact_name', ''));
    if ($title === '' || $place === '') {
      return json_error('请填写广告位与投放标题', 422);
    }
    if ($phone === '') {
      return json_error('请填写联系电话', 422);
    }
    $row = AdBooking::create([
      'user_id' => $user->id,
      'place_code' => $place,
      'title' => $title,
      'image' => $this->request->post('image') ?: null,
      'link' => $this->request->post('link') ?: null,
      'contact_name' => $contact !== '' ? $contact : ($user->name ?: $user->username),
      'contact_phone' => $phone,
      'remark' => $this->request->post('remark') ?: null,
      'starts_at' => $this->request->post('starts_at') ?: null,
      'ends_at' => $this->request->post('ends_at') ?: null,
      'status' => 'pending',
    ]);
    return json(['data' => $row->toArray()], 201);
  }
}
