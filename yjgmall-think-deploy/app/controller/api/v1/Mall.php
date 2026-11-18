<?php
namespace app\controller\api\v1;
use app\BaseController; use app\common\ApiResource; use app\model\Category; use app\model\Product; use app\model\ProductReview; use think\Response;
class Mall extends BaseController {
  public function index(): Response {
    $req=$this->request;
    $query=Product::publishedQuery()->with(['category','user']);
    if($q=$req->param('q')){ $kw='%'.$q.'%'; $query->where(function($w)use($kw){ $w->whereLike('title',$kw)->whereOr('summary','like',$kw); }); }
    if($catId=(int)$req->param('category')){ $childIds=Category::where('parent_id',$catId)->column('id'); $query->whereIn('category_id',array_merge([$catId],$childIds)); }
    $tab=(string)$req->param('tab','all');
    match($tab){ 'hot'=>$query->order('sales','desc'), 'new'=>$query->order('id','desc'), 'deal'=>$query->where('price','>','0')->order('price','asc'), default=>$query->order('id','desc') };
    $paginator=$query->paginate(['list_rows'=>16]);
    $result=paginate_laravel($paginator, fn($p)=>ApiResource::product($p));
    $floors=[]; $tabs=[['key'=>'all','label'=>'全部'],['key'=>'hot','label'=>'热销'],['key'=>'new','label'=>'新品'],['key'=>'deal','label'=>'特惠']];
    if((int)$req->param('page',1)<=1 && !$req->param('q') && !$req->param('category') && $tab==='all'){
      $cats=Category::where('module','mall')->whereNull('parent_id')->where('is_active',1)->order('sort','asc')->limit(8)->select();
      if($cats->isEmpty()) $cats=Category::where('module','sell')->whereNull('parent_id')->where('is_active',1)->order('sort','asc')->limit(8)->select();
      foreach($cats as $i=>$cat){
        $ids=array_merge([$cat->id], Category::where('parent_id',$cat->id)->column('id'));
        $items=Product::publishedQuery()->with(['category','user'])->whereIn('category_id',$ids)->order('sales','desc')->limit(8)->select();
        $floors[]=['floor_no'=>$i+1,'category'=>ApiResource::category($cat),'items'=>array_map(fn($p)=>ApiResource::product($p),$items->all())];
      }
    }
    return json(array_merge($result,['floors'=>$floors,'tabs'=>$tabs,'filter_label'=>$req->param('q')?:'全部商品']));
  }
  public function show($id): Response {
    $item=Product::where('status','approved')->with(['category','user'])->find($id);
    if(!$item) return json_error('Not found',404);
    $item->views=(int)$item->views+1; $item->save();
    $avg=ProductReview::where('product_id',$item->id)->where('status','published')->avg('rating');
    $count=ProductReview::where('product_id',$item->id)->where('status','published')->count();
    $data=ApiResource::product($item);
    $data['reviews_meta']=['avg'=>round((float)$avg,1),'count'=>(int)$count];
    return json_data($data);
  }
}