<?php

namespace app\controller\api\v1;

use app\BaseController;
use app\model\Ad;
use app\model\Announcement;
use app\model\Article;
use app\model\Banner;
use app\model\Category;
use app\model\Company;
use app\model\FriendLink;
use app\model\Product;
use app\model\Purchase;
use app\model\QuoteProduct;
use app\model\QuoteProductPrice;
use app\model\QuoteRegionPrice;
use app\model\Supply;
use app\model\User;
use think\facade\Cache;
use think\Response;

class Home extends BaseController
{
    /** @var array<int, array{id:int,slug:string,name:string}|null> */
    private array $companyBriefs = [];

    private bool $companyBriefsLoaded = false;

    /** @return array{id:int,slug:string,name:string}|null */
    private function companyBrief(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        if (!$this->companyBriefsLoaded) {
            $this->companyBriefsLoaded = true;
            $rows = Company::where('status', 'approved')->field('id,slug,name')->select();
            foreach ($rows as $c) {
                $name = trim((string) $c->name);
                if ($name === '' || $name === '管理员') {
                    continue;
                }
                $this->companyBriefs[(int) $c->id] = [
                    'id' => (int) $c->id,
                    'slug' => $c->slug ?: (string) $c->id,
                    'name' => $name,
                ];
            }
        }

        return $this->companyBriefs[$id] ?? null;
    }

