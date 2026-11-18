<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\Favorite as FavoriteModel; use think\Response;
class Favorite extends BaseController {
  public function index(): Response {
    $paginator=FavoriteModel::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
  public function store(): Response {
    $type=$this->request->post('favoritable_type');
    $id=(int)$this->request->post('favoritable_id');
    if(!$type||!$id) return json_error('参数错误',422);
    $item=FavoriteModel::create([
      'user_id'=>$this->request->user->id,
      'favoritable_type'=>$type,
      'favoritable_id'=>$id,
    ]);
    return json(['data'=>$item->toArray()],201);
  }
  public function destroy($id): Response {
    $item=FavoriteModel::where('user_id',$this->request->user->id)->find($id);
    if(!$item) return json_error('Not found',404);
    $item->delete();
    return json_message('ok');
  }
}