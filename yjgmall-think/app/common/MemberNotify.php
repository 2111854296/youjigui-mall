<?php

namespace app\common;

use app\model\Article;
use app\model\Company;
use app\model\MailSubscription;
use app\model\Message;
use app\model\Product;
use app\model\Purchase;
use app\model\QuoteProduct;
use app\model\QuoteProductPrice;
use app\model\Supply;
use app\model\TradeAlert;
use app\model\User;
use think\facade\Db;

/** 贸易提醒发站内信，邮件订阅按栏目发送新增内容。 */
class MemberNotify
{
    public static function run(): array
    {
        return [
            'alerts' => self::dispatchAlerts(),
            'mail' => self::dispatchMail(),
        ];
    }

    public static function dispatchAlerts(): int
    {
        $sent = 0;
        $alerts = TradeAlert::where('status', 'active')->select();
        foreach ($alerts as $alert) {
            $rows = self::matchAlert($alert);
            foreach ($rows as $row) {
                $exists = Db::name('trade_alert_hits')
                    ->where('alert_id', (int) $alert->id)
                    ->where('target_type', $row['type'])
                    ->where('target_id', $row['id'])
                    ->find();
                if ($exists) {
                    continue;
                }
                Message::create([
                    'user_id' => (int) $alert->user_id,
                    'from_user_id' => 0,
                    'title' => '贸易提醒：'.$alert->keyword,
                    'content' => $row['text'],
                    'is_read' => 0,
                ]);
                Db::name('trade_alert_hits')->insert([
                    'alert_id' => (int) $alert->id,
                    'target_type' => $row['type'],
                    'target_id' => $row['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $sent++;
            }
        }

        return $sent;
    }

    public static function dispatchMail(): array
    {
        if (!SmtpMail::configured()) {
            return ['sent' => 0, 'skipped' => 'smtp'];
        }
        $sent = 0;
        $rows = MailSubscription::where('enabled', 1)->select();
        foreach ($rows as $sub) {
            $email = trim((string) ($sub->email ?? ''));
            if ($email === '') {
                $email = (string) (User::where('id', (int) $sub->user_id)->value('email') ?: '');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $modules = $sub->modules;
            if (is_string($modules)) {
                $modules = json_decode($modules, true);
            }
            if (!is_array($modules) || !$modules) {
                continue;
            }
            $since = (string) ($sub->last_digest_at ?: date('Y-m-d H:i:s', time() - 86400));
            $lines = self::digestLines($modules, $since);
            if (!$lines) {
                $sub->last_digest_at = date('Y-m-d H:i:s');
                $sub->save();
                continue;
            }
            $body = "您好，以下是您订阅的最新内容：\n\n".implode("\n", $lines)."\n\n有机硅商城 https://g.aifeili.cn\n";
            try {
                SmtpMail::send($email, '有机硅商城订阅', $body);
                $sub->last_digest_at = date('Y-m-d H:i:s');
                $sub->save();
                $sent++;
            } catch (\Throwable $e) {
                // 失败不推进水位，下次再发
            }
        }

        return ['sent' => $sent, 'skipped' => ''];
    }

    /** @return list<array{type:string,id:int,text:string}> */
    private static function matchAlert($alert): array
    {
        $keyword = trim((string) $alert->keyword);
        if ($keyword === '') {
            return [];
        }
        $like = '%'.$keyword.'%';
        $uid = (int) $alert->user_id;
        $since = (string) ($alert->created_at ?: date('Y-m-d H:i:s', time() - 86400));
        $floor = date('Y-m-d H:i:s', time() - 14 * 86400);
        if ($since < $floor) {
            $since = $floor;
        }
        $out = [];
        $module = (string) $alert->module;
        if ($module === 'sell') {
            $q = Supply::publishedQuery()->where('user_id', '<>', $uid)->where('created_at', '>=', $since)->where(function ($w) use ($like) {
                $w->whereLike('title', $like)->whereOr('summary', 'like', $like)->whereOr('brand', 'like', $like);
            });
            self::applyAreaPrice($q, $alert, 'price');
            foreach ($q->order('id', 'desc')->limit(10)->select() as $row) {
                $out[] = [
                    'type' => 'sell',
                    'id' => (int) $row->id,
                    'text' => '新供应：'.$row->title."\nhttps://g.aifeili.cn/sell/".$row->id,
                ];
            }
        } elseif ($module === 'buy') {
            $q = Purchase::publishedQuery()->where('user_id', '<>', $uid)->where('created_at', '>=', $since)->where(function ($w) use ($like) {
                $w->whereLike('title', $like)->whereOr('summary', 'like', $like);
            });
            self::applyAreaPrice($q, $alert, 'budget');
            foreach ($q->order('id', 'desc')->limit(10)->select() as $row) {
                $out[] = [
                    'type' => 'buy',
                    'id' => (int) $row->id,
                    'text' => '新采购：'.$row->title."\nhttps://g.aifeili.cn/buy/".$row->id,
                ];
            }
        } elseif ($module === 'mall') {
            $q = Product::publishedQuery()->where('user_id', '<>', $uid)->where('created_at', '>=', $since)->where(function ($w) use ($like) {
                $w->whereLike('title', $like)->whereOr('summary', 'like', $like);
            });
            self::applyAreaPrice($q, $alert, 'price');
            foreach ($q->order('id', 'desc')->limit(10)->select() as $row) {
                $out[] = [
                    'type' => 'mall',
                    'id' => (int) $row->id,
                    'text' => '新商品：'.$row->title."\nhttps://g.aifeili.cn/mall/".$row->id,
                ];
            }
        } elseif ($module === 'company') {
            $q = Company::publishedQuery()->where('user_id', '<>', $uid)->where('created_at', '>=', $since)->where(function ($w) use ($like) {
                $w->whereLike('name', $like)->whereOr('business', 'like', $like);
            });
            $area = trim((string) ($alert->area ?? ''));
            if ($area !== '') {
                $q->whereLike('area', '%'.$area.'%');
            }
            foreach ($q->order('id', 'desc')->limit(10)->select() as $row) {
                $out[] = [
                    'type' => 'company',
                    'id' => (int) $row->id,
                    'text' => '新企业：'.$row->name."\nhttps://g.aifeili.cn/companies/".($row->slug ?: $row->id),
                ];
            }
        }

        return $out;
    }

    private static function applyAreaPrice($query, $alert, string $priceCol): void
    {
        $area = trim((string) ($alert->area ?? ''));
        if ($area !== '') {
            $query->whereLike('area', '%'.$area.'%');
        }
        if ($alert->price_min !== null && $alert->price_min !== '') {
            $query->where($priceCol, '>=', (float) $alert->price_min);
        }
        if ($alert->price_max !== null && $alert->price_max !== '') {
            $query->where($priceCol, '<=', (float) $alert->price_max);
        }
    }

    /** @param list<string> $modules */
    private static function digestLines(array $modules, string $since): array
    {
        $lines = [];
        if (in_array('sell', $modules, true)) {
            $rows = Supply::publishedQuery()->where('created_at', '>', $since)->order('id', 'desc')->limit(8)->select();
            foreach ($rows as $row) {
                $lines[] = '【供应】'.$row->title.'  https://g.aifeili.cn/sell/'.$row->id;
            }
        }
        if (in_array('buy', $modules, true)) {
            $rows = Purchase::publishedQuery()->where('created_at', '>', $since)->order('id', 'desc')->limit(8)->select();
            foreach ($rows as $row) {
                $lines[] = '【采购】'.$row->title.'  https://g.aifeili.cn/buy/'.$row->id;
            }
        }
        if (in_array('news', $modules, true)) {
            $rows = Article::publishedQuery()->where('created_at', '>', $since)->order('id', 'desc')->limit(8)->select();
            foreach ($rows as $row) {
                $lines[] = '【资讯】'.$row->title.'  https://g.aifeili.cn/news/'.$row->id;
            }
        }
        if (in_array('quote', $modules, true)) {
            try {
                $rows = QuoteProductPrice::where('created_at', '>', $since)->order('id', 'desc')->limit(8)->select();
                foreach ($rows as $row) {
                    $name = (string) (QuoteProduct::where('id', (int) $row->product_id)->value('name') ?: '行情');
                    $lines[] = '【行情】'.$name.' '.$row->price_avg.'  https://g.aifeili.cn/quotes/'.$row->product_id;
                }
            } catch (\Throwable $e) {
                // 行情表结构不齐时跳过这一栏
            }
        }

        return $lines;
    }
}