    /** 楼层左栏图/品牌图对齐原站 yjgmall.com 首页（/file/upload/...），迁移后路径不变 */
    private function floorMeta(): array
    {
        return [
            '有机硅单体/中间体' => [
                'image' => '/file/upload/201707/18/165732731.jpg',
                'brand_logo' => '',
                'feature' => 'DMC D4 D5',
                'brands' => ['星火', '科毅品牌', '山东东岳'],
                'groups' => [
                    ['title' => '甲基中间体', 'items' => ['二甲基环硅氧烷（DMC）', '六甲基环三硅氧烷', '八甲基环四硅氧烷（D4）', '十甲基环五硅氧烷（D5）']],
                    ['title' => '甲基单体', 'items' => ['一甲基三氯硅烷（M1）', '二甲基二氯硅烷（M2）', '三甲基一氯硅烷（M3）']],
                    ['title' => '苯基单体', 'items' => ['苯基氯硅烷', '甲基乙烯基氯硅烷', '乙基三氯硅烷', '丙基三氯硅烷', '乙烯基三氯硅烷', 'γ-氯丙基三氯硅烷']],
                    ['title' => '氟硅单体', 'items' => ['氟硅单体']],
                ],
            ],
            '硅油' => [
                'image' => '/file/upload/201707/28/175710281.jpg',
                'brand_logo' => '/file/upload/201708/04/163032351.gif',
                'feature' => '甲基硅油 · 107胶',
                'brands' => ['黄山强力', '枣阳华威', '强力化工'],
                'groups' => [
                    ['title' => '甲基硅油', 'items' => ['二甲基硅油', '羟基硅油', '乙烯基硅油', '氨基硅油', '高粘度硅油', '水溶性硅油', '乳化硅油', '硅油乳液']],
                    ['title' => '含氢硅油', 'items' => ['高含氢硅油', '低含氢硅油', '含氢硅油乳液']],
                    ['title' => '苯基硅油', 'items' => ['苯甲基硅油', '高真空扩散泵油', '氟硅油']],
                    ['title' => '改性硅油', 'items' => ['流平剂', '润湿剂', '抗油剂', '聚醚改性硅油', '硬泡稳定剂', '农用有机硅助剂', '三元共聚硅油', '烷基改性硅油']],
                ],
            ],
            '硅橡胶' => [
                'image' => '/file/upload/201707/28/175757871.jpg',
                'brand_logo' => '/file/upload/202103/22/15391135598.gif',
                'feature' => '混炼胶 / 液体硅橡胶',
                'brands' => ['道康宁', '东莞子轩', '新安', '蓝星'],
                'groups' => [
                    ['title' => '101甲基乙烯基硅橡胶', 'items' => ['沉淀混炼胶', '耐高温胶', '模具硅橡胶', '加成型液体硅橡胶（LSR）', '自干型丝印硅胶']],
                    ['title' => '苯基硅橡胶', 'items' => ['苯基硅橡胶', '腈硅橡胶', '苯撑硅橡胶', '苯醚撑硅橡胶']],
                ],
            ],
            '有机硅助剂/填料' => [
                'image' => '/file/upload/201707/28/175340481.jpg',
                'brand_logo' => '/file/upload/202204/11/174511601.gif',
                'feature' => '铂金催化剂 · 白炭黑',
                'brands' => ['迈图', '晶和', '聚兴源', '硅众'],
                'groups' => [
                    ['title' => '硅胶助剂', 'items' => ['铂金硫化剂', '铂金催化剂', '硅胶抑制剂', '硅胶底涂剂']],
                    ['title' => '硅胶色母', 'items' => ['固体硅胶色母', '液体硅胶色浆']],
                    ['title' => '白炭黑', 'items' => ['沉淀白炭黑', '气相白炭黑']],
                    ['title' => '乙烯基单/双封头', 'items' => ['乙烯基单封头', '乙烯基双封头', '含氢双封头', '乙烯基环体', '二甲二乙', '正硅酸乙脂']],
                ],
            ],
            '机械设备' => [
                'image' => '/file/upload/201707/28/175650671.jpg',
                'brand_logo' => '',
                'feature' => '真空捏合机 / 反应釜',
                'brands' => ['太仓豪达', '强盛捏合机', '无锡华宸', '荣兴'],
                'groups' => [
                    ['title' => '硅橡胶设备', 'items' => ['真空捏合机', '行星分散搅拌机', '液态硅胶注射成型机', '硅橡胶挤出机', '液压式压料机', '三辊研磨机']],
                    ['title' => '有机硅全套设备', 'items' => ['不锈钢反应锅', '不锈钢反应釜', '列管冷凝器', '高效酒精回收塔', '实验设备']],
                ],
            ],
            '硅烷' => [
                'image' => '/file/upload/201707/28/175508761.jpg',
                'brand_logo' => '/file/upload/201708/04/165023731.jpg',
                'feature' => '偶联剂 / 交联剂',
                'brands' => ['莱宝硅材料', '晨光', '拓格'],
                'groups' => [
                    ['title' => '硅烷偶联剂', 'items' => ['氨丙基三乙氧基硅烷', '氨丙基三甲氧基硅烷', '三乙氧基丙基硅烷', '苯基三甲氧基硅烷', '二苯基二甲氧基硅烷', '辛基三乙氧基硅烷', '氯丙基三乙氧基硅烷', '氯丙基三甲氧基硅烷']],
                    ['title' => '硅烷交联剂', 'items' => ['甲基三丁酮肟基硅烷', '甲基混合酮肟交联剂', '乙烯基三丁酮肟基硅烷', '甲基三甲氧基硅烷', '乙基三乙酰氧基硅烷', '乙烯基混合酮肟交联剂', '甲基三乙酰氧基硅烷']],
                ],
            ],
            '胶粘剂' => [
                'image' => '/file/upload/201707/28/175218661.jpg',
                'brand_logo' => '/file/upload/202312/12/141807941.png',
                'feature' => '密封胶 · 灌封胶 · 玻璃胶',
                'brands' => ['中蓝晨光', '拓格电子', '乐迪雅'],
                'groups' => [
                    ['title' => '硅酮密封胶', 'items' => ['电子灌封胶', '玻璃胶', '胶粘剂', '中性耐候胶', '酸性玻璃胶']],
                ],
            ],
            '硅树脂' => [
                'image' => '/file/upload/201707/28/175437121.jpg',
                'brand_logo' => '/file/upload/201708/04/165052151.jpg',
                'feature' => 'MQ硅树脂 / 甲基苯基硅树脂',
                'brands' => ['得尔塔', 'M-100系列'],
                'groups' => [
                    ['title' => '甲基苯基硅树脂', 'items' => ['甲基透明硅树脂', '甲基MQ硅树脂', '乙烯基MQ硅树脂']],
                ],
            ],
        ];
    }

