<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Announcement;
use app\model\Article;
use app\model\Banner;
use app\model\Category;
use app\model\Company;
use app\model\FriendLink;
use app\model\Product;
use app\model\Purchase;
use app\model\QuoteProduct;
use app\model\Supply;
use app\model\User;
use think\Response;

class Home extends BaseController
{
    private function floorMeta(): array
    {
        return [
            '有机硅单体/中间体' => [
                'image' => '/img/floor-mono.svg',
                'feature' => 'DMC / D4 中间体现货',
                'brands' => ['星火', '科毅', '山东东岳', '合盛'],
                'groups' => [
                    ['title' => '甲基中间体', 'items' => ['二甲基环硅氧烷（DMC）', '六甲基环三硅氧烷', '八甲基环四硅氧烷（D4）', '十甲基环五硅氧烷（D5）']],
                    ['title' => '甲基单体', 'items' => ['一甲基三氯硅烷（M1）', '二甲基二氯硅烷（M2）', '三甲基一氯硅烷（M3）']],
                    ['title' => '苯基单体', 'items' => ['苯基氯硅烷', '甲基乙烯基氯硅烷', '乙基三氯硅烷', '丙基三氯硅烷', '乙烯基三氯硅烷', 'γ-氯丙基三氯硅烷']],
                    ['title' => '氟硅单体', 'items' => ['氟硅单体']],
                ],
            ],
            '硅油' => [
                'image' => '/img/floor-oil.svg',
                'feature' => '甲基硅油 · 107胶',
                'brands' => ['黄山强力', '枣阳华威', '强力化工', '百纯'],
                'groups' => [
                    ['title' => '甲基硅油', 'items' => ['二甲基硅油', '羟基硅油', '乙烯基硅油', '氨基硅油', '高粘度硅油', '水溶性硅油', '乳化硅油', '硅油乳液']],
                    ['title' => '含氢硅油', 'items' => ['高含氢硅油', '低含氢硅油', '含氢硅油乳液']],
                    ['title' => '苯基硅油', 'items' => ['苯甲基硅油', '高真空扩散泵油', '氟硅油']],
                    ['title' => '改性硅油', 'items' => ['流平剂', '润湿剂', '抗油剂', '聚醚改性硅油', '硬泡稳定剂', '农用有机硅助剂', '三元共聚硅油', '烷基改性硅油']],
                ],
            ],
            '硅橡胶' => [
                'image' => '/img/floor-rubber.svg',
                'feature' => '混炼胶 / 液体硅橡胶',
                'brands' => ['道康宁', '东莞子轩', '新安', '蓝星'],
                'groups' => [
                    ['title' => '101甲基乙烯基硅橡胶', 'items' => ['沉淀混炼胶', '耐高温胶', '模具硅橡胶', '加成型液体硅橡胶（LSR）', '自干型丝印硅胶']],
                    ['title' => '苯基硅橡胶', 'items' => ['苯基硅橡胶', '腈硅橡胶', '苯撑硅橡胶', '苯醚撑硅橡胶']],
                ],
            ],
            '有机硅助剂/填料' => [
                'image' => '/img/floor-addon.svg',
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
                'image' => '/img/floor-equip.svg',
                'feature' => '真空捏合机 / 反应釜',
                'brands' => ['太仓豪达', '强盛捏合机', '无锡华宸', '荣兴'],
                'groups' => [
                    ['title' => '硅橡胶设备', 'items' => ['真空捏合机', '行星分散搅拌机', '液态硅胶注射成型机', '硅橡胶挤出机', '液压式压料机', '三辊研磨机']],
                    ['title' => '有机硅全套设备', 'items' => ['不锈钢反应锅', '不锈钢反应釜', '列管冷凝器', '高效酒精回收塔', '实验设备']],
                ],
            ],
            '硅烷' => [
                'image' => '/img/floor-silane.svg',
                'feature' => '偶联剂 / 交联剂',
                'brands' => ['莱宝硅材料', '晨光', '拓格'],
                'groups' => [
                    ['title' => '硅烷偶联剂', 'items' => ['氨丙基三乙氧基硅烷', '氨丙基三甲氧基硅烷', '三乙氧基丙基硅烷', '苯基三甲氧基硅烷', '二苯基二甲氧基硅烷', '辛基三乙氧基硅烷', '氯丙基三乙氧基硅烷', '氯丙基三甲氧基硅烷']],
                    ['title' => '硅烷交联剂', 'items' => ['甲基三丁酮肟基硅烷', '甲基混合酮肟交联剂', '乙烯基三丁酮肟基硅烷', '甲基三甲氧基硅烷', '乙基三乙酰氧基硅烷', '乙烯基混合酮肟交联剂', '甲基三乙酰氧基硅烷']],
                ],
            ],
            '胶粘剂' => [
                'image' => '/img/floor-glue.svg',
                'feature' => '密封胶 · 灌封胶 · 玻璃胶',
                'brands' => ['中蓝晨光', '拓格电子', '乐迪雅'],
                'groups' => [
                    ['title' => '硅酮密封胶', 'items' => ['电子灌封胶', '玻璃胶', '胶粘剂', '中性耐候胶', '酸性玻璃胶']],
                ],
            ],
            '硅树脂' => [
                'image' => '/img/floor-resin.svg',
                'feature' => 'MQ硅树脂 / 甲基苯基硅树脂',
                'brands' => ['得尔塔', 'M-100系列'],
                'groups' => [
                    ['title' => '甲基苯基硅树脂', 'items' => ['甲基透明硅树脂', '甲基MQ硅树脂', '乙烯基MQ硅树脂']],
                ],
            ],
        ];
    }

