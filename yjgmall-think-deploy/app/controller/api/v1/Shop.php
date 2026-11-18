<?php
namespace app\controller\api\v1;
use app\BaseController; use app\common\ApiResource;
use app\model\Company; use app\model\CompanyHonor; use app\model\CompanyLink; use app\model\CompanyNews; use app\model\CompanyPage;
use think\Response;
class Shop extends BaseController {
  private function company() {
    $user=$this->request->user;
    $c=Company::where('user_id',$user->id)->find();
    if(!$c){ $c=Company::create(['user_id'=>$user->id,'name'=>$user->company_name?:$user->name,'slug'=>'u'.$user->id,'status'=>'pending','contact'=>$user->name,'phone'=>$user->mobile]); }
    return $c;
  }
  public function show(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $c->news=CompanyNews::where('company_id',$c->id)->order('id','desc')->select();
    $c->honors=CompanyHonor::where('company_id',$c->id)->select();
    $c->pages=CompanyPage::where('company_id',$c->id)->select();
    $c->links=CompanyLink::where('company_id',$c->id)->select();
    return json_data(ApiResource::company($c,true));
  }
  public function update(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $c->save($this->request->only(['name','logo','template','contact','phone','website','address','area','business','intro','content']));
    return json_data(ApiResource::company($c->refresh()));
  }
  public function storeNews(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $row=CompanyNews::create(['company_id'=>$c->id,'title'=>$this->request->post('title'),'content'=>$this->request->post('content','')]);
    return json(['data'=>$row->toArray()],201);
  }
  public function destroyNews($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    CompanyNews::where('company_id',$c->id)->where('id',$id)->delete();
    return json_message('ok');
  }
  public function storeHonor(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $row=CompanyHonor::create(['company_id'=>$c->id,'title'=>$this->request->post('title'),'image'=>$this->request->post('image'),'authority'=>$this->request->post('authority'),'issued_at'=>$this->request->post('issued_at')]);
    return json(['data'=>$row->toArray()],201);
  }
  public function destroyHonor($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    CompanyHonor::where('company_id',$c->id)->where('id',$id)->delete();
    return json_message('ok');
  }
  public function storePage(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $row=CompanyPage::create(['company_id'=>$c->id,'title'=>$this->request->post('title'),'slug'=>$this->request->post('slug'),'content'=>$this->request->post('content','')]);
    return json(['data'=>$row->toArray()],201);
  }
  public function destroyPage($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    CompanyPage::where('company_id',$c->id)->where('id',$id)->delete();
    return json_message('ok');
  }
  public function storeLink(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $row=CompanyLink::create(['company_id'=>$c->id,'name'=>$this->request->post('name'),'url'=>$this->request->post('url')]);
    return json(['data'=>$row->toArray()],201);
  }
  public function destroyLink($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    CompanyLink::where('company_id',$c->id)->where('id',$id)->delete();
    return json_message('ok');
  }
}