    /** @param array<int, array<int, array{id:int,slug:string,name:string}>> $shopsByUser */
    private function supplyCard($s, array $shopsByUser): array
    {
        $uid = (int) ($s->user_id ?: 0);
        $companyName = trim((string) ($s->user->company_name ?? ''));
        if ($companyName === '管理员') {
            $companyName = '';
        }
        $shops = ($uid && !empty($shopsByUser[$uid])) ? $shopsByUser[$uid] : [];
        $co = null;
        $linkedId = (int) ($s->company_id ?? 0);
        if ($companyName === '' && $linkedId > 0) {
            $co = $this->companyBrief($linkedId);
            if ($co) {
                $companyName = $co['name'];
            }
        }
        if ($companyName === '' && $shops) {
            // 与供应详情一致：账号没填公司名时，用该会员最新的旺铺名
            $co = $shops[0];
            foreach ($shops as $shop) {
                if ((int) ($shop['id'] ?? 0) > (int) ($co['id'] ?? 0)) {
                    $co = $shop;
                }
            }
            $companyName = trim((string) ($co['name'] ?? ''));
            if ($companyName === '管理员') {
                $companyName = '';
                $co = null;
            }
        } elseif ($companyName !== '' && $shops && !$co) {
            $hits = array_values(array_filter(
                $shops,
                static fn ($c) => $c['name'] === $companyName
            ));
            if (count($hits) === 1) {
                $co = $hits[0];
            }
        }

        return [
            'id' => (int) $s->id,
            'title' => $s->title,
            'thumb' => $s->thumb ?: '',
            'price' => $s->price !== null ? (string) $s->price : null,
            'unit' => $s->unit,
            'area' => $s->area ?? null,
            'brand' => $s->brand ?? null,
            'is_hot' => (bool) ($s->is_hot ?? false),
            'is_urgent' => (bool) ($s->is_urgent ?? false),
            'category_id' => (int) ($s->category_id ?? 0) ?: null,
            'user_id' => $uid ?: null,
            'username' => $s->user->username ?? '',
            'company_id' => $co['id'] ?? null,
            'company_slug' => $co['slug'] ?? '',
            'company_name' => $companyName,
            ...\app\common\VipEntitlement::memberMark($s->user ?? null),
            'refreshed_at' => $s->refreshed_at,
        ];
    }

    /** 首页材料板块推广位：每个板块 5 个，编码 floor_xxx_1 … floor_xxx_5。 */
    private function floorPlaceBase(string $name): string
    {
        return match ($name) {
            '有机硅单体/中间体' => 'floor_mono',
            '硅油' => 'floor_oil',
            '硅橡胶' => 'floor_rubber',
            '有机硅助剂/填料' => 'floor_addon',
            '机械设备' => 'floor_equip',
            '硅烷' => 'floor_silane',
            '胶粘剂' => 'floor_glue',
            '硅树脂' => 'floor_resin',
            default => '',
        };
    }

    /** @return array<int, array<string, mixed>> */
    private function floorPromos(string $base, array $shopsByUser): array
    {
        $now = date('Y-m-d H:i:s');
        $ads = Ad::where('is_active', 1)->whereLike('position', $base.'%')->order('sort', 'asc')->order('id', 'asc')->select();
        $bySlot = [];
        foreach ($ads as $ad) {
            if ($ad->starts_at && (string) $ad->starts_at > $now) {
                continue;
            }
            if ($ad->ends_at && (string) $ad->ends_at < $now) {
                continue;
            }
            $parsed = \app\common\FloorSlots::parseFloor((string) $ad->position);
            $slot = $parsed['slot'];
            $childId = $parsed['child'];
            if ($slot < 1) {
                $slot = max(1, min(5, (int) ($ad->sort ?: 1)));
            }
            if ($slot < 1 || $slot > 5) {
                continue;
            }
            $key = $childId.':'.$slot;
            if (isset($bySlot[$key])) {
                continue;
            }
            $id = 0;
            if (preg_match('#/sell/(\d+)#', (string) ($ad->link ?? ''), $mm)) {
                $id = (int) $mm[1];
            }
            if ($id <= 0) {
                continue;
            }
            $supply = Supply::publishedQuery()->with(['category', 'user'])->find($id);
            if (!$supply) {
                continue;
            }
            $card = $this->supplyCard($supply, $shopsByUser);
            $card['is_promoted'] = true;
            $card['promo_slot'] = $slot;
            $card['category_id'] = $childId;
            $bySlot[$key] = $card;
        }
        ksort($bySlot);

        return array_values($bySlot);
    }

