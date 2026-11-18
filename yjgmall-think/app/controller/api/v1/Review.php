<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\Product; use app\model\ProductReview; use think\Response;
class Review extends BaseController {
  public function index($product): Response {
    $paginator=ProductReview::where('product_id',$product)->where('status','published')->order('id','desc')->paginate(['list_rows'=>20]);
    $payload=paginate_laravel($paginator);
    $payload['meta']['avg_rating']=round((float)ProductReview::where('product_id',$product)->where('status','published')->avg('rating'),1);
    $payload['meta']['total']=(int)$paginator->total();
    return json($payload);
  }
  public function store($product): Response {
    $p=Product::find($product);
    if(!$p) return json_error('Not found',404);
    $user=$this->request->user;
    $item=ProductReview::create([
      'product_id'=>$product,
      'user_id'=>$user->id,
      'rating'=>(int)$this->request->post('rating',5),
      'content'=>$this->request->post('content',''),
      'status'=>'published',
    ]);
    return json(['data'=>$item->toArray()],201);
  }
  public function mine(): Response {
    $paginator=ProductReview::where('user_id',$this->request->user->id)->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
}