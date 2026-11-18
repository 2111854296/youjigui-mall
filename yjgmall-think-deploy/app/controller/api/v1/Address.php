<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\Address as AddressModel; use think\Response;
class Address extends BaseController {
  public function index(): Response {
    return json_data(AddressModel::where('user_id',$this->request->user->id)->order('is_default','desc')->order('id','desc')->select()->toArray());
  }
  public function store(): Response {
    $data=$this->request->only(['name','phone','province','city','district','address','is_default']);
    $data['user_id']=$this->request->user->id;
    if(!empty($data['is_default'])) AddressModel::where('user_id',$data['user_id'])->update(['is_default'=>0]);
    $item=AddressModel::create($data);
    return json(['data'=>$item->toArray()],201);
  }
  public function update($id): Response {
    $item=AddressModel::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    $data=$this->request->only(['name','phone','province','city','district','address','is_default']);
    if(!empty($data['is_default'])) AddressModel::where('user_id',$item->user_id)->update(['is_default'=>0]);
    $item->save($data);
    return json_data($item->refresh()->toArray());
  }
  public function destroy($id): Response {
    $item=AddressModel::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    $item->delete();
    return json_message('ok');
  }
}