    /** @param array<int> $userIds @return array<int, array<int, array{id:int,slug:string,name:string}>> */
    private function shopsByUser(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        $index = [];
        if (!$userIds) {
            return $index;
        }
        $rows = Company::whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->field('id,user_id,slug,name')
            ->select();
        foreach ($rows as $co) {
            $index[(int) $co->user_id][] = [
                'id' => (int) $co->id,
                'slug' => $co->slug ?: (string) $co->id,
                'name' => trim((string) $co->name),
            ];
        }

        return $index;
    }

    /** @return list<array<string, mixed>> */
    private function quoteProductsForHome(): array
    {
        $products = QuoteProduct::where('is_active', 1)->order('sort', 'asc')->limit(16)->select();
        $ids = [];
        foreach ($products as $p) {
            $ids[] = (int) $p->id;
        }
        $byProduct = [];
        $seriesFrom = date('Y-m-d', strtotime('-14 days'));
        if ($ids) {
            // 只取各品种「最新有价日」再回看 14 天；禁止全表历史进内存（会 OOM 导致首页全空）
            $latestByPid = [];
            foreach ($ids as $pid) {
                $maxOn = QuoteProductPrice::where('product_id', $pid)
                    ->whereNotNull('price_avg')
                    ->max('quoted_on');
                if ($maxOn) {
                    $latestByPid[$pid] = substr((string) $maxOn, 0, 10);
                }
            }
            $fromDates = [];
            foreach ($latestByPid as $pid => $lastDate) {
                $fromDates[$pid] = date('Y-m-d', strtotime($lastDate . ' -14 days'));
            }
            $seriesFrom = $fromDates ? min($fromDates) : $seriesFrom;

            $rows = QuoteProductPrice::whereIn('product_id', $ids)
                ->where('quoted_on', '>=', $seriesFrom)
                ->field('product_id,quoted_on,price_avg,price_min,price_max')
                ->order('quoted_on', 'asc')
                ->select();
            foreach ($rows as $row) {
                $avg = $row->price_avg !== null ? (float) $row->price_avg : null;
                if ($avg === null) {
                    continue;
                }
                $pid = (int) $row->product_id;
                $date = substr((string) $row->quoted_on, 0, 10);
                $pidFrom = $fromDates[$pid] ?? $seriesFrom;
                if ($date < $pidFrom) {
                    continue;
                }
                $byProduct[$pid][] = [
                    'date' => $date,
                    'label' => substr($date, 5),
                    'price_avg' => round($avg, 2),
                    'price_min' => $row->price_min !== null ? round((float) $row->price_min, 2) : null,
                    'price_max' => $row->price_max !== null ? round((float) $row->price_max, 2) : null,
                ];
            }
        }
        // 公开首页截断走势深度：以「最新有价日」为终点取近 7 天
        $regionBriefs = $this->regionBriefsFor($ids, $seriesFrom);
        $out = [];
        foreach ($products as $p) {
            $item = $p->toArray();
            $series = $byProduct[(int) $p->id] ?? [];
            if ($series) {
                $lastDate = (string) ($series[count($series) - 1]['date'] ?? date('Y-m-d'));
                $from = date('Y-m-d', strtotime($lastDate . ' -6 days'));
                $series = array_values(array_filter($series, static fn ($row) => ($row['date'] ?? '') >= $from));
            }
            $item['series'] = $series;
            $item['regions'] = $regionBriefs[(int) $p->id] ?? [];
            $item['market'] = '均价';
            $out[] = $item;
        }

        return $out;
    }

