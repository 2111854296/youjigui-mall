<?php
namespace app\controller\api\v1;

use app\BaseController;
use app\common\QuoteAccess;
use think\facade\Cache;
use think\Response;

class QuoteTicket extends BaseController
{
    /** 签发短时行情访问票据（公开，轻量限流；不影响 HTML 收录） */
    public function issue(): Response
    {
        $ip = (string) ($this->request->ip() ?: '0.0.0.0');
        $bucket = 'ip:'.md5($ip);
        $key = 'rl:quote_ticket:'.$bucket.':'.date('YmdHi');
        $n = (int) Cache::get($key, 0);
        if ($n >= 60) {
            return json_error('请求过于频繁，请稍后再试', 429);
        }
        Cache::set($key, $n + 1, 90);

        return json_data(QuoteAccess::issue($ip));
    }
}
