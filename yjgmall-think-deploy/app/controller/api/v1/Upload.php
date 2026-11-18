<?php
namespace app\controller\api\v1;
use app\BaseController; use think\Response;
class Upload extends BaseController {
  public function store(): Response {
    $file=$this->request->file('file');
    if(!$file) return json_error('请上传文件',422);
    $savename=\think\facade\Filesystem::disk('public')->putFile('uploads',$file);
    $url='/storage/'.$savename;
    return json(['data'=>['url'=>$url,'path'=>$savename]]);
  }
}