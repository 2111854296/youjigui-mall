<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\Inquiry as InquiryModel; use app\model\Message; use think\Response;
class Inquiry extends BaseController {
  public function store(): Response {
    $data=$this->request->post();
    if(empty($data['title'])||empty($data['content'])) return json_error('请填写标题和内容',422);
    $user=$this->request->user ?? null;
    $item=InquiryModel::create([
      'user_id'=>$user->id ?? null,
      'to_user_id'=>$data['to_user_id']??null,
      'product_id'=>$data['product_id']??null,
      'supply_id'=>$data['supply_id']??null,
      'title'=>$data['title'],
      'content'=>$data['content'],
      'contact_name'=>$data['contact_name']??($user->name??null),
      'contact_phone'=>$data['contact_phone']??($user->mobile??null),
      'contact_email'=>$data['contact_email']??($user->email??null),
      'status'=>'new',
    ]);
    if(!empty($data['to_user_id'])){
      Message::create([
        'user_id'=>$data['to_user_id'],
        'from_user_id'=>$user->id ?? null,
        'title'=>'收到询盘：'.$data['title'],
        'content'=>$data['content'],
        'is_read'=>0,
      ]);
    }
    return json(['data'=>$item->toArray(),'message'=>'已提交'],201);
  }
  public function index(): Response {
    $user=$this->request->user;
    $paginator=InquiryModel::where(function($q)use($user){
      $q->where('user_id',$user->id)->whereOr('to_user_id',$user->id);
    })->order('id','desc')->paginate(['list_rows'=>20]);
    return json(paginate_laravel($paginator));
  }
  public function reply($id): Response {
    $user=$this->request->user;
    $item=InquiryModel::find($id);
    if(!$item || ((int)$item->user_id!==(int)$user->id && (int)$item->to_user_id!==(int)$user->id)) return json_error('Forbidden',403);
    $reply=$this->request->post('reply','');
    $item->reply=$reply; $item->status='replied'; $item->save();
    $to=$item->user_id==(int)$user->id ? $item->to_user_id : $item->user_id;
    if($to) Message::create(['user_id'=>$to,'from_user_id'=>$user->id,'title'=>'询盘回复：'.$item->title,'content'=>$reply,'is_read'=>0]);
    return json_data($item->toArray());
  }
  public function updateStatus($id): Response {
    $user=$this->request->user;
    $item=InquiryModel::find($id);
    if(!$item || ((int)$item->user_id!==(int)$user->id && (int)$item->to_user_id!==(int)$user->id)) return json_error('Forbidden',403);
    $item->status=$this->request->post('status',$item->status); $item->save();
    return json_data($item->toArray());
  }
}