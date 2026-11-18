<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\Brand as BrandModel; use think\Response;
class Brand extends BaseController {
  public function index(): Response {
    $q=$this->request->param('q');
    $query=BrandModel::where('status','published')->order('sort','asc')->order('id','desc');
    if($q) $query->whereLike('name','%'.$q.'%');
    return json(paginate_laravel($query->paginate(['list_rows'=>20])));
  }
  public function show($brand): Response {
    $item=BrandModel::where('status','published')->where(function($q)use($brand){ $q->where('slug',$brand)->whereOr('id',$brand); })->find();
    if(!$item) return json_error('Not found',404);
    return json_data($item->toArray());
  }
}