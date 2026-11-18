<?php
namespace app\controller\api\v1;
use app\BaseController; use app\common\ApiResource;
use app\model\Company; use app\model\CompanyHonor; use app\model\CompanyLink; use app\model\CompanyNews; use app\model\CompanyPage;
use think\Response;
class Shop extends BaseController {
  private function company() {
    $user=$this->request->user;
    $c=Company::where('user_id',$user->id)->find();
    if(!$c){
      $name = (string) ($user->company_name ?: $user->name);
      $c=Company::create(['user_id'=>$user->id,'name'=>$name,'slug'=>company_slug_from_name($name, (int) $user->id),'status'=>'pending','contact'=>$user->name,'phone'=>$user->mobile]);
    }
    if ($c && (string) $c->status === 'pending' && preg_match('/^-\d+$/', (string) $c->slug)) {
      $next = 'c'.$c->user_id;
      $taken = Company::where('slug', $next)->where('id', '<>', $c->id)->find();
      if ($taken) $next = 'co'.$c->id;
      $c->save(['slug' => $next]);
    }
    return $c;
  }
  public function show(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $c->news=CompanyNews::where('company_id',$c->id)->order('id','desc')->select();
    $c->honors=CompanyHonor::where('company_id',$c->id)->order('id','desc')->select();
    $c->pages=CompanyPage::where('company_id',$c->id)->select();
    $c->links=CompanyLink::where('company_id',$c->id)->select();
    $data = ApiResource::company($c, true);
    $data['templates'] = [
      ['code' => 'atelier', 'name' => '展厅·清雅'],
      ['code' => 'forge', 'name' => '工场·沉稳'],
      ['code' => 'ledger', 'name' => '名录·商务'],
    ];
    // legacy Destoon codes still accepted on save
    if (!in_array($data['template'] ?? '', ['atelier', 'forge', 'ledger'], true)) {
      $map = ['default' => 'atelier', 'blue' => 'ledger', 'orange' => 'forge'];
      $data['template'] = $map[$data['template'] ?? ''] ?? 'atelier';
    }
    return json_data($data);
  }
  public function update(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $data=$this->request->only([
      'name','logo','banner','env_images','template','contact','phone','fax','email','website','address','zip','area','business','tags',
      'company_type','business_mode','scale','capital','capital_unit','reg_year',
      'sell_products','buy_products','intro','content',
    ]);
    if (array_key_exists('tags', $data)) {
      $parts = preg_split('/[,|，、;；]+/u', (string) ($data['tags'] ?? '')) ?: [];
      $parts = array_values(array_filter(array_map('trim', $parts), fn ($s) => $s !== ''));
      $data['tags'] = $parts ? implode(',', array_slice($parts, 0, 4)) : null;
    }
    if (array_key_exists('env_images', $data)) {
      $data['env_images'] = ApiResource::encodeEnvImages($data['env_images']);
    }
    // 对外展示的敏感字段改动后重新进审，避免改完仍挂在前台
    $sensitive = [
      'name','logo','banner','env_images','contact','phone','fax','email','website','address','area','business','tags',
      'company_type','business_mode','scale','capital','capital_unit','reg_year',
      'sell_products','buy_products','intro','content',
    ];
    $changed = false;
    foreach ($sensitive as $key) {
      if (!array_key_exists($key, $data)) continue;
      $old = (string) ($c->$key ?? '');
      $new = (string) ($data[$key] ?? '');
      if ($old !== $new) {
        $changed = true;
        break;
      }
    }
    if ($changed && in_array((string) $c->status, ['approved', 'published'], true)) {
      $data['status'] = 'pending';
    }
    $c->save($data);
    return json_data(ApiResource::company($c->refresh()));
  }
  public function storeNews(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $row=CompanyNews::create([
      'company_id'=>$c->id,
      'title'=>$this->request->post('title'),
      'content'=>$this->request->post('content',''),
      'status'=>'pending',
    ]);
    return json(['data'=>$row->toArray(),'message'=>'已提交，审核通过后展示'],201);
  }
  public function destroyNews($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    CompanyNews::where('company_id',$c->id)->where('id',$id)->delete();
    return json_message('ok');
  }
  public function storeHonor(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $row=CompanyHonor::create([
      'company_id'=>$c->id,
      'title'=>$this->request->post('title'),
      'image'=>$this->request->post('image'),
      'authority'=>$this->request->post('authority'),
      'issued_at'=>$this->request->post('issued_at'),
      'expires_at'=>$this->request->post('expires_at'),
      'status'=>'pending',
    ]);
    return json(['data'=>$row->toArray()],201);
  }
  public function destroyHonor($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    CompanyHonor::where('company_id',$c->id)->where('id',$id)->delete();
    return json_message('ok');
  }
  public function storePage(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $row=CompanyPage::create([
      'company_id'=>$c->id,
      'title'=>$this->request->post('title'),
      'slug'=>$this->request->post('slug'),
      'content'=>$this->request->post('content',''),
      'status'=>'pending',
    ]);
    return json(['data'=>$row->toArray(),'message'=>'已提交，审核通过后展示'],201);
  }
  public function destroyPage($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    CompanyPage::where('company_id',$c->id)->where('id',$id)->delete();
    return json_message('ok');
  }
  public function storeLink(): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    $row=CompanyLink::create(['company_id'=>$c->id,'name'=>$this->request->post('name'),'url'=>$this->request->post('url')]);
    return json(['data'=>$row->toArray()],201);
  }
  public function destroyLink($id): Response {
    if($e=ApiResource::requireEnterprise()) return $e;
    $c=$this->company();
    CompanyLink::where('company_id',$c->id)->where('id',$id)->delete();
    return json_message('ok');
  }
}