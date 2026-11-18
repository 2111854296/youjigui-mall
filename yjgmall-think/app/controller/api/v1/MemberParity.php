<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ApiResource;
use app\model\Article; use app\model\AskTicket; use app\model\ChatMessage; use app\model\ChatSession;
use app\model\MailSubscription; use app\model\Product; use app\model\TradeAlert; use app\model\User; use app\model\WithdrawRequest;
use app\model\FinanceRecord;
use think\facade\Db;
use think\Response;
class MemberParity extends BaseController {
  public static function groupLabels(): array {
    return ['pending'=>'待审核','personal'=>'个人会员','enterprise'=>'企业会员','company'=>'企业会员','vip'=>'VIP会员','banned'=>'已禁用'];
  }
  public function profileDeep(): Response {
    $user=$this->request->user;
    $labels=self::groupLabels();
    return json(['data'=>$user->toApiArray(),'groups'=>$labels,'group_label'=>$labels[$user->group_code??'personal']??'个人会员']);
  }
  public function updateProfileDeep(): Response {
    $user=$this->request->user;
    $name=trim((string)$this->request->post('name',''));
    if($name!=='' && !\app\common\InputCheck::realName($name)) return json_error('请用真实姓名',422);
    $mobile=trim((string)$this->request->post('mobile',''));
    if($mobile!=='' && !\app\common\InputCheck::mobile($mobile)) return json_error('请填写11位手机号',422);
    $email=trim((string)$this->request->post('email',''));
    if($email!=='' && !\app\common\InputCheck::email($email)) return json_error('请填写正确的邮箱',422);
    $fields=['name','gender','qq','area','province','city','district','company_name','company_type','avatar','sound','online_status','truename','bank_name','bank_account','email','mobile'];
    $user->save($this->request->only($fields));
    return json_data($user->refresh()->toApiArray());
  }
  public function updatePasswords(): Response {
    $user=$this->request->user;
    $data=$this->request->post();
    $changed=[];
    if(!empty($data['password'])){
      $next=(string)$data['password'];
      if(($data['password_confirmation']??'')!==$next) return json_error('两次登录密码不一致',422);
      if(strlen($next)<6) return json_error('登录密码至少 6 位',422);
      if(!password_verify((string)($data['current_password']??''), (string)$user->password)) return json_error('现有登录密码不正确',422);
      $user->password=password_hash($next, PASSWORD_BCRYPT);
      $changed[]='登录密码';
    }
    if(!empty($data['pay_password'])){
      $next=(string)$data['pay_password'];
      if(($data['pay_password_confirmation']??'')!==$next) return json_error('两次支付密码不一致',422);
      if(strlen($next)<6) return json_error('支付密码至少 6 位',422);
      $has=trim((string)($user->pay_password??''))!=='';
      if($has && !password_verify((string)($data['current_pay_password']??''), (string)$user->pay_password)) return json_error('现有支付密码不正确',422);
      $user->pay_password=password_hash($next, PASSWORD_BCRYPT);
      $changed[]='支付密码';
    }
    if(!$changed) return json_error('请填写要修改的密码',422);
    $user->save();
    return json_message(implode('、', $changed).'已更新');
  }
  public function validations(): Response {
    $u=$this->request->user;
    $latest=[];
    foreach(['mobile','truename','bank'] as $type){
      $row=\app\model\MemberVerification::where('user_id',$u->id)->where('type',$type)->order('id','desc')->find();
      $latest[$type.'_status']=$row?(string)$row->status:'none';
      $latest[$type.'_remark']=$row?(string)($row->admin_remark??''):'';
    }
    return json_data(array_merge([
      'mobile_verified'=>(bool)$u->mobile_verified,
      'bank_verified'=>(bool)$u->bank_verified,
      'truename_verified'=>(bool)$u->truename_verified,
      'truename'=>$u->truename,'id_card'=>$u->id_card ?? '','bank_name'=>$u->bank_name,'bank_account'=>$u->bank_account,'mobile'=>$u->mobile,
    ], $latest));
  }
  public function sendMobileCode(): Response {
    $mobile=trim((string)$this->request->post('mobile',''));
    $result=\app\common\SmsCode::send($mobile, (int)$this->request->user->id);
    if(empty($result['ok'])) return json_error($result['message']??'发送失败',422);
    return json(['message'=>$result['message']??'验证码已发送','data'=>['sent'=>true]]);
  }
  public function submitValidation(): Response {
    $user=$this->request->user;
    $type=(string)$this->request->post('type');
    if(!in_array($type,['mobile','truename','bank'],true)) return json_error('认证类型无效',422);
    $mobile=trim((string)$this->request->post('mobile',$user->mobile));
    $truename=trim((string)$this->request->post('truename',''));
    $bankName=trim((string)$this->request->post('bank_name',''));
    $bankAccount=trim((string)$this->request->post('bank_account',''));
    $idCard=strtoupper(trim((string)$this->request->post('id_card','')));
    if($type==='mobile'){
      if(!\app\common\InputCheck::mobile($mobile)) return json_error('请填写11位手机号',422);
      $code=trim((string)$this->request->post('sms_code',''));
      if(!\app\common\SmsCode::check($mobile,$code)) return json_error('验证码不正确或已过期',422);
      $user->mobile=$mobile;
      $user->mobile_verified=0;
    }elseif($type==='truename'){
      if(!\app\common\InputCheck::realName($truename)) return json_error('请用真实姓名',422);
      if(!\app\common\InputCheck::idCard($idCard)) return json_error('请填写正确的18位身份证号码',422);
      $user->truename=$truename;
      $user->id_card=$idCard;
      $user->truename_verified=0;
    }else{
      if($bankName==='') return json_error('请填写开户行',422);
      if(!\app\common\InputCheck::bankAccount($bankAccount)) return json_error('请填写正确的银行账号',422);
      if($truename!=='' && !\app\common\InputCheck::realName($truename)) return json_error('请用真实姓名',422);
      $user->bank_name=$bankName;
      $user->bank_account=$bankAccount;
      if($truename!=='') $user->truename=$truename;
      $user->bank_verified=0;
    }
    $user->save();
    $pending=\app\model\MemberVerification::where('user_id',$user->id)->where('type',$type)->where('status','pending')->find();
    $payload=[
      'user_id'=>$user->id,
      'type'=>$type,
      'status'=>'pending',
      'mobile'=>$type==='mobile'?$mobile:null,
      'truename'=>$truename!==''?$truename:null,
      'id_card'=>$idCard!==''?$idCard:null,
      'bank_name'=>$bankName!==''?$bankName:null,
      'bank_account'=>$bankAccount!==''?$bankAccount:null,
      'admin_remark'=>null,
      'reviewed_at'=>null,
    ];
    if($pending) $pending->save($payload); else \app\model\MemberVerification::create($payload);
    $message=$type==='mobile'?'短信验证成功，已提交审核':'已提交，等待后台审核';
    return json_message($message);
  }
  public function oauth(): Response {
    $u=$this->request->user;
    return json([
      'data' => [
        'oauth_qq' => (bool) $u->oauth_qq,
        'oauth_weibo' => (bool) $u->oauth_weibo,
        'qq' => (bool) $u->oauth_qq,
        'weibo' => (bool) $u->oauth_weibo,
        'available' => false,
      ],
      'message' => '第三方一键登录暂未开通，请使用账号密码登录',
    ]);
  }
  public function bindOauth(): Response {
    return json_error('第三方一键登录暂未开通，请使用账号密码登录', 422);
  }
  public function chats(): Response {
    $uid = (int) $this->request->user->id;
    // 与 startChat 一致用 whereRaw，避免 whereOr 闭包在 TP8 下生成异常 SQL
    $rows = ChatSession::whereRaw('(user_id = ? OR peer_id = ?)', [$uid, $uid])
      ->order('id', 'desc')
      ->select();

    $out = [];
    foreach ($rows as $s) {
      $peerId = (int) $s->user_id === $uid ? (int) $s->peer_id : (int) $s->user_id;
      $peer = User::find($peerId);
      $last = null;
      try {
        $last = ChatMessage::where('session_id', $s->id)->order('id', 'desc')->find();
      } catch (\Throwable $e) {
        $last = null;
      }
      $out[] = [
        'id' => (int) $s->id,
        'user_id' => (int) $s->user_id,
        'peer_id' => $peerId,
        'last_message_at' => $last ? ($last->created_at ?? null) : ($s->updated_at ?? $s->created_at ?? null),
        'last_message' => $last ? (string) ($last->content ?? '') : '',
        'peer' => $peer ? [
          'id' => (int) $peer->id,
          'name' => $peer->name ?: $peer->username ?: ('会员' . $peer->id),
          'username' => $peer->username,
          'company_name' => $peer->company_name ?? null,
        ] : [
          'id' => $peerId,
          'name' => '会员' . $peerId,
          'username' => null,
          'company_name' => null,
        ],
      ];
    }
    return json_data($out);
  }
  public function startChat(): Response {
    return json_error('站内交谈已关闭，请通过询盘留言，由平台审核后转达企业', 403);
  }
  public function chatMessages($id): Response {
    $uid = (int) $this->request->user->id;
    $s = ChatSession::find($id);
    if (!$s || ((int) $s->user_id !== $uid && (int) $s->peer_id !== $uid)) {
      return json_error('Forbidden', 403);
    }
    ChatMessage::where('session_id', $id)->where('to_user_id', $uid)->where('is_read', 0)->update(['is_read' => 1]);
    $rows = ChatMessage::where('session_id', $id)->order('id', 'asc')->select();
    return json_data($rows->toArray());
  }
  public function markNoticeSeen(): Response {
    $type = (string) $this->request->post('type', '');
    if (!in_array($type, ['chat', 'favored', 'announce'], true)) {
      return json_error('无效类型', 422);
    }
    $this->request->user->markNoticeSeen($type);
    return json_message('ok');
  }
  public function sendChat($id): Response {
    return json_error('站内交谈已关闭，请通过询盘留言，由平台审核后转达企业', 403);
  }
  public function alerts(): Response {
    return json_data(TradeAlert::where('user_id',$this->request->user->id)->order('id','desc')->select()->toArray());
  }
  public function storeAlert(): Response {
    $keyword=trim((string)$this->request->post('keyword',''));
    if($keyword==='') return json_error('请填写关键词',422);
    $module=(string)$this->request->post('module','sell');
    if(!in_array($module,['sell','buy','mall','company'],true)) $module='sell';
    $min=$this->request->post('price_min');
    $max=$this->request->post('price_max');
    $row=TradeAlert::create([
      'user_id'=>$this->request->user->id,
      'keyword'=>mb_substr($keyword,0,128),
      'module'=>$module,
      'area'=>trim((string)$this->request->post('area',''))?:null,
      'price_min'=>($min===''||$min===null)?null:(float)$min,
      'price_max'=>($max===''||$max===null)?null:(float)$max,
      'status'=>'active',
    ]);
    return json(['data'=>$row->toArray()],201);
  }
  public function destroyAlert($id): Response {
    TradeAlert::where('user_id',$this->request->user->id)->where('id',$id)->delete();
    return json_message('ok');
  }
  public function mailSub(): Response {
    $user=$this->request->user;
    $row=MailSubscription::where('user_id',$user->id)->find();
    return json_data($this->mailPayload($row, (string)($user->email ?? '')));
  }
  public function saveMailSub(): Response {
    $user=$this->request->user;
    $smtpReady = \app\common\SmtpMail::configured();
    $topics=$this->request->post('topics', $this->request->post('modules', []));
    if(!is_array($topics)) $topics=[];
    $topics=array_values(array_intersect(['sell','buy','news','quote'], array_map('strval', $topics)));
    $flag=$this->request->post('is_active', $this->request->post('enabled', 1));
    $enabled=is_bool($flag) ? $flag : !in_array((string)$flag, ['0','false',''], true);
    // 未配发信邮箱时不允许真正开启订阅，避免用户以为会收到邮件
    if ($enabled && !$smtpReady) {
      return json_error('邮件服务暂未开通，订阅功能暂时不可用', 422);
    }
    $email=trim((string)$this->request->post('email', ''));
    if($email!=='' && !filter_var($email, FILTER_VALIDATE_EMAIL)) return json_error('邮箱格式不正确',422);
    if($email==='') $email=(string)($user->email ?? '');
    $row=MailSubscription::where('user_id',$user->id)->find();
    $data=['user_id'=>$user->id,'enabled'=>$enabled?1:0,'modules'=>json_encode($topics, JSON_UNESCAPED_UNICODE),'email'=>$email];
    if($row) $row->save($data); else $row=MailSubscription::create($data);
    return json_data($this->mailPayload($row->refresh(), (string)($user->email ?? '')));
  }
  private function mailPayload($row, string $fallbackEmail): array {
    $smtpReady = \app\common\SmtpMail::configured();
    $notice = $smtpReady
      ? '订阅偏好已保存；系统会按主题发送邮件摘要。'
      : '订阅偏好已保存。当前站点尚未配置发信邮箱，暂不会真正发邮件；贸易提醒仍会以站内信通知。';
    if(!$row) {
      return [
        'enabled'=>false,'is_active'=>false,'modules'=>[],'topics'=>[],
        'email'=>$fallbackEmail,'smtp_ready'=>$smtpReady,'mail_notice'=>$notice,
      ];
    }
    $modules=$row->modules;
    if(is_string($modules)) $modules=json_decode($modules, true);
    if(!is_array($modules)) $modules=[];
    $email=trim((string)($row->email ?? '')) ?: $fallbackEmail;
    $on=(bool)$row->enabled;
    return [
      'enabled'=>$on,'is_active'=>$on,'modules'=>$modules,'topics'=>$modules,
      'email'=>$email,'smtp_ready'=>$smtpReady,'mail_notice'=>$notice,
    ];
  }
  public function withdraws(): Response {
    $paginator=WithdrawRequest::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(array_merge(paginate_laravel($paginator), ['wallet'=>user_wallet($this->request->user)]));
  }
  public function storeWithdraw(): Response {
    $user=$this->request->user;
    $amount=(float)$this->request->post('amount',0);
    if($amount<=0) return json_error('金额无效',422);
    $accountName=trim((string)$this->request->post('account_name',''));
    $bankAccount=trim((string)$this->request->post('bank_account',$user->bank_account));
    if($accountName!=='' && !\app\common\InputCheck::realName($accountName)) return json_error('请用真实姓名',422);
    if($bankAccount!=='' && !\app\common\InputCheck::bankAccount($bankAccount)) return json_error('请填写正确的银行账号',422);
    $row=null;
    Db::startTrans();
    try {
      $locked=User::where('id',$user->id)->lock(true)->find();
      if(!$locked) { Db::rollback(); return json_error('用户不存在',404); }
      if($amount>(float)$locked->balance) { Db::rollback(); return json_error('金额无效或余额不足',422); }
      $locked->balance=round((float)$locked->balance-$amount,2);
      $locked->save();
      FinanceRecord::create([
        'user_id'=>(int)$locked->id,
        'type'=>'cash',
        'amount'=>-$amount,
        'balance_after'=>$locked->balance,
        'title'=>'提现申请冻结',
        'remark'=>'待财务打款审核',
      ]);
      $row=WithdrawRequest::create([
        'user_id'=>$locked->id,
        'amount'=>$amount,
        'bank_name'=>$this->request->post('bank_name',$locked->bank_name),
        'bank_account'=>$this->request->post('bank_account',$locked->bank_account),
        'status'=>'pending',
      ]);
      Db::commit();
      $user=$locked;
    } catch (\Throwable $e) {
      Db::rollback();
      return json_error('提现申请失败，请稍后重试',500);
    }
    return json(['data'=>$row->toArray(),'wallet'=>user_wallet($user)],201);
  }
  public function addDeposit(): Response {
    $user=$this->request->user;
    $amount=(float)$this->request->post('amount',0);
    if($amount<=0) return json_error('金额无效',422);
    Db::startTrans();
    try {
      $locked=User::where('id',$user->id)->lock(true)->find();
      if(!$locked) { Db::rollback(); return json_error('用户不存在',404); }
      if($amount>(float)$locked->balance) { Db::rollback(); return json_error('余额不足，请先对公充值',422); }
      $locked->balance=round((float)$locked->balance-$amount,2);
      $locked->deposit=(float)($locked->deposit??0)+$amount;
      $locked->save();
      FinanceRecord::create([
        'user_id'=>(int)$locked->id,
        'type'=>'deposit',
        'amount'=>-$amount,
        'balance_after'=>$locked->balance,
        'title'=>'转入保证金',
        'remark'=>'从可用余额划转',
      ]);
      Db::commit();
      $user=$locked;
    } catch (\Throwable $e) {
      Db::rollback();
      return json_error('划转失败，请稍后重试',500);
    }
    return json_data(['deposit'=>(string)$user->deposit,'balance'=>(string)$user->balance]);
  }
  public function buyPoints(): Response {
    $user=$this->request->user;
    $points=(int)$this->request->post('points',0);
    $cost=round($points * 0.1, 2);
    if($points<=0 || $cost<=0) return json_error('积分数量无效',422);
    $result=\app\common\FundPay::createIntent($user,\app\common\FundPay::TYPE_POINTS,$cost,'购买'.$points.'积分','points:'.$points,['points'=>$points],$points.' 积分');
    if(empty($result['ok']) || empty($result['order'])) return json_error($result['message'] ?? '下单失败',422);
    return json(['data'=>\app\common\FundPay::serialize($result['order']),'message'=>!empty($result['reused'])?'已有进行中的付款订单':'请对公转账并上传付款凭证']);
  }
  private function assertProductTitle(array $data): ?Response {
    if (!array_key_exists('title', $data)) return null;
    $title = trim((string) $data['title']);
    if (mb_strlen($title) > 30) return json_error('标题请控制在30字以内', 422);
    return null;
  }
  private function applyPriceTiers(array &$data): ?Response {
    $parsed = \app\common\PriceTiers::normalize($this->request->post('price_tiers'));
    if (!$parsed['ok']) return json_error($parsed['message'], 422);
    if ($parsed['tiers']) {
      $data['price_tiers'] = json_encode($parsed['tiers'], JSON_UNESCAPED_UNICODE);
      $data['price'] = $parsed['price'];
    } else {
      $data['price_tiers'] = null;
    }
    return null;
  }
  private function applyDetailImages(array &$data): ?Response {
    $raw = $this->request->post('detail_images', null);
    if ($raw === null) return null;
    if (is_string($raw)) {
      $decoded = json_decode($raw, true);
      $raw = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($raw)) return json_error('详情图格式不正确', 422);
    $urls = [];
    foreach ($raw as $src) {
      $src = trim((string) $src);
      if ($src === '') continue;
      if (strlen($src) > 255) return json_error('详情图地址过长', 422);
      $urls[] = $src;
    }
    if (count($urls) > 5) return json_error('详情图最多5张', 422);
    $data['detail_images'] = $urls ? json_encode(array_values($urls), JSON_UNESCAPED_UNICODE) : null;
    return null;
  }
  private function normalizeProductImages(array $data): array {
    foreach (['thumb', 'thumb1', 'thumb2'] as $key) {
      if (!array_key_exists($key, $data)) continue;
      $data[$key] = trim((string) $data[$key]) === '' ? null : trim((string) $data[$key]);
    }
    return $data;
  }
  public function products(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $q = trim((string) $this->request->get('q', ''));
    $sort = (string) $this->request->get('sort', 'date_desc');
    $query = Product::where('user_id', $this->request->user->id);
    if ($q !== '') {
      $query->whereLike('title', '%'.$q.'%');
    }
    $dir = $sort === 'date_asc' ? 'asc' : 'desc';
    $paginator = $query->order('created_at', $dir)->order('id', $dir)->paginate(['list_rows' => 100]);
    $payload = paginate_laravel($paginator, fn($p)=>ApiResource::product($p));
    $user = $this->request->user;
    $showcase = \app\common\ShopShowcase::meta($user);
    $visible = array_flip(\app\common\ShopShowcase::visibleIds((int) $user->id, (int) $showcase['limit']));
    foreach ($payload['data'] as &$row) {
      $row['showcase_on'] = isset($visible[(int) ($row['id'] ?? 0)]);
    }
    unset($row);
    $payload['showcase'] = $showcase;
    return json($payload);
  }
  public function showcaseProduct(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $user = $this->request->user;
    $id = (int) $this->request->post('id', 0);
    $on = (int) $this->request->post('on', 0) === 1;
    $item = Product::where('user_id', $user->id)->find($id);
    if(!$item) return json_error('商品不存在', 404);
    if (!$on) {
      $item->save(['is_showcase' => 0]);
      return json(['message' => '已取消展示', 'is_showcase' => 0, 'showcase' => \app\common\ShopShowcase::meta($user)]);
    }
    $meta = \app\common\ShopShowcase::meta($user);
    if ((int) $meta['limit'] < 1) {
      return json_error('普通会员不能在商品页展示本店商品。白银会员可展示2款，黄金3款，星钻5款', 422);
    }
    if ((string) $item->status !== 'approved' || (int) ($item->is_listed ?? 0) !== 1) {
      return json_error('请先上架并通过审核，才能展示到商品页', 422);
    }
    $used = (int) Product::where('user_id', $user->id)->where('is_showcase', 1)->where('id', '<>', $id)->count();
    if ($used >= (int) $meta['limit']) {
      $name = (string) $meta['grade_name'];
      $limit = (int) $meta['limit'];
      return json_error("{$name}最多展示{$limit}款，请先取消其他商品", 422);
    }
    $item->save(['is_showcase' => 1]);
    return json(['message' => '已设为商品页展示', 'is_showcase' => 1, 'showcase' => \app\common\ShopShowcase::meta($user)]);
  }
  public function sampleProduct(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $user = $this->request->user;
    $id = (int) $this->request->post('id', 0);
    $on = (int) $this->request->post('on', 0) === 1;
    $item = Product::where('user_id', $user->id)->find($id);
    if(!$item) return json_error('商品不存在', 404);
    if (!$on) {
      $item->save(['is_sample' => 0]);
      return json(['message' => '已移出样品专区', 'is_sample' => 0]);
    }
    if ((string) $item->status !== 'approved' || (int) ($item->is_listed ?? 0) !== 1) {
      return json_error('请先上架并通过审核，才能放入样品专区', 422);
    }
    $item->save(['is_sample' => 1]);
    return json(['message' => '已放入样品专区', 'is_sample' => 1]);
  }
  public function showProduct($id): Response {
    $item=Product::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    return json_data(ApiResource::product($item));
  }
  public function storeProduct(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $data=$this->request->only(['title','thumb','thumb1','thumb2','price','stock','unit','summary','content','category_id']);
    $data=$this->normalizeProductImages($data);
    if ($e = $this->assertProductTitle($data)) return $e;
    if ($e = $this->applyPriceTiers($data)) return $e;
    if ($e = $this->applyDetailImages($data)) return $e;
    $data['user_id']=$this->request->user->id; $data['status']='pending'; $data['is_listed']=0;
    $item=Product::create($data);
    return json(['data'=>ApiResource::product($item)],201);
  }
  public function updateProduct($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $item=Product::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    $fields = $this->normalizeProductImages($this->request->only(['title','thumb','thumb1','thumb2','price','stock','unit','summary','content','category_id']));
    if ($e = $this->assertProductTitle($fields)) return $e;
    if ($e = $this->applyPriceTiers($fields)) return $e;
    if ($e = $this->applyDetailImages($fields)) return $e;
    // 改文重新进审并暂时下架，避免绕过复审
    $fields['status'] = 'pending';
    $fields['is_listed'] = 0;
    $item->save($fields);
    return json_data(ApiResource::product($item->refresh()));
  }
  public function shelfProducts(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $ids = $this->request->post('ids', []);
    if (!is_array($ids) || !$ids) return json_error('请选择商品', 422);
    $listed = (int) $this->request->post('listed', 0) === 1 ? 1 : 0;
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    if (!$ids) return json_error('请选择商品', 422);
    $uid = $this->request->user->id;
    if ($listed) {
      // 已通过审核的商品可重新上架；下架/待审不可自行改回 approved
      $n = Product::where('user_id', $uid)->whereIn('id', $ids)->where('status', 'approved')->update(['is_listed' => 1]);
      $blocked = Product::where('user_id', $uid)->whereIn('id', $ids)->where('status', '<>', 'approved')->count();
      if ($n <= 0 && $blocked > 0) {
        return json_error('仅审核通过的商品可上架；待审或已下架请等待平台审核', 422);
      }
    } else {
      $n = Product::where('user_id', $uid)->whereIn('id', $ids)->update(['is_listed' => 0, 'is_showcase' => 0, 'is_sample' => 0]);
    }
    return json(['message' => $listed ? '已上架' : '已下架', 'updated' => $n]);
  }
  public function destroyProduct($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $item=Product::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    $item->delete(); return json_message('ok');
  }
  /** 会员仅可发「技术分享」，归入前台信息资讯·技术分享栏目；其余资讯仅后台发 */
  private function memberTechCategoryId(): ?int {
    static $id = null;
    if ($id !== null) return $id ?: null;
    $row = \app\model\Category::where('module', 'article')->where('slug', 'news-tech')->where('is_active', 1)->find();
    $id = $row ? (int) $row->id : 0;
    return $id ?: null;
  }
  private function memberTechArticlesQuery() {
    $catId = $this->memberTechCategoryId();
    $q = Article::where('user_id', $this->request->user->id);
    if ($catId) $q->where('category_id', $catId);
    else $q->whereRaw('1=0');
    return $q;
  }
  public function articles(): Response {
    $paginator = $this->memberTechArticlesQuery()->order('id', 'desc')->paginate(['list_rows' => 15]);
    return json(paginate_laravel($paginator, fn ($a) => ApiResource::article($a)));
  }
  public function showArticle($id): Response {
    $item = $this->memberTechArticlesQuery()->find($id);
    if (!$item) return json_error('Not found', 404);
    return json_data(ApiResource::article($item));
  }
  public function storeArticle(): Response {
    if ($e = ApiResource::requireEnterprise()) return $e;
    $catId = $this->memberTechCategoryId();
    if (!$catId) return json_error('技术分享栏目未配置', 500);
    $data = $this->request->only(['title', 'thumb', 'author', 'summary', 'content']);
    $data['user_id'] = $this->request->user->id;
    $data['category_id'] = $catId;
    $data['type'] = 'news';
    $data['status'] = 'pending';
    $item = Article::create($data);
    return json(['data' => ApiResource::article($item), 'message' => '已提交，等待审核'], 201);
  }
  public function updateArticle($id): Response {
    if ($e = ApiResource::requireEnterprise()) return $e;
    $item = $this->memberTechArticlesQuery()->find($id);
    if (!$item) return json_error('Not found', 404);
    $data = $this->request->only(['title', 'thumb', 'author', 'summary', 'content']);
    $data['category_id'] = $this->memberTechCategoryId();
    $data['type'] = 'news';
    $data['status'] = 'pending';
    $item->save($data);
    return json_data(ApiResource::article($item->refresh()));
  }
  public function destroyArticle($id): Response {
    if ($e = ApiResource::requireEnterprise()) return $e;
    $item = $this->memberTechArticlesQuery()->find($id);
    if (!$item) return json_error('Not found', 404);
    $item->delete();
    return json_message('ok');
  }
  public function asks(): Response {
    $paginator=AskTicket::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
  public function storeAsk(): Response {
    $row=AskTicket::create([
      'user_id'=>$this->request->user->id,
      'title'=>$this->request->post('title'),
      'content'=>$this->request->post('content'),
      'contact_name'=>$this->request->post('contact_name'),
      'contact_phone'=>$this->request->post('contact_phone'),
      'status'=>'pending',
    ]);
    return json(['data'=>$row->toArray()],201);
  }
}