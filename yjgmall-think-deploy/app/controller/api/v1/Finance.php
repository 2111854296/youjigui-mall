<?php
namespace app\controller\api\v1;
use app\BaseController; use app\common\ApiResource;
use app\model\AdBooking; use app\model\FinanceRecord; use app\model\Spread;
use think\Response;
class Finance extends BaseController {
  public function records(): Response {
    $paginator=FinanceRecord::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
  public function charge(): Response {
    $user=$this->request->user;
    $amount=(float)$this->request->post('amount',0);
    if($amount<=0) return json_error('金额无效',422);
    $user->balance=(float)$user->balance+$amount; $user->save();
    $rec=FinanceRecord::create(['user_id'=>$user->id,'type'=>'charge','amount'=>$amount,'balance'=>$user->balance,'remark'=>'账户充值(模拟)','status'=>'done']);
    return json(['data'=>$rec->toArray(),'message'=>'充值成功']);
  }
  public function spreads(): Response {
    $paginator=Spread::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
  public function storeSpread(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $row=Spread::create([
      'user_id'=>$this->request->user->id,
      'title'=>$this->request->post('title'),
      'module'=>$this->request->post('module','sell'),
      'target_id'=>$this->request->post('target_id'),
      'starts_at'=>$this->request->post('starts_at'),
      'ends_at'=>$this->request->post('ends_at'),
      'status'=>'pending',
    ]);
    return json(['data'=>$row->toArray()],201);
  }
  public function adBookings(): Response {
    $paginator=AdBooking::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
  public function bookAd(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $row=AdBooking::create([
      'user_id'=>$this->request->user->id,
      'place_code'=>$this->request->post('place_code'),
      'title'=>$this->request->post('title'),
      'image'=>$this->request->post('image'),
      'link'=>$this->request->post('link'),
      'starts_at'=>$this->request->post('starts_at'),
      'ends_at'=>$this->request->post('ends_at'),
      'status'=>'pending',
    ]);
    return json(['data'=>$row->toArray()],201);
  }
}