    /** @param list<int> $ids @return array<int, list<array{name:string,price:float,prev:float,quoted_on:string}>> */
    private function regionBriefsFor(array $ids, string $from): array
    {
        if (!$ids) {
            return [];
        }
        try {
            // 只拉窗口内数据，避免地区价全历史撑爆内存
            $rows = QuoteRegionPrice::whereIn('product_id', $ids)
                ->where('quoted_on', '>=', $from)
                ->field('product_id,region,price,quoted_on')
                ->order('quoted_on', 'asc')
                ->select();
        } catch (\Throwable $e) {
            return [];
        }
        $by = [];
        foreach ($rows as $row) {
            $pid = (int) $row->product_id;
            $name = (string) $row->region;
            $by[$pid][$name][] = [
                'price' => (float) $row->price,
                'quoted_on' => substr((string) $row->quoted_on, 0, 10),
            ];
        }
        $out = [];
        foreach ($by as $pid => $regions) {
            foreach ($regions as $name => $list) {
                $last = $list[count($list) - 1];
                $on = (string) ($last['quoted_on'] ?? '');
                if ($on < $from) {
                    continue;
                }
                $prev = count($list) >= 2 ? $list[count($list) - 2] : $last;
                $out[$pid][] = [
                    'name' => $name,
                    'price' => round((float) $last['price'], 2),
                    'prev' => round((float) $prev['price'], 2),
                    'quoted_on' => $on,
                ];
            }
        }

        return $out;
    }

    public function index(): Response
    {
        $payload = Cache::get('api:home:v34');
        if (!$payload) {
            $payload = $this->build();
            Cache::set('api:home:v34', $payload, 300);
        }

        return json_data($payload);
    }

