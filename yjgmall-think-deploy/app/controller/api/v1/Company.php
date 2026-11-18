<?php
namespace app\controller\api\v1;
use app\BaseController; use app\common\ApiResource;
use app\model\Category; use app\model\Company as CompanyModel;
use app\model\CompanyHonor; use app\model\CompanyLink; use app\model\CompanyNews; use app\model\CompanyPage;
use app\model\Product; use app\model\Supply; use think\Response;
class Company extends BaseController {
  public function index(): Response {
    $req=$this->request;
    $query=CompanyModel::publishedQuery()->with(['category']);
    if($q=$req->param('q')){ $kw='%'.$q.'%'; $query->where(function($w)use($kw){ $w->whereLike('name',$kw)->whereOr('business','like',$kw)->whereOr('area','like',$kw); }); }
    if($c=(int)$req->param('category')) $query->where('category_id',$c);
    if($req->param('featured')) $query->where('is_featured',1);
    $query->order('is_featured','desc')->order('id','desc');
    $paginator=$query->paginate(['list_rows'=>16]);
    $result=paginate_laravel($paginator, fn($c)=>ApiResource::company($c));
    $featured=[]; $floors=[]; $categories=[];
    if((int)$req->param('page',1)<=1 && !$req->param('q') && !$req->param('category')){
      $featuredRows=CompanyModel::publishedQuery()->where('is_featured',1)->limit(12)->select();
      $featured=array_map(fn($c)=>ApiResource::company($c), $featuredRows->all());
      $categories=Category::where('module','company')->whereNull('parent_id')->where('is_active',1)->with(['children'])->order('sort','asc')->select();
      if($categories->isEmpty()) $categories=Category::where('module','sell')->whereNull('parent_id')->where('is_active',1)->with(['children'])->order('sort','asc')->limit(8)->select();
      foreach($categories as $i=>$cat){
        $ids=array_merge([$cat->id], $cat->children?$cat->children->column('id'):[]);
        $items=CompanyModel::publishedQuery()->whereIn('category_id',$ids)->limit(8)->select();
        $floors[]=['floor_no'=>$i+1,'category'=>ApiResource::category($cat),'items'=>array_map(fn($c)=>ApiResource::company($c),$items->all())];
      }
    }
    return json(array_merge($result,[
      'featured'=>$featured,
      'floors'=>$floors,
      'categories'=>array_map(fn($c)=>ApiResource::category($c), is_object($categories)?$categories->all():[]),
    ]));
  }
  public function show($company): Response {
    $item=CompanyModel::where(function($q)use($company){ $q->where('slug',$company)->whereOr('id',$company); })->find();
    if(!$item || $item->status!=='approved') return json_error('Not found',404);
    $item->views=(int)$item->views+1; $item->save();
    $item->category; // load
    $item->supplies = Supply::publishedQuery()->where('user_id',$item->user_id)->limit(20)->select();
    $item->products = Product::publishedQuery()->where('user_id',$item->user_id)->limit(20)->select();
    $item->news = CompanyNews::where('company_id',$item->id)->order('id','desc')->limit(20)->select();
    $item->honors = CompanyHonor::where('company_id',$item->id)->order('id','desc')->select();
    $item->pages = CompanyPage::where('company_id',$item->id)->select();
    $item->links = CompanyLink::where('company_id',$item->id)->select();
    return json_data(ApiResource::company($item, true));
  }
}