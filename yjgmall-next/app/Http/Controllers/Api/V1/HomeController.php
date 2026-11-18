<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Company;
use App\Models\FriendLink;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\QuoteProduct;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    private function floorMeta(): array
    {
        // groups: 对齐线上楼层「二级分类标题 + 可点子项」
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

    public function __invoke()
    {
        $payload = Cache::store('file')->remember('api:home:v7', 300, fn () => $this->build());

        return response()->json(['data' => $payload]);
    }

    private function build(): array
    {
        $meta = $this->floorMeta();

        $rootCats = Category::query()
            ->whereNull('parent_id')
            ->where('module', 'sell')
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')->select(['id', 'parent_id', 'name', 'slug', 'sort', 'is_active'])])
            ->orderBy('sort')
            ->get(['id', 'parent_id', 'name', 'slug', 'sort', 'module', 'is_active']);

        // 一次取出供应/企业，再按分类分组，避免楼层 N+1
        $allChildMap = [];
        foreach ($rootCats as $cat) {
            foreach ($cat->children as $ch) {
                $allChildMap[$ch->id] = $cat->id;
            }
            $allChildMap[$cat->id] = $cat->id;
        }

        $supplies = Supply::published()
            ->latest('refreshed_at')
            ->limit(80)
            ->get(['id', 'title', 'price', 'unit', 'refreshed_at', 'category_id']);

        $companies = Company::published()
            ->orderByDesc('is_featured')
            ->limit(40)
                ->get(['id', 'name', 'slug', 'is_certified', 'is_featured', 'area', 'category_id', 'business']);

        $suppliesByRoot = [];
        foreach ($supplies as $s) {
            $rootId = $allChildMap[$s->category_id] ?? null;
            if (! $rootId) {
                continue;
            }
            $suppliesByRoot[$rootId][] = $s->only(['id', 'title', 'price', 'unit', 'refreshed_at']);
        }

        $companiesByRoot = [];
        foreach ($companies as $c) {
            $rootId = $allChildMap[$c->category_id] ?? null;
            if (! $rootId) {
                continue;
            }
            $companiesByRoot[$rootId][] = $c->only(['id', 'name', 'slug', 'is_certified', 'area']);
        }

        $fallbackCos = $companies->take(6)->map->only(['id', 'name', 'slug', 'is_certified', 'area'])->values()->all();

        $floors = $rootCats->map(function (Category $cat) use ($meta, $suppliesByRoot, $companiesByRoot, $fallbackCos) {
            $m = $meta[$cat->name] ?? [
                'image' => '/img/product.svg',
                'feature' => $cat->name.'精选供应',
                'brands' => ['示范品牌A', '示范品牌B'],
            ];
            $cos = array_slice($companiesByRoot[$cat->id] ?? [], 0, 6);
            if (count($cos) < 3) {
                $cos = $fallbackCos;
            }

            return [
                'category' => ['id' => $cat->id, 'name' => $cat->name, 'slug' => $cat->slug],
                'image' => $m['image'],
                'feature' => $m['feature'],
                'brands' => $m['brands'],
                'groups' => $m['groups'] ?? [],
                'children' => $cat->children->map(fn ($ch) => [
                    'id' => $ch->id,
                    'name' => $ch->name,
                    'slug' => $ch->slug,
                ])->values()->all(),
                'supplies' => array_slice($suppliesByRoot[$cat->id] ?? [], 0, 8),
                'companies' => $cos,
            ];
        })->values()->all();

        $categories = $rootCats->map(fn (Category $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'children' => $c->children->map(fn ($ch) => [
                'id' => $ch->id,
                'name' => $ch->name,
                'slug' => $ch->slug,
            ])->values()->all(),
        ])->values()->all();

        $banners = Banner::query()
            ->where('is_active', true)
            ->where('position', 'home')
            ->orderBy('sort')
            ->limit(6)
            ->get(['id', 'title', 'image', 'link', 'sort'])
            ->toArray();
        if ($banners) {
            $banners[0]['image'] = '/img/banner-hero.svg';
        }

        $articles = Article::published()->latest('published_at')->limit(12)
            ->get(['id', 'title', 'type', 'published_at', 'views', 'summary'])->toArray();

        return [
            'banners' => $banners,
            'categories' => $categories,
            'floors' => $floors,
            'supplies' => $supplies->take(12)->map->only(['id', 'title', 'price', 'unit', 'refreshed_at'])->values()->all(),
            'purchases' => Purchase::published()->latest('refreshed_at')->limit(12)
                ->get(['id', 'title', 'budget', 'quantity', 'refreshed_at'])->toArray(),
            'products' => Product::published()->latest()->limit(8)
                ->get(['id', 'title', 'thumb', 'price', 'unit', 'sales'])->toArray(),
            'articles' => $articles,
            'rank_articles' => Article::published()->orderByDesc('views')->limit(10)
                ->get(['id', 'title', 'views', 'published_at'])->toArray(),
            'quotes' => collect($articles)->where('type', 'quote')->take(8)->values()->all(),
            'quote_products' => QuoteProduct::query()->where('is_active', true)->orderBy('sort')->limit(8)->get()->toArray(),
            'companies' => $companies->where('is_featured', true)->take(8)->values()
                ->map->only(['id', 'name', 'slug', 'is_certified', 'area', 'business'])->all(),
            'announcements' => Announcement::active()->orderByDesc('sort')->limit(6)
                ->get(['id', 'title', 'link', 'created_at'])->toArray(),
            'helps' => [
                ['title' => 'B2B电商运营外包服务', 'link' => '/pages/help'],
                ['title' => '怎样拥有企业旺铺？', 'link' => '/pages/help'],
                ['title' => '产品信息怎么发？', 'link' => '/pages/help'],
                ['title' => '如何让信息排名提前', 'link' => '/pages/help'],
            ],
            'links' => FriendLink::active()->orderBy('sort')->limit(20)
                ->get(['id', 'name', 'url'])->toArray(),
            'stats' => [
                'supplies' => (int) Supply::published()->count(),
                'purchases' => (int) Purchase::published()->count(),
                'companies' => (int) Company::published()->count(),
                'members' => (int) User::query()->where('is_admin', false)->count(),
            ],
            'ads' => ['home_right' => [], 'home_banner' => []],
        ];
    }
}