    private function build(): array
    {
        $meta = $this->floorMeta();
        $rootCats = Category::whereNull('parent_id')->where('module', 'sell')->where('is_active', 1)
            ->with(['children'])->order('sort', 'asc')
            ->field('id,parent_id,name,slug,sort,module,is_active,icon')->select();

        $allChildMap = [];
        foreach ($rootCats as $cat) {
            $allChildMap[$cat->id] = $cat->id;
            foreach ($cat->children ?: [] as $ch) {
                $allChildMap[$ch->id] = $cat->id;
            }
        }

        // 企业挂在 company 模块分类树上，需按名称对齐到 sell 楼层，否则各楼层会落到同一批兜底企业
        $companyRoots = Category::whereNull('parent_id')->where('module', 'company')->where('is_active', 1)
            ->with(['children'])
            ->field('id,parent_id,name,slug,sort,module,is_active')->select();
        $companyNameToRoot = [];
        $companyChildMap = [];
        foreach ($companyRoots as $cr) {
            $companyNameToRoot[$cr->name] = (int) $cr->id;
            $companyChildMap[(int) $cr->id] = (int) $cr->id;
            foreach ($cr->children ?: [] as $ch) {
                $companyChildMap[(int) $ch->id] = (int) $cr->id;
            }
        }
        $sellNameAliases = [
            '硅烷' => ['硅烷/硅树脂', '硅烷'],
            '硅树脂' => ['硅烷/硅树脂', '硅树脂'],
        ];
        $sellToCompanyRoots = [];
        foreach ($rootCats as $cat) {
            $ids = [];
            $names = $sellNameAliases[$cat->name] ?? [$cat->name];
            foreach ($names as $n) {
                if (!empty($companyNameToRoot[$n])) {
                    $ids[] = $companyNameToRoot[$n];
                }
            }
            $sellToCompanyRoots[(int) $cat->id] = array_values(array_unique($ids));
        }

        $supplies = Supply::publishedQuery()->order('refreshed_at', 'desc')->limit(80)
            ->with(['user'])
            ->field('id,title,thumb,price,unit,area,brand,refreshed_at,category_id,user_id,company_id,is_hot,is_urgent')->select();
        $companies = Company::publishedQuery()->order('is_featured', 'desc')->order('id', 'desc')->limit(320)
            ->field('id,name,slug,logo,is_certified,is_featured,area,address,category_id,business,intro,company_type,scale,business_mode,tags,vip_level,user_id')->select();

        // 每个楼层单独取供应。原先只拿全站最新 80 条，机械设备等楼层经常是空的，首页就用没有企业的占位卡。
        $idsByRoot = [];
        foreach ($allChildMap as $cid => $root) {
            $idsByRoot[(int) $root][] = (int) $cid;
        }
        $floorSupplyRows = [];
        foreach ($rootCats as $cat) {
            $ids = $idsByRoot[(int) $cat->id] ?? [(int) $cat->id];
            $floorQuery = Supply::publishedQuery()->alias('s')->with(['user'])->whereIn('s.category_id', $ids);
            supply_natural_order($floorQuery, 's');
            $rows = $floorQuery
                ->limit(80)
                ->field('s.id,s.title,s.thumb,s.price,s.unit,s.area,s.brand,s.refreshed_at,s.category_id,s.user_id,s.company_id,s.is_hot,s.is_urgent')
                ->select();
            foreach ($rows as $s) {
                $floorSupplyRows[] = $s;
            }
        }

        $supplyUserIds = [];
        foreach ($supplies as $s) {
            if ($s->user_id) {
                $supplyUserIds[] = (int) $s->user_id;
            }
        }
        foreach ($floorSupplyRows as $s) {
            if ($s->user_id) {
                $supplyUserIds[] = (int) $s->user_id;
            }
        }
        $shopsByUser = $this->shopsByUser($supplyUserIds);

        $suppliesByRoot = [];
        foreach ($floorSupplyRows as $s) {
            $rootId = $allChildMap[$s->category_id] ?? null;
            if (!$rootId) {
                continue;
            }
            $suppliesByRoot[$rootId][] = $this->supplyCard($s, $shopsByUser);
        }

        $companyUserIds = [];
        foreach ($companies as $c) {
            if ($c->user_id) {
                $companyUserIds[] = (int) $c->user_id;
            }
        }
        $companyUsers = [];
        if ($companyUserIds) {
            foreach (User::whereIn('id', array_values(array_unique($companyUserIds)))->select() as $u) {
                $companyUsers[(int) $u->id] = $u;
            }
        }

        $companiesByCompanyRoot = [];
        foreach ($companies as $c) {
            $rootId = $companyChildMap[(int) $c->category_id] ?? null;
            if (!$rootId) {
                continue;
            }
            $owner = $companyUsers[(int) ($c->user_id ?? 0)] ?? null;
            $mark = \app\common\VipEntitlement::memberMark($owner);
            if (($mark['member_grade'] ?? '') === '') {
                $mark = ['member_level' => 0, 'member_badge' => '普通会员', 'member_grade' => 'normal'];
            }
            $companiesByCompanyRoot[$rootId][] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'logo' => $c->logo ?: '',
                'is_certified' => (bool) $c->is_certified,
                'is_featured' => (bool) $c->is_featured,
                'vip_level' => (int) ($c->vip_level ?? 0),
                'member_level' => (int) ($mark['member_level'] ?? 0),
                'member_badge' => (string) ($mark['member_badge'] ?? ''),
                'member_grade' => (string) ($mark['member_grade'] ?? ''),
                'area' => $c->area,
                'address' => $c->address,
                'business' => $c->business,
                'intro' => $c->intro ?? '',
                'company_type' => $c->company_type,
                'scale' => $c->scale,
                'business_mode' => $c->business_mode,
                'tags' => $c->tags ?? '',
                'category_id' => (int) $c->category_id,
            ];
        }