    public function index(): Response
    {
        return json_data($this->build());
    }

    private function build(): array
    {
        $meta = $this->floorMeta();

        $rootCats = Category::whereNull('parent_id')
            ->where('module', 'sell')
            ->where('is_active', 1)
            ->with(['children'])
            ->order('sort', 'asc')
            ->field(['id', 'parent_id', 'name', 'slug', 'sort', 'module', 'is_active'])
            ->select();

        $allChildMap = [];
        foreach ($rootCats as $cat) {
            foreach ($cat->children ?? [] as $ch) {
                $allChildMap[(int) $ch->id] = (int) $cat->id;
            }
            $allChildMap[(int) $cat->id] = (int) $cat->id;
        }

        $supplies = Supply::publishedQuery()
            ->order('refreshed_at', 'desc')
            ->limit(80)
            ->field(['id', 'title', 'price', 'unit', 'refreshed_at', 'category_id'])
            ->select();

        $companies = Company::publishedQuery()
            ->order('is_featured', 'desc')
            ->limit(40)
            ->field(['id', 'name', 'slug', 'is_certified', 'is_featured', 'area', 'category_id', 'business'])
            ->select();

        $suppliesByRoot = [];
        foreach ($supplies as $s) {
            $rootId = $allChildMap[(int) $s->category_id] ?? null;
            if (!$rootId) {
                continue;
            }
            $suppliesByRoot[$rootId][] = [
                'id' => (int) $s->id,
                'title' => $s->title,
                'price' => $s->price !== null ? (string) $s->price : null,
                'unit' => $s->unit,
                'refreshed_at' => $s->refreshed_at,
            ];
        }

        $companiesByRoot = [];
        foreach ($companies as $c) {
            $rootId = $allChildMap[(int) $c->category_id] ?? null;
            if (!$rootId) {
                continue;
            }
            $companiesByRoot[$rootId][] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'is_certified' => (bool) $c->is_certified,
                'area' => $c->area,
            ];
        }

        $fallbackCos = [];
        foreach ($companies as $i => $c) {
            if ($i >= 6) {
                break;
            }
            $fallbackCos[] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'is_certified' => (bool) $c->is_certified,
                'area' => $c->area,
            ];
        }

        $floors = [];
        $categories = [];
        foreach ($rootCats as $cat) {
            $m = $meta[$cat->name] ?? [
                'image' => '/img/product.svg',
                'feature' => $cat->name.'精选供应',
                'brands' => ['示范品牌A', '示范品牌B'],
            ];
            $cos = array_slice($companiesByRoot[(int) $cat->id] ?? [], 0, 6);
            if (count($cos) < 3) {
                $cos = $fallbackCos;
            }
            $children = [];
            foreach ($cat->children ?? [] as $ch) {
                $children[] = ['id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug];
            }
            $floors[] = [
                'category' => ['id' => (int) $cat->id, 'name' => $cat->name, 'slug' => $cat->slug],
                'image' => $m['image'],
                'feature' => $m['feature'],
                'brands' => $m['brands'],
                'groups' => $m['groups'] ?? [],
                'children' => $children,
                'supplies' => array_slice($suppliesByRoot[(int) $cat->id] ?? [], 0, 8),
                'companies' => $cos,
            ];
            $categories[] = [
                'id' => (int) $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'children' => $children,
            ];
        }

        $banners = Banner::where('is_active', 1)
            ->where('position', 'home')
            ->order('sort', 'asc')
            ->limit(6)
            ->field(['id', 'title', 'image', 'link', 'sort'])
            ->select()
            ->toArray();
        if ($banners) {
            $banners[0]['image'] = '/img/banner-hero.svg';
        }

        $articles = Article::publishedQuery()
            ->order('published_at', 'desc')
            ->limit(12)
            ->field(['id', 'title', 'type', 'published_at', 'views', 'summary'])
            ->select()
            ->toArray();

        $suppliesTop = [];
        foreach ($supplies as $i => $s) {
            if ($i >= 12) {
                break;
            }
            $suppliesTop[] = [
                'id' => (int) $s->id,
                'title' => $s->title,
                'price' => $s->price !== null ? (string) $s->price : null,
                'unit' => $s->unit,
                'refreshed_at' => $s->refreshed_at,
            ];
        }

        $featuredCompanies = [];
        foreach ($companies as $c) {
            if (!(bool) $c->is_featured) {
                continue;
            }
            $featuredCompanies[] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'is_certified' => (bool) $c->is_certified,
                'area' => $c->area,
                'business' => $c->business,
            ];
            if (count($featuredCompanies) >= 8) {
                break;
            }
        }

        $quotes = [];
        foreach ($articles as $a) {
            if (($a['type'] ?? '') === 'quote') {
                $quotes[] = $a;
                if (count($quotes) >= 8) {
                    break;
                }
            }
        }

        return [
            'banners' => $banners,
            'categories' => $categories,
            'floors' => $floors,
            'supplies' => $suppliesTop,
            'purchases' => Purchase::publishedQuery()
                ->order('refreshed_at', 'desc')
                ->limit(12)
                ->field(['id', 'title', 'budget', 'quantity', 'refreshed_at'])
                ->select()
                ->toArray(),
            'products' => Product::publishedQuery()
                ->order('id', 'desc')
                ->limit(8)
                ->field(['id', 'title', 'thumb', 'price', 'unit', 'sales'])
                ->select()
                ->toArray(),
            'articles' => $articles,
            'rank_articles' => Article::publishedQuery()
                ->order('views', 'desc')
                ->limit(10)
                ->field(['id', 'title', 'views', 'published_at'])
                ->select()
                ->toArray(),
            'quotes' => $quotes,
            'quote_products' => QuoteProduct::where('is_active', 1)->order('sort', 'asc')->limit(8)->select()->toArray(),
            'companies' => $featuredCompanies,
            'announcements' => Announcement::activeQuery()
                ->order('sort', 'desc')
                ->limit(6)
                ->field(['id', 'title', 'link', 'created_at'])
                ->select()
                ->toArray(),
            'helps' => [
                ['title' => 'B2B电商运营外包服务', 'link' => '/pages/help'],
                ['title' => '怎样拥有企业旺铺？', 'link' => '/pages/help'],
                ['title' => '产品信息怎么发？', 'link' => '/pages/help'],
                ['title' => '如何让信息排名提前', 'link' => '/pages/help'],
            ],
            'links' => FriendLink::activeQuery()
                ->order('sort', 'asc')
                ->limit(20)
                ->field(['id', 'name', 'url'])
                ->select()
                ->toArray(),
            'stats' => [
                'supplies' => (int) Supply::publishedQuery()->count(),
                'purchases' => (int) Purchase::publishedQuery()->count(),
                'companies' => (int) Company::publishedQuery()->count(),
                'members' => (int) User::where('is_admin', 0)->count(),
            ],
            'ads' => ['home_right' => [], 'home_banner' => []],
        ];
    }
}
