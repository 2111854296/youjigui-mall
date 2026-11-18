<?php

namespace app\common;

use app\controller\api\v1\Site;

/**
 * 阿里云短信。模板变量名为 code。
 */
class SmsGateway
{
    /** @return array{ok:bool,message:string} */
    public static function send(string $mobile, string $code): array
    {
        if (!preg_match('/^1\d{10}$/', $mobile)) {
            return ['ok' => false, 'message' => '请填写11位手机号'];
        }

        return self::aliyun($mobile, $code, Site::allSettings());
    }

    /** @param array<string, string> $s */
    private static function aliyun(string $mobile, string $code, array $s): array
    {
        $key = trim((string) ($s['sms_aliyun_key'] ?? ''));
        $secret = (string) ($s['sms_aliyun_secret'] ?? '');
        $sign = trim((string) ($s['sms_aliyun_sign'] ?? ''));
        $template = trim((string) ($s['sms_aliyun_template'] ?? ''));
        if ($key === '' || $secret === '' || $sign === '' || $template === '') {
            return ['ok' => false, 'message' => '请先在站点设置填写阿里云短信密钥、签名和模板'];
        }

        $params = [
            'AccessKeyId' => $key,
            'Action' => 'SendSms',
            'Format' => 'JSON',
            'PhoneNumbers' => $mobile,
            'RegionId' => 'cn-hangzhou',
            'SignName' => $sign,
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureNonce' => bin2hex(random_bytes(16)),
            'SignatureVersion' => '1.0',
            'TemplateCode' => $template,
            'TemplateParam' => json_encode(['code' => $code], JSON_UNESCAPED_UNICODE),
            'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'Version' => '2017-05-25',
        ];
        ksort($params);
        $canonical = [];
        foreach ($params as $k => $v) {
            $canonical[] = self::percentEncode((string) $k).'='.self::percentEncode((string) $v);
        }
        $stringToSign = 'GET&%2F&'.self::percentEncode(implode('&', $canonical));
        $params['Signature'] = base64_encode(hash_hmac('sha1', $stringToSign, $secret.'&', true));

        $url = 'https://dysmsapi.aliyuncs.com/?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $raw = self::http('GET', $url, [], null);
        if ($raw['error'] !== '') {
            return ['ok' => false, 'message' => '短信发送失败'];
        }
        $json = json_decode($raw['body'], true);
        if (!is_array($json) || (string) ($json['Code'] ?? '') !== 'OK') {
            $msg = trim((string) ($json['Message'] ?? ''));

            return ['ok' => false, 'message' => $msg !== '' ? '短信发送失败：'.$msg : '短信发送失败'];
        }

        return ['ok' => true, 'message' => '验证码已发送'];
    }

    private static function percentEncode(string $value): string
    {
        return str_replace(['+', '*', '%7E'], ['%20', '%2A', '~'], rawurlencode($value));
    }

    /** @param list<string> $headers @return array{body:string,error:string} */
    private static function http(string $method, string $url, array $headers, ?string $body): array
    {
        if (!function_exists('curl_init')) {
            return ['body' => '', 'error' => 'curl'];
        }
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $out = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($out === false) {
            return ['body' => '', 'error' => $err !== '' ? $err : 'http'];
        }

        return ['body' => (string) $out, 'error' => ''];
    }
}