        $floors = [];
        foreach ($rootCats as $cat) {
            $m = $meta[$cat->name] ?? [
                'image' => '/img/product.svg',
                'feature' => $cat->name.'精选供应',
                'brands' => ['示范品牌A', '示范品牌B'],
                'groups' => [],
            ];
            $cos = [];
            $seen = [];
            foreach ($sellToCompanyRoots[(int) $cat->id] ?? [] as $crid) {
                foreach ($companiesByCompanyRoot[$crid] ?? [] as $co) {
                    if (isset($seen[$co['id']])) {
                        continue;
                    }
                    // 硅烷 / 硅树脂共用「硅烷/硅树脂」时，用主营关键词再拆一层
                    if ($cat->name === '硅烷' && !preg_match('/硅烷|偶联|交联|硅氧烷/u', (string) ($co['business'] ?? $co['name']))) {
                        if (preg_match('/树脂|MQ/u', (string) ($co['business'] ?? ''))) {
                            continue;
                        }
                    }
                    if ($cat->name === '硅树脂' && !preg_match('/树脂|MQ/u', (string) ($co['business'] ?? $co['name']))) {
                        if (preg_match('/偶联|交联剂/u', (string) ($co['business'] ?? ''))) {
                            continue;
                        }
                    }
                    $seen[$co['id']] = true;
                    $cos[] = $co;
                    if (count($cos) >= 8) {
                        break 2;
                    }
                }
            }
            // 关键词过滤过严时，退回该 company 根下真实企业（仍不跨类凑数）
            if (count($cos) < 3) {
                $cos = [];
                $seen = [];
                foreach ($sellToCompanyRoots[(int) $cat->id] ?? [] as $crid) {
                    foreach ($companiesByCompanyRoot[$crid] ?? [] as $co) {
                        if (isset($seen[$co['id']])) {
                            continue;
                        }
                        $seen[$co['id']] = true;
                        $cos[] = $co;
                        if (count($cos) >= 8) {
                            break 2;
                        }
                    }
                }
            }
            $place = $this->floorPlaceBase((string) $cat->name);
            $floors[] = [
                'category' => ['id' => (int) $cat->id, 'name' => $cat->name, 'slug' => $cat->slug],
                'image' => $m['image'],
                'brand_logo' => $m['brand_logo'] ?? '',
                'feature' => $m['feature'],
                'brands' => $m['brands'],
                'groups' => $m['groups'] ?? [],
                'children' => array_map(fn ($ch) => [
                    'id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug,
                ], $cat->children ? $cat->children->all() : []),
                'supplies' => array_slice($suppliesByRoot[$cat->id] ?? [], 0, 80),
                'promos' => $place !== '' ? $this->floorPromos($place, $shopsByUser) : [],
                'promo_code' => $place,
                'companies' => $cos,
            ];
        }

