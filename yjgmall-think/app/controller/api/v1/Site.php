<?php

namespace app\controller\api\v1;

use app\BaseController;
use app\model\SiteSetting;
use think\Response;

class Site extends BaseController
{
    /** @var array<string, string> */
    public const DEFAULTS = [
        'wechat_subscribe_qr' => '/img/qr/weima.jpg',
        'wechat_subscribe_label' => '扫一扫订阅号',
        'wechat_service_qr' => '/img/qr/weimas.jpg',
        'wechat_service_label' => '扫一扫服务号',
        'header_cta_text' => '免费发布',
        'header_cta_link' => '/member',
        'site_name' => '有机硅商城',
        'site_domain' => 'g.aifeili.cn',
        'vip_bank_account_name' => '',
        'vip_bank_name' => '',
        'vip_bank_account' => '',
        'vip_pay_hint' => '请使用对公账户转账，备注填写订单号；上传凭证后由财务审核开通。需开票请在订单备注说明抬头与税号。',
        'vip_pay_expire_minutes' => '30',
        'home_list_carousel' => '1',
        'smtp_host' => '',
        'smtp_port' => '465',
        'smtp_encryption' => 'ssl',
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_from' => '',
        'smtp_from_name' => '有机硅商城',
        'sms_mode' => 'fixed',
        'sms_fixed_code' => '888888',
        'sms_aliyun_key' => '',
        'sms_aliyun_secret' => '',
        'sms_aliyun_sign' => '',
        'sms_aliyun_template' => '',
    ];

    public function index(): Response
    {
        $data = self::allSettings();
        // 前台不暴露短信/邮件密钥与模式，避免探测 fixed 绕过
        unset(
            $data['smtp_pass'],
            $data['smtp_user'],
            $data['sms_fixed_code'],
            $data['sms_mode'],
            $data['sms_aliyun_key'],
            $data['sms_aliyun_secret'],
            $data['sms_aliyun_sign'],
            $data['sms_aliyun_template']
        );

        return json_data($data);
    }

    public static function allSettings(): array
    {
        $out = self::DEFAULTS;
        try {
            $rows = SiteSetting::select();
            foreach ($rows as $row) {
                $k = (string) $row->getAttr('key');
                if ($k === '') {
                    continue;
                }
                $out[$k] = (string) ($row->value ?? '');
            }
        } catch (\Throwable $e) {
            // table may not exist yet — fall back to defaults
        }

        return $out;
    }
}
