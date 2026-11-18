<?php
namespace app\controller\api\v1;
use app\BaseController; use app\model\QuoteProduct; use think\Response;
class Quote extends BaseController {
  public function index(): Response {
    $paginator=QuoteProduct::where('is_active',1)->order('sort','asc')->paginate(['list_rows'=>50]);
    return json(paginate_laravel($paginator));
  }
}