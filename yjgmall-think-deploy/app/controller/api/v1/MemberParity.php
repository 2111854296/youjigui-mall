<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ApiResource;
use app\model\Article; use app\model\AskTicket; use app\model\ChatMessage; use app\model\ChatSession;
use app\model\MailSubscription; use app\model\Product; use app\model\TradeAlert; use app\model\WithdrawRequest;
use think\Response;
class MemberParity extends BaseController {
  public static function groupLabels(): array {
    return ['pending'=>'待审核','personal'=>'个人会员','enterprise'=>'企业会员','company'=>'企业会员','vip'=>'VIP会员','banned'=>'已禁用'];
  }
  public function profileDeep(): Response { return json_data($this->request->user->toApiArray()); }
  public function updateProfileDeep(): Response {
    $user=$this->request->user;
    $fields=['name','gender','qq','area','province','city','district','company_name','company_type','avatar','sound','online_status','truename','bank_name','bank_account'];
    $user->save($this->request->only($fields));
    return json_data($user->refresh()->toApiArray());
  }
  public function updatePasswords(): Response {
    $user=$this->request->user;
    $data=$this->request->post();
    if(!empty($data['password'])){
      if(!password_verify((string)($data['current_password']??''), (string)$user->password)) return json_error('当前密码错误',422);
      $user->password=password_hash($data['password'], PASSWORD_BCRYPT);
    }
    if(!empty($data['pay_password'])){
      $user->pay_password=password_hash($data['pay_password'], PASSWORD_BCRYPT);
    }
    $user->save();
    return json_message('ok');
  }
  public function validations(): Response {
    $u=$this->request->user;
    return json_data([
      'mobile_verified'=>(bool)$u->mobile_verified,
      'bank_verified'=>(bool)$u->bank_verified,
      'truename_verified'=>(bool)$u->truename_verified,
      'truename'=>$u->truename,'bank_name'=>$u->bank_name,'bank_account'=>$u->bank_account,'mobile'=>$u->mobile,
    ]);
  }
  public function submitValidation(): Response {
    $user=$this->request->user;
    $type=$this->request->post('type');
    $data=$this->request->only(['truename','bank_name','bank_account','mobile']);
    $user->save($data);
    if($type==='mobile') $user->mobile_verified=1;
    if($type==='bank') $user->bank_verified=1;
    if($type==='truename') $user->truename_verified=1;
    $user->save();
    return json_message('已提交');
  }
  public function oauth(): Response {
    $u=$this->request->user;
    return json_data(['oauth_qq'=>(bool)$u->oauth_qq,'oauth_weibo'=>(bool)$u->oauth_weibo]);
  }
  public function bindOauth(): Response {
    $user=$this->request->user;
    $provider=$this->request->post('provider');
    if($provider==='qq') $user->oauth_qq=1;
    if($provider==='weibo') $user->oauth_weibo=1;
    $user->save();
    return json_message('ok');
  }
  public function chats(): Response {
    $uid=$this->request->user->id;
    $rows=ChatSession::where('user_id',$uid)->whereOr('peer_id',$uid)->order('id','desc')->select();
    return json_data($rows->toArray());
  }
  public function startChat(): Response {
    $peer=(int)$this->request->post('peer_id');
    $uid=$this->request->user->id;
    if(!$peer||$peer===$uid) return json_error('无效会话',422);
    $s=ChatSession::where(function($q)use($uid,$peer){ $q->where([['user_id','=',$uid],['peer_id','=',$peer]]); })->find();
    if(!$s) $s=ChatSession::create(['user_id'=>$uid,'peer_id'=>$peer]);
    return json(['data'=>$s->toArray()],201);
  }
  public function chatMessages($id): Response {
    $uid=$this->request->user->id;
    $s=ChatSession::find($id);
    if(!$s || ((int)$s->user_id!==$uid && (int)$s->peer_id!==$uid)) return json_error('Forbidden',403);
    $rows=ChatMessage::where('session_id',$id)->order('id','asc')->select();
    return json_data($rows->toArray());
  }
  public function sendChat($id): Response {
    $uid=$this->request->user->id;
    $s=ChatSession::find($id);
    if(!$s || ((int)$s->user_id!==$uid && (int)$s->peer_id!==$uid)) return json_error('Forbidden',403);
    $to=(int)$s->user_id===$uid ? (int)$s->peer_id : (int)$s->user_id;
    $msg=ChatMessage::create(['session_id'=>$id,'from_user_id'=>$uid,'to_user_id'=>$to,'content'=>$this->request->post('content',''),'is_read'=>0]);
    return json(['data'=>$msg->toArray()],201);
  }
  public function alerts(): Response {
    return json_data(TradeAlert::where('user_id',$this->request->user->id)->order('id','desc')->select()->toArray());
  }
  public function storeAlert(): Response {
    $row=TradeAlert::create(['user_id'=>$this->request->user->id,'keyword'=>$this->request->post('keyword'),'module'=>$this->request->post('module','sell'),'status'=>'active']);
    return json(['data'=>$row->toArray()],201);
  }
  public function destroyAlert($id): Response {
    TradeAlert::where('user_id',$this->request->user->id)->where('id',$id)->delete();
    return json_message('ok');
  }
  public function mailSub(): Response {
    $row=MailSubscription::where('user_id',$this->request->user->id)->find();
    return json_data($row?$row->toArray():['enabled'=>false,'modules'=>[]]);
  }
  public function saveMailSub(): Response {
    $user=$this->request->user;
    $row=MailSubscription::where('user_id',$user->id)->find();
    $data=['user_id'=>$user->id,'enabled'=>(int)$this->request->post('enabled',1),'modules'=>json_encode($this->request->post('modules',[]))];
    if($row) $row->save($data); else $row=MailSubscription::create($data);
    return json_data($row->toArray());
  }
  public function withdraws(): Response {
    $paginator=WithdrawRequest::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
  public function storeWithdraw(): Response {
    $user=$this->request->user;
    $amount=(float)$this->request->post('amount',0);
    if($amount<=0 || $amount>(float)$user->balance) return json_error('金额无效',422);
    $row=WithdrawRequest::create(['user_id'=>$user->id,'amount'=>$amount,'bank_name'=>$this->request->post('bank_name',$user->bank_name),'bank_account'=>$this->request->post('bank_account',$user->bank_account),'status'=>'pending']);
    return json(['data'=>$row->toArray()],201);
  }
  public function addDeposit(): Response {
    $user=$this->request->user;
    $amount=(float)$this->request->post('amount',0);
    if($amount<=0) return json_error('金额无效',422);
    $user->deposit=(float)($user->deposit??0)+$amount; $user->save();
    return json_data(['deposit'=>(string)$user->deposit]);
  }
  public function buyPoints(): Response {
    $user=$this->request->user;
    $points=(int)$this->request->post('points',0);
    $cost=$points; // 1:1 mock
    if($points<=0 || $cost>(float)$user->balance) return json_error('积分购买失败',422);
    $user->balance=(float)$user->balance-$cost;
    $user->points=(int)$user->points+$points;
    $user->save();
    return json_data(['points'=>(int)$user->points,'balance'=>(string)$user->balance]);
  }
  public function products(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $paginator=Product::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>15]);
    return json(paginate_laravel($paginator, fn($p)=>ApiResource::product($p)));
  }
  public function showProduct($id): Response {
    $item=Product::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    return json_data(ApiResource::product($item));
  }
  public function storeProduct(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $data=$this->request->only(['title','thumb','price','stock','unit','summary','content','category_id']);
    $data['user_id']=$this->request->user->id; $data['status']='pending';
    $item=Product::create($data);
    return json(['data'=>ApiResource::product($item)],201);
  }
  public function updateProduct($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $item=Product::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    $item->save($this->request->only(['title','thumb','price','stock','unit','summary','content','category_id']));
    return json_data(ApiResource::product($item->refresh()));
  }
  public function destroyProduct($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $item=Product::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    $item->delete(); return json_message('ok');
  }
  public function articles(): Response {
    $paginator=Article::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>15]);
    return json(paginate_laravel($paginator, fn($a)=>ApiResource::article($a)));
  }
  public function showArticle($id): Response {
    $item=Article::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    return json_data(ApiResource::article($item));
  }
  public function storeArticle(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $data=$this->request->only(['title','thumb','author','summary','content','type','category_id']);
    $data['user_id']=$this->request->user->id; $data['status']='draft';
    $item=Article::create($data);
    return json(['data'=>ApiResource::article($item)],201);
  }
  public function updateArticle($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $item=Article::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    $item->save($this->request->only(['title','thumb','author','summary','content','type','category_id','status']));
    return json_data(ApiResource::article($item->refresh()));
  }
  public function destroyArticle($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $item=Article::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    $item->delete(); return json_message('ok');
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