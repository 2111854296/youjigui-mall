<?php
namespace app\controller\api\v1;

use app\BaseController;
use app\common\SlideCaptcha;
use think\Response;

class Captcha extends BaseController
{
    /** 生成滑块拼图 */
    public function gen(): Response
    {
        try {
            return json_data(SlideCaptcha::generate());
        } catch (\Throwable $e) {
            return json_error('验证码生成失败：'.$e->getMessage(), 500);
        }
    }

    /** 校验滑动位置，成功返回一次性 captcha_token */
    public function check(): Response
    {
        $payload = $this->request->post();
        if (!$payload) {
            $raw = $this->request->getContent();
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        }
        $key = (string) ($payload['captcha_key'] ?? '');
        $x = (float) ($payload['x'] ?? -1);
        if ($key === '' || $x < 0) {
            return json_error('请完成滑块验证', 422);
        }
        $token = SlideCaptcha::check($key, $x);
        if (!$token) {
            return json_error('验证失败，请重试', 422);
        }
        return json_data(['captcha_token' => $token]);
    }
}
