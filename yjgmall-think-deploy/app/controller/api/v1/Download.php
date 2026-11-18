<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\Download as DownloadModel; use think\Response;
class Download extends BaseController {
  public function index(): Response {
    $q=$this->request->param('q');
    $query=DownloadModel::where('status','published')->order('id','desc');
    if($q) $query->whereLike('title','%'.$q.'%');
    return json(paginate_laravel($query->paginate(['list_rows'=>20])));
  }
  public function show($id): Response {
    $item=DownloadModel::where('status','published')->find($id);
    if(!$item) return json_error('Not found',404);
    return json_data($item->toArray());
  }
  public function hit($id): Response {
    $item=DownloadModel::find($id);
    if(!$item) return json_error('Not found',404);
    $item->downloads=(int)($item->downloads??0)+1; $item->save();
    return json(['message'=>'ok','data'=>['downloads'=>(int)$item->downloads]]);
  }
}