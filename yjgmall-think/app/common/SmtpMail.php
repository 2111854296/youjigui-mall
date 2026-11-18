<?php

namespace app\common;

use app\controller\api\v1\Site;

class SmtpMail
{
    public static function configured(): bool
    {
        $s = self::settings();

        return $s['host'] !== '' && $s['user'] !== '' && $s['pass'] !== '';
    }

    /** @return array{host:string,port:int,user:string,pass:string,from:string,name:string,secure:string} */
    public static function settings(): array
    {
        $all = Site::allSettings();
        $secure = strtolower(trim((string) ($all['smtp_encryption'] ?? 'ssl')));
        if (!in_array($secure, ['ssl', 'tls'], true)) {
            $secure = 'ssl';
        }

        return [
            'host' => trim((string) ($all['smtp_host'] ?? '')),
            'port' => max(1, (int) ($all['smtp_port'] ?: 465)),
            'user' => trim((string) ($all['smtp_user'] ?? '')),
            'pass' => (string) ($all['smtp_pass'] ?? ''),
            'from' => trim((string) ($all['smtp_from'] ?? '')),
            'name' => trim((string) ($all['smtp_from_name'] ?? '有机硅商城')) ?: '有机硅商城',
            'secure' => $secure,
        ];
    }

    public static function send(string $to, string $subject, string $body): void
    {
        $s = self::settings();
        if (!self::configured()) {
            throw new \RuntimeException('尚未配置发信邮箱');
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('收件邮箱无效');
        }
        $from = $s['from'] !== '' ? $s['from'] : $s['user'];
        $remote = ($s['secure'] === 'ssl' ? 'ssl://' : 'tcp://').$s['host'].':'.$s['port'];
        $fp = @stream_socket_client($remote, $errno, $errstr, 20);
        if (!$fp) {
            throw new \RuntimeException('连接邮箱服务器失败');
        }
        stream_set_timeout($fp, 20);
        $read = function (string $expect) use ($fp): void {
            $buf = '';
            while (($line = fgets($fp, 515)) !== false) {
                $buf .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            if (!str_starts_with($buf, $expect)) {
                throw new \RuntimeException('邮箱服务器拒绝：'.trim($buf));
            }
        };
        $write = function (string $line) use ($fp): void {
            fwrite($fp, $line."\r\n");
        };

        $read('220');
        $write('EHLO g.aifeili.cn');
        $read('250');
        if ($s['secure'] === 'tls') {
            $write('STARTTLS');
            $read('220');
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('邮箱加密失败');
            }
            $write('EHLO g.aifeili.cn');
            $read('250');
        }
        $write('AUTH LOGIN');
        $read('334');
        $write(base64_encode($s['user']));
        $read('334');
        $write(base64_encode($s['pass']));
        $read('235');
        $write('MAIL FROM:<'.$from.'>');
        $read('250');
        $write('RCPT TO:<'.$to.'>');
        $read('250');
        $write('DATA');
        $read('354');
        $encodedName = '=?UTF-8?B?'.base64_encode($s['name']).'?=';
        $encodedSubject = '=?UTF-8?B?'.base64_encode($subject).'?=';
        $headers = implode("\r\n", [
            'From: '.$encodedName.' <'.$from.'>',
            'To: <'.$to.'>',
            'Subject: '.$encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ]);
        $payload = $headers."\r\n\r\n".chunk_split(base64_encode($body));
        fwrite($fp, $payload."\r\n.\r\n");
        $read('250');
        $write('QUIT');
        fclose($fp);
    }
}