        $categories = [];
        foreach ($rootCats as $c) {
            $categories[] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'icon' => (string) ($c->icon ?? ''),
                'children' => array_map(fn ($ch) => [
                    'id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug,
                ], $c->children ? $c->children->all() : []),
            ];
        }

        $banners = $this->resolveBanners();

        $articles = array_map(
            static fn ($a) => [
                'id' => (int) $a->id,
                'title' => $a->title,
                'type' => $a->type,
                'published_at' => $a->published_at,
                'views' => $a->displayViews(),
                'summary' => $a->summary,
            ],
            Article::publishedQuery()->order('published_at', 'desc')->limit(24)->select()->all()
        );

        $featuredCompanies = [];
        foreach ($companies as $c) {
            if ($c->is_featured) {
                $featuredCompanies[] = [
                    'id' => (int) $c->id, 'name' => $c->name, 'slug' => $c->slug,
                    'logo' => $c->logo ?: '',
                    'is_certified' => (bool) $c->is_certified,
                    'is_featured' => true,
                    'vip_level' => (int) ($c->vip_level ?? 0),
                    'area' => $c->area,
                    'address' => $c->address,
                    'business' => $c->business,
                    'intro' => $c->intro ?? '',
                    'company_type' => $c->company_type,
                    'scale' => $c->scale,
                    'business_mode' => $c->business_mode,
                    'tags' => $c->tags ?? '',
                ];
            }
            if (count($featuredCompanies) >= 8) {
                break;
            }
        }

        $supplyList = [];
        foreach (array_slice($supplies->all(), 0, 30) as $s) {
            $supplyList[] = $this->supplyCard($s, $shopsByUser);
        }

        $quotes = array_values(array_filter($articles, fn ($a) => ($a['type'] ?? '') === 'quote'));
        $quotes = array_slice($quotes, 0, 8);

        return [
            'banners' => $banners,
            'categories' => $categories,
            'floors' => $floors,
            'supplies' => $supplyList,
            'purchases' => array_map(
                static fn ($p) => [
                    'id' => (int) $p->id,
                    'title' => $p->title,
                    'budget' => $p->budget !== null ? (string) $p->budget : null,
                    'quantity' => $p->quantity,
                    'area' => $p->area ?? null,
                    'is_hot' => (bool) ($p->is_hot ?? false),
                    'is_urgent' => (bool) ($p->is_urgent ?? false),
                    'refreshed_at' => $p->refreshed_at,
                ],
                Purchase::publishedQuery()->order('refreshed_at', 'desc')->limit(30)->select()->all()
            ),
            'products' => Product::publishedQuery()->order('id', 'desc')->limit(8)
                ->field('id,title,thumb,price,unit,sales')->select()->toArray(),
            'articles' => $articles,
            'rank_articles' => array_map(
                static fn ($a) => [
                    'id' => (int) $a->id,
                    'title' => $a->title,
                    'views' => $a->displayViews(),
                    'published_at' => $a->published_at,
                ],
                Article::orderByDisplayViews(Article::publishedQuery(), 'desc')->limit(24)->select()->all()
            ),
            'quotes' => $quotes,
            'quote_products' => $this->quoteProductsForHome(),
            'companies' => $featuredCompanies,
            'announcements' => Announcement::where('is_active', 1)->order('sort', 'desc')->limit(10)
                ->field('id,title,link,created_at')->select()->toArray(),
            'helps' => [
                ['title' => 'B2B电商运营外包服务', 'link' => '/pages/help'],
                ['title' => '怎样拥有企业旺铺？', 'link' => '/pages/help'],
                ['title' => '产品信息怎么发？', 'link' => '/pages/help'],
                ['title' => '如何让信息排名提前', 'link' => '/pages/help'],
            ],
            'links' => FriendLink::where('is_active', 1)->order('sort', 'asc')->limit(20)
                ->field('id,name,url')->select()->toArray(),
            'stats' => [
                'supplies' => (int) Supply::publishedQuery()->count(),
                'purchases' => (int) Purchase::publishedQuery()->count(),
                'companies' => (int) Company::publishedQuery()->count(),
                'members' => (int) User::where('is_admin', 0)->count(),
            ],
            'ads' => [
                'home_banner' => $banners,
                'news_top' => Ad::slides('news_top', 8),
                // 首页分类面板展位（企业广告图，点击跳官网/旺铺）
                'home_portal' => $this->resolvePortalAds(),
            ],
        ];
    }

    /** 分类面板三个小图：只读首页分类展位，不从首页轮播里拆。 */
    private function resolvePortalAds(): array
    {
        return Ad::slides('home_portal', 6);
    }

    private function resolveBanners(): array
    {
        $ads = Ad::slides('home_banner', 8);
        if ($ads) {
            return array_map(static fn ($a) => [
                'id' => $a['id'],
                'title' => $a['title'],
                'image' => $a['image'],
                'link' => $a['link'],
                'sort' => $a['sort'],
            ], $ads);
        }

        $rows = Banner::where('is_active', 1)
            ->whereIn('position', ['home', 'home_banner'])
            ->order('sort', 'asc')
            ->limit(8)
            ->field('id,title,image,link,sort')
            ->select();
        $out = [];
        foreach ($rows as $b) {
            if (!Ad::usableImage($b->image)) {
                continue;
            }
            $title = trim((string) ($b->title ?? ''));
            $link = trim((string) ($b->link ?: ''));
            if ($title === '' && $link === '') {
                continue;
            }
            $out[] = [
                'id' => (int) $b->id,
                'title' => $title,
                'image' => $b->image,
                'link' => $link,
                'sort' => (int) $b->sort,
            ];
        }
        return $out;
    }
}
