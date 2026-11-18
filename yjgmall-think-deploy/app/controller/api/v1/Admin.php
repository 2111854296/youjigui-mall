<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\model\Company; use app\model\Inquiry; use app\model\Order; use app\model\Product;
use app\model\Purchase; use app\model\Supply; use app\model\User;
use think\Response;
class Admin extends BaseController {
  public function dashboard(): Response {
    return json_data([
      'users'=>User::count(),
      'pending_users'=>User::where('status','pending')->count(),
      'supplies_pending'=>Supply::where('status','pending')->count(),
      'purchases_pending'=>Purchase::where('status','pending')->count(),
      'products'=>Product::count(),
      'orders'=>Order::count(),
      'companies'=>Company::count(),
      'inquiries'=>Inquiry::count(),
    ]);
  }
  public function users(): Response {
    $q=User::order('id','desc');
    if($kw=$this->request->param('q')){ $like='%'.$kw.'%'; $q->where(function($w)use($like){ $w->whereLike('name',$like)->whereOr('username','like',$like)->whereOr('email','like',$like)->whereOr('mobile','like',$like)->whereOr('company_name','like',$like); }); }
    if($s=$this->request->param('status')) $q->where('status',$s);
    if($t=$this->request->param('member_type')) $q->where('member_type',$t);
    $paginator=$q->paginate(['list_rows'=>20]);
    return json(['data'=>array_map(fn($u)=>$u->toApiArray(), $paginator->items()),'meta'=>['current_page'=>$paginator->currentPage(),'last_page'=>$paginator->lastPage(),'total'=>$paginator->total()]]);
  }
  public function updateUser($id): Response {
    $user=User::find($id); if(!$user) return json_error('Not found',404);
    $data=$this->request->only(['status','group_code','member_type','is_admin','is_certified','level']);
    if(($data['status']??null)==='active' && empty($data['group_code'])){
      $data['group_code']=(($user->member_type??'')==='company')?'enterprise':'personal';
    }
    $user->save($data);
    return json_data($user->refresh()->toApiArray());
  }
  private function moderateList(string $model): Response {
    $q=$model::order('id','desc');
    if($kw=$this->request->param('q')) $q->whereLike('title','%'.$kw.'%');
    if($s=$this->request->param('status')) $q->where('status',$s);
    $paginator=$q->paginate(['list_rows'=>20]);
    return json(['data'=>array_map(fn($r)=>$r->toArray(), $paginator->items()),'meta'=>['current_page'=>$paginator->currentPage(),'last_page'=>$paginator->lastPage(),'total'=>$paginator->total()]]);
  }
  private function moderateUpdate(string $model, $id): Response {
    $item=$model::find($id); if(!$item) return json_error('Not found',404);
    $item->save($this->request->only(['status','price','stock']));
    return json_data($item->refresh()->toArray());
  }
  public function supplies(): Response { return $this->moderateList(Supply::class); }
  public function updateSupply($id): Response { return $this->moderateUpdate(Supply::class,$id); }
  public function purchases(): Response { return $this->moderateList(Purchase::class); }
  public function updatePurchase($id): Response { return $this->moderateUpdate(Purchase::class,$id); }
  public function products(): Response { return $this->moderateList(Product::class); }
  public function updateProduct($id): Response { return $this->moderateUpdate(Product::class,$id); }
  public function orders(): Response {
    $q=Order::order('id','desc');
    if($s=$this->request->param('status')) $q->where('status',$s);
    $paginator=$q->paginate(['list_rows'=>20]);
    return json(['data'=>array_map(fn($r)=>$r->toArray(), $paginator->items()),'meta'=>['current_page'=>$paginator->currentPage(),'last_page'=>$paginator->lastPage(),'total'=>$paginator->total()]]);
  }
  public function updateOrder($id): Response {
    $item=Order::find($id); if(!$item) return json_error('Not found',404);
    $item->status=$this->request->post('status',$item->status); $item->save();
    return json_data($item->toArray());
  }
}