<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\AdPlace; use app\model\VipApplication; use app\model\VipGrade; use think\Response;
class Vip extends BaseController {
  public function grades(): Response {
    return json_data(VipGrade::where('is_active',1)->order('sort','asc')->select()->toArray());
  }
  public function adPlaces(): Response {
    return json_data(AdPlace::where('is_active',1)->order('sort','asc')->select()->toArray());
  }
  public function apply(): Response {
    $user=$this->request->user;
    $data=$this->request->post();
    $app=VipApplication::create([
      'user_id'=>$user->id,
      'grade_id'=>$data['grade_id']??null,
      'company_name'=>$data['company_name']??$user->company_name,
      'contact'=>$data['contact']??$user->name,
      'phone'=>$data['phone']??$user->mobile,
      'content'=>$data['content']??null,
      'status'=>'pending',
    ]);
    return json(['data'=>$app->toArray(),'message'=>'申请已提交'],201);
  }
}