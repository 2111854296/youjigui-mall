<?php
namespace app\controller\api\v1;
use app\BaseController; use app\common\ApiResource;
use app\model\Article; use app\model\Company; use app\model\Product; use app\model\Purchase; use app\model\Supply;
use think\Response;
class Search extends BaseController {
  public function index(): Response {
    $q=trim((string)$this->request->param('q',''));
    $type=(string)$this->request->param('type','sell');
    if($q===''){ return json(['data'=>[],'meta'=>['current_page'=>1,'last_page'=>1,'per_page'=>16,'total'=>0],'links'=>['first'=>null,'last'=>null,'prev'=>null,'next'=>null]]); }
    $kw='%'.$q.'%';
    $paginator = match($type){
      'buy' => Purchase::publishedQuery()->whereLike('title',$kw)->order('id','desc')->paginate(['list_rows'=>16]),
      'company' => Company::publishedQuery()->whereLike('name',$kw)->order('id','desc')->paginate(['list_rows'=>16]),
      'news' => Article::publishedQuery()->whereLike('title',$kw)->order('id','desc')->paginate(['list_rows'=>16]),
      'mall' => Product::publishedQuery()->whereLike('title',$kw)->order('id','desc')->paginate(['list_rows'=>16]),
      default => Supply::publishedQuery()->with(['category','user'])->whereLike('title',$kw)->order('id','desc')->paginate(['list_rows'=>16]),
    };
    $map = match($type){
      'buy' => fn($r)=>ApiResource::purchase($r),
      'company' => fn($r)=>ApiResource::company($r),
      'news' => fn($r)=>ApiResource::article($r),
      'mall' => fn($r)=>ApiResource::product($r),
      default => fn($r)=>ApiResource::supply($r),
    };
    return json(paginate_laravel($paginator,$map));
  }
}