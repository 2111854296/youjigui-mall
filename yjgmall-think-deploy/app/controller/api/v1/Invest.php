<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\InvestLead; use app\model\InvestProject; use think\Response;
class Invest extends BaseController {
  public function index(): Response {
    $query=InvestProject::where('status','published')->order('id','desc');
    if($q=$this->request->param('q')) $query->whereLike('title','%'.$q.'%');
    return json(paginate_laravel($query->paginate(['list_rows'=>16])));
  }
  public function show($id): Response {
    $item=InvestProject::where('status','published')->find($id);
    if(!$item) return json_error('Not found',404);
    return json_data($item->toArray());
  }
  public function apply(): Response {
    $data=$this->request->post();
    if(empty($data['project_id'])||empty($data['contact'])) return json_error('请填写完整信息',422);
    $lead=InvestLead::create([
      'project_id'=>$data['project_id'],
      'user_id'=>$this->request->user->id ?? null,
      'name'=>$data['name']??($data['contact']??''),
      'contact'=>$data['contact'],
      'phone'=>$data['phone']??null,
      'company_name'=>$data['company_name']??null,
      'content'=>$data['content']??null,
      'status'=>'pending',
    ]);
    return json(['data'=>$lead->toArray(),'message'=>'已提交'],201);
  }
}