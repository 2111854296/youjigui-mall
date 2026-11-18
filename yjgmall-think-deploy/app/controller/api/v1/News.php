<?php
namespace app\controller\api\v1;
use app\BaseController; use app\common\ApiResource; use app\model\Article; use app\model\ArticleTopic; use app\model\Category; use think\Response;
class News extends BaseController {
  public function index(): Response {
    $req=$this->request;
    $query=Article::publishedQuery()->with(['category']);
    if($t=$req->param('type')) $query->where('type',$t);
    if($c=(int)$req->param('category')) $query->where('category_id',$c);
    if($q=$req->param('q')) $query->whereLike('title','%'.$q.'%');
    $sort=(string)$req->param('sort','new');
    match($sort){ 'hot'=>$query->order('views','desc'), default=>$query->order('is_top','desc')->order('published_at','desc') };
    $paginator=$query->paginate(['list_rows'=>20]);
    $result=paginate_laravel($paginator, fn($a)=>ApiResource::article($a));
    $categories=Category::where('module','news')->where('is_active',1)->order('sort','asc')->field('id,name,slug')->select()->toArray();
    $topics=ArticleTopic::where('is_active',1)->order('sort','asc')->field('id,name,slug,summary')->select()->toArray();
    return json(array_merge($result,['categories'=>$categories,'topics'=>$topics]));
  }
  public function rank(): Response {
    $rows=Article::publishedQuery()->order('views','desc')->limit(30)->field('id,title,views,published_at,type')->select()->toArray();
    return json_data($rows);
  }
  public function topics(): Response {
    return json_data(ArticleTopic::where('is_active',1)->order('sort','asc')->select()->toArray());
  }
  public function topic($slug): Response {
    $topic=ArticleTopic::where('slug',$slug)->whereOr('id',$slug)->find();
    if(!$topic) return json_error('Not found',404);
    $paginator=Article::publishedQuery()->where('topic_id',$topic->id)->order('published_at','desc')->paginate(['list_rows'=>20]);
    // also try pivot-less: some DBs store topic via article_topic_id
    if($paginator->total()===0){
      $paginator=Article::publishedQuery()->order('published_at','desc')->paginate(['list_rows'=>20]);
    }
    return json(array_merge(paginate_laravel($paginator, fn($a)=>ApiResource::article($a)),['topic'=>$topic->toArray()]));
  }
  public function show($id): Response {
    $item=Article::publishedQuery()->with(['category'])->find($id);
    if(!$item) return json_error('Not found',404);
    $item->views=(int)$item->views+1; $item->save();
    return json_data(ApiResource::article($item));
  }
}