<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\Page as PageModel; use think\Response;
class Page extends BaseController {
  public function show($slug): Response {
    $item=PageModel::where('slug',$slug)->find();
    if(!$item) return json_error('Not found',404);
    return json_data(['slug'=>$item->slug,'title'=>$item->title,'content'=>$item->content]);
  }
}