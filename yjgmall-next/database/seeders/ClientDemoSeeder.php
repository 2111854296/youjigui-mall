<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Ad;
use App\Models\Announcement;
use App\Models\Article;
use App\Models\Banner;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Company;
use App\Models\Favorite;
use App\Models\FinanceRecord;
use App\Models\Friendship;
use App\Models\Inquiry;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\QuoteProduct;
use App\Models\Supply;
use App\Models\TradeAlert;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientDemoSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('is_admin', true)->first()
            ?? User::query()->where('email', 'admin@yjgmall.test')->first();

        $demo = User::query()->where('username', '2111854296')->first();
        if ($demo) {
            $demo->update([
                'name' => '沈',
                'group_code' => 'enterprise',
                'status' => 'active',
                'member_type' => 'company',
                'company_name' => '东莞德鑫有机硅材料有限公司',
                'balance' => 12880.50,
                'deposit' => 2000,
                'points' => 1260,
                'credit' => 98,
                'level' => 2,
                'vip_expire_at' => now()->addYear(),
                'is_certified' => true,
                'mobile_verified' => true,
                'bank_verified' => true,
                'truename_verified' => true,
                'truename' => '沈先生',
                'bank_name' => '中国工商银行东莞分行',
                'bank_account' => '6222 **** **** 8899',
                'province' => '广东省',
                'city' => '东莞市',
                'district' => '虎门镇',
                'area' => '广东东莞',
                'qq' => '454053243',
                'avatar' => 'https://placehold.co/128x128/255d9a/ffffff/png?text=Shen',
                'online_status' => 'online',
            ]);
        }

        // 个人会员演示账号（买家侧：采购/购物车/订单，无旺铺与供应）
        User::updateOrCreate(
            ['username' => 'personal01'],
            [
                'name' => '张个人',
                'email' => 'personal01@yjgmall.test',
                'mobile' => '13800001111',
                'password' => Hash::make('123456'),
                'member_type' => 'personal',
                'group_code' => 'personal',
                'status' => 'active',
                'company_name' => null,
                'balance' => 500,
                'deposit' => 0,
                'points' => 80,
                'credit' => 20,
                'level' => 1,
                'is_certified' => false,
                'mobile_verified' => true,
                'bank_verified' => false,
                'truename_verified' => false,
                'area' => '广东深圳',
                'avatar' => 'https://placehold.co/128x128/666666/ffffff/png?text=P',
                'online_status' => 'online',
            ]
        );

        $childCats = Category::query()
            ->whereNotNull('parent_id')
            ->where('module', 'sell')
            ->pluck('id')
            ->all();
        if (! $childCats) {
            $childCats = Category::query()->pluck('id')->all();
        }
        $pickCat = fn () => $childCats[array_rand($childCats)];

        $areas = ['广东东莞', '广东广州', '江苏镇江', '浙江嘉兴', '山东淄博', '江西九江', '四川成都', '上海', '福建泉州'];
        $brands = ['合盛', '新安', '东岳', '瓦克', '道康宁', '星火', '晨光', '强力', '百纯', '莱宝'];

        $companies = [
            ['东莞德鑫有机硅材料有限公司', 'dexin-silicone', '广东东莞', 'DMC、107胶、混炼胶'],
            ['黄山市强力化工有限公司', 'qiangli-chem', '安徽黄山', '硅油、改性硅油'],
            ['太仓市豪达化工防腐设备厂', 'haoda-equip', '江苏太仓', '捏合机、反应釜'],
            ['广州莱宝硅材料有限公司', 'laibao-si', '广东广州', '硅烷偶联剂'],
            ['江西乐迪雅节能科技有限公司', 'lediya', '江西赣州', '密封胶、灌封胶'],
            ['无锡市华丰有机硅材料厂', 'huafeng-si', '江苏无锡', '硅油乳液'],
            ['上海百纯贸易有限公司', 'baichun', '上海', '进口硅橡胶'],
            ['东莞市硅众网络科技有限公司', 'guizhong', '广东东莞', '助剂、色母'],
            ['如皋市荣兴捏合机械制造有限公司', 'rongxing', '江苏南通', '真空捏合机'],
            ['福建德兴源化工有限公司', 'dexingyuan', '福建三明', '中间体、单体'],
            ['淄博凯华硅业有限公司', 'kaihua', '山东淄博', 'D4、DMC'],
            ['嘉兴合创新材料有限公司', 'hechuang', '浙江嘉兴', '液体硅橡胶'],
        ];

        $sellers = [];
        foreach ($companies as $i => [$name, $slug, $area, $biz]) {
            $user = User::updateOrCreate(
                ['username' => 'seller'.str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)],
                [
                    'name' => mb_substr($name, 0, 2).'经理',
                    'email' => 'seller'.($i + 1).'@yjgmall.test',
                    'mobile' => '139'.str_pad((string) (10000000 + $i), 8, '0', STR_PAD_LEFT),
                    'password' => Hash::make('123456'),
                    'company_name' => $name,
                    'member_type' => 'company',
                    'group_code' => 'enterprise',
                    'status' => 'active',
                    'level' => $i < 4 ? 2 : 1,
                    'vip_expire_at' => now()->addMonths(6),
                    'is_certified' => true,
                    'points' => 200 + $i * 30,
                    'balance' => 1000 + $i * 100,
                    'area' => $area,
                    'avatar' => 'https://placehold.co/96x96/e85d04/ffffff/png?text=S'.($i + 1),
                ]
            );
            $sellers[] = $user;

            Company::updateOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $user->id,
                    'name' => $name,
                    'category_id' => $pickCat(),
                    'logo' => 'https://placehold.co/120x120/255d9a/ffffff/png?text='.rawurlencode(mb_substr($name, 0, 2)),
                    'contact' => $user->name,
                    'phone' => $user->mobile,
                    'website' => 'https://www.yjgmall.com',
                    'address' => $area.'工业园',
                    'area' => $area,
                    'business' => $biz,
                    'intro' => $name.'专注有机硅材料研发与供应，欢迎询价合作。',
                    'content' => "<p>{$name}成立多年，主营：{$biz}。</p><p>服务客户覆盖华南、华东，支持吨级供货与样品试用。</p>",
                    'status' => 'approved',
                    'is_featured' => $i < 6,
                    'is_certified' => true,
                    'views' => rand(200, 5000),
                ]
            );
        }

        if ($demo) {
            $sellers[] = $demo;
            Company::updateOrCreate(
                ['slug' => 'demo-silicone-co'],
                [
                    'user_id' => $demo->id,
                    'name' => $demo->company_name,
                    'category_id' => $pickCat(),
                    'logo' => 'https://placehold.co/120x120/c2410c/ffffff/png?text=DX',
                    'contact' => '沈先生',
                    'phone' => $demo->mobile,
                    'address' => '广东省东莞市虎门镇',
                    'area' => '广东东莞',
                    'business' => 'DMC、107胶、混炼胶、硅油',
                    'intro' => '专注有机硅材料供应与技术服务',
                    'content' => '<p>公司长期供应 DMC、107胶、混炼胶等产品，欢迎询价。</p>',
                    'status' => 'approved',
                    'is_featured' => true,
                    'is_certified' => true,
                    'views' => 3860,
                ]
            );
        }

        $supplyPool = [
            '羟基硅油低粘度107胶端羟基聚二甲基硅氧烷',
            '二甲基环硅氧烷混合环体DMC现货',
            '八甲基环四硅氧烷D4工业级',
            '氟硅混炼胶通用型BLD-9100',
            '氟硅混炼胶高抗撕型BLD-9200',
            '甲基苯基硅油BP560/100',
            '铂金硫化剂高活性催化剂',
            '气相白炭黑疏水型工业级',
            '真空捏合机50L不锈钢',
            '氨丙基三乙氧基硅烷偶联剂',
            '电子灌封胶双组份加成型',
            '甲基乙烯基硅橡胶生胶',
            '高含氢硅油低挥发',
            '氨基硅油柔软剂乳液',
            '液体硅橡胶LSR透明级',
            '模具硅橡胶室温硫化',
            '硅酮密封胶中性耐候',
            '乙烯基双封头剂',
            '硅胶色母黑色浓缩',
            '行星分散搅拌机实验型',
            '苯基三甲氧基硅烷',
            '羟基硅油高粘度50万',
            'D5十甲基环五硅氧烷',
            '混炼胶耐高温系列',
            '聚醚改性硅油农用助剂',
            '玻璃胶酸性透明',
            '氯丙基三甲氧基硅烷',
            '加成型灌封胶导热型',
            '三辊研磨机实验室',
            'MQ硅树脂乙烯基型',
        ];

        foreach ($supplyPool as $i => $title) {
            $seller = $sellers[$i % count($sellers)];
            Supply::updateOrCreate(
                ['user_id' => $seller->id, 'title' => $title],
                [
                    'category_id' => $pickCat(),
                    'thumb' => 'https://placehold.co/240x180/'.($i % 2 ? 'fb8500' : 'e85d04').'/ffffff/png?text=P'.($i + 1),
                    'price' => round(mt_rand(85, 4200) / 10, 2),
                    'unit' => $i % 5 === 0 ? '吨' : 'kg',
                    'brand' => $brands[$i % count($brands)],
                    'area' => $areas[$i % count($areas)],
                    'summary' => $title.'，现货供应，支持试样与吨级订单。',
                    'content' => "产品名称：{$title}\n品牌：".$brands[$i % count($brands)]."\n产地：".$areas[$i % count($areas)]."\n包装：正规工业包装\n付款：电汇/承兑（演示）\n说明：质量稳定，可提供COA。",
                    'status' => 'approved',
                    'is_urgent' => $i % 7 === 0,
                    'is_elite' => $i % 5 === 0,
                    'level' => $i % 4,
                    'views' => rand(50, 3000),
                    'refreshed_at' => now()->subHours(rand(1, 120)),
                ]
            );
        }

        $buyPool = [
            '求购DMC现货吨级长期合作', '求购107胶粘度稳定货源', '采购混炼胶样品评估',
            '急需气相白炭黑工业级', '采购铂金催化剂小包装', '求购液体硅橡胶透明级',
            '采购硅烷偶联剂KH-550', '求购真空捏合机二手/全新', '采购电子灌封胶导热型',
            '求购D4高纯度', '采购氨基硅油乳液', '求购模具硅胶软硬度可调',
            '采购密封胶中性耐候', '求购含氢硅油高含氢', '采购硅胶色母定制色号',
            '求购MQ硅树脂', '采购农用有机硅助剂', '求购反应釜不锈钢材质',
        ];
        foreach ($buyPool as $i => $title) {
            $buyer = $sellers[$i % count($sellers)];
            Purchase::updateOrCreate(
                ['user_id' => $buyer->id, 'title' => $title],
                [
                    'category_id' => $pickCat(),
                    'budget' => rand(8000, 280000),
                    'quantity' => rand(1, 50).($i % 2 ? '吨' : 'kg'),
                    'summary' => '有稳定货源优先，需质检报告。',
                    'content' => "采购需求：{$title}\n收货地：".$areas[$i % count($areas)]."\n请有货商家站内询盘或电话联系。",
                    'status' => 'approved',
                    'views' => rand(20, 900),
                    'refreshed_at' => now()->subHours(rand(1, 72)),
                ]
            );
        }

        $productPool = [
            '加成型液体硅橡胶套装1kg', '气相白炭黑工业级1kg装', '硅烷偶联剂KH550小包装',
            '铂金硫化剂实验装', '模具硅橡胶软型5kg', '导热灌封胶双组份',
            '羟基硅油107胶样品500g', '硅胶色母红色浓缩', '氨基硅油柔软剂1kg',
            '高含氢硅油样品装', '密封胶中性透明支装', '乙烯基硅油低粘度',
            '混炼胶通用型试料', '农用有机硅助剂1L', '苯基硅油小包装',
            'MQ硅树脂样品', '硅胶抑制剂实验装', '底涂剂通用型',
            '液体胶固化剂配套', '白炭黑疏水型500g', '色浆黑色液体',
            '偶联剂KH560小瓶', '硅油乳液阳离子型', '导热硅脂小支',
        ];
        foreach ($productPool as $i => $title) {
            $seller = $sellers[$i % count($sellers)];
            Product::updateOrCreate(
                ['user_id' => $seller->id, 'title' => $title],
                [
                    'category_id' => $pickCat(),
                    'thumb' => 'https://placehold.co/320x240/'.(['255d9a', 'e85d04', '2a9d8f', 'bc6c25'][$i % 4]).'/ffffff/png?text=M'.($i + 1),
                    'price' => round(29.9 + $i * 7.5, 2),
                    'stock' => rand(20, 500),
                    'unit' => '件',
                    'summary' => '商城在售 · '.$title.' · 支持模拟下单',
                    'content' => "<p>{$title}</p><p>规格齐全，支持在线下单（演示支付为模拟）。</p><p>发货地：".$areas[$i % count($areas)].'</p>',
                    'status' => 'approved',
                    'sales' => rand(5, 860),
                    'views' => rand(80, 4000),
                ]
            );
        }

        $articlePool = [
            ['9月有机硅DMC价格动态汇总', 'quote'],
            ['107胶本周报价与成交简析', 'quote'],
            ['生胶价格波动：下游观望情绪升温', 'quote'],
            ['合盛、新安装置动态跟踪', 'news'],
            ['有机硅巨头哪家强？陶氏杜邦与国内龙头对比', 'news'],
            ['液体硅橡胶应用：医疗与消费电子双轮驱动', 'news'],
            ['硅烷偶联剂选型指南（入门）', 'news'],
            ['混炼胶加工常见问题与对策', 'news'],
            ['华南有机硅仓储物流费用参考', 'news'],
            ['D4供需格局变化对中间体定价影响', 'quote'],
            ['白炭黑涨跌互现，终端按需采购', 'quote'],
            ['企业动态：德鑫有机硅扩产一期投产', 'news'],
            ['展会预告：华南橡塑与有机硅专题论坛', 'news'],
            ['安全提示：谨防虚假供应商与定金诈骗', 'news'],
            ['VIP会员如何提升供应信息排名', 'news'],
            ['甲基硅油出口询盘增多，汇率如何影响报价', 'quote'],
            ['加成型硅胶催化剂活性检测方法', 'news'],
            ['有机硅助剂在农药喷雾中的减量增效', 'news'],
            ['9月上旬密封胶原料成本跟踪', 'quote'],
            ['平台升级说明：商务中心功能一览', 'news'],
        ];
        foreach ($articlePool as $i => [$title, $type]) {
            Article::updateOrCreate(
                ['title' => $title],
                [
                    'user_id' => $admin?->id ?? $sellers[0]->id,
                    'category_id' => Category::query()->where('module', 'article')->inRandomOrder()->value('id'),
                    'thumb' => 'https://placehold.co/400x240/334155/ffffff/png?text=News'.($i + 1),
                    'author' => $type === 'quote' ? '行情编辑' : '资讯中心',
                    'summary' => mb_substr($title, 0, 28).'……本期要点速览。',
                    'content' => "<p>{$title}</p><p>本内容为客户演示假数据，仅用于展示资讯列表与详情页效果。</p><p>市场观望为主，成交按需；具体价格请联系供应商确认。</p>",
                    'type' => $type,
                    'status' => 'published',
                    'is_top' => $i < 3,
                    'views' => rand(300, 26000),
                    'published_at' => now()->subDays($i)->subHours(rand(0, 10)),
                ]
            );
        }

        $bannerTitles = [
            '福建德兴源化工 · 中间体专场',
            '合盛硅业渠道 · DMC稳定供货',
            'VIP旺铺升级 · 首页优先展示',
            '107胶现货专场 · 吨级起订',
            '设备专场 · 捏合机/反应釜促销',
        ];
        Banner::query()->where('position', 'home')->delete();
        foreach ($bannerTitles as $i => $title) {
            Banner::create([
                'title' => $title,
                'image' => 'https://placehold.co/960x320/'.(['e85d04', '255d9a', 'c2410c', '2a9d8f', 'bc6c25'][$i]).'/ffffff/png?text='.rawurlencode('Banner '.($i + 1)),
                'link' => $i % 2 ? '/mall' : '/sell',
                'position' => 'home',
                'sort' => $i + 1,
                'is_active' => true,
            ]);
        }

        foreach ([
            ['DMC', '元/吨', 28500, 31200, 29800, '华东'],
            ['D4', '元/吨', 30000, 32500, 31200, '华东'],
            ['107胶', '元/吨', 27000, 29500, 28200, '华南'],
            ['生胶', '元/吨', 22000, 24500, 23200, '华南'],
            ['甲基硅油', '元/吨', 25000, 27800, 26400, '华东'],
            ['混炼胶', '元/吨', 21000, 23800, 22400, '华南'],
            ['气相白炭黑', '元/吨', 18000, 21000, 19500, '华北'],
            ['铂金催化剂', '元/kg', 1800, 2600, 2200, '华东'],
        ] as $i => [$name, $unit, $min, $max, $avg, $market]) {
            QuoteProduct::updateOrCreate(
                ['name' => $name],
                [
                    'unit' => $unit,
                    'price_min' => $min,
                    'price_max' => $max,
                    'price_avg' => $avg,
                    'market' => $market,
                    'quoted_on' => now()->toDateString(),
                    'sort' => $i,
                    'is_active' => true,
                ]
            );
        }

        Announcement::query()->delete();
        foreach ([
            '如何让您发布的信息排名提前',
            '忘记了登录名密码该如何找回',
            '联系方式变更怎么办？',
            'VIP会员可享首页推荐位优先展示',
            '严打虚假信息，请如实填写企业资料',
        ] as $i => $title) {
            Announcement::create([
                'title' => $title,
                'content' => $title.'（演示公告）',
                'link' => '/pages/help',
                'sort' => 20 - $i,
                'is_active' => true,
            ]);
        }

        Ad::query()->where('position', 'home_right')->delete();
        foreach (['VIP旺铺推广位', '首页右侧金牌供应商', '设备专场广告'] as $i => $title) {
            Ad::create([
                'title' => $title,
                'position' => 'home_right',
                'type' => 'image',
                'image' => 'https://placehold.co/260x90/ff7a00/ffffff/png?text=AD'.($i + 1),
                'content' => $title,
                'link' => '/pages/vip',
                'sort' => $i,
                'is_active' => true,
                'price' => 3000 + $i * 800,
            ]);
        }

        if ($demo && $admin) {
            Message::query()->where('user_id', $demo->id)->delete();
            foreach ([
                ['系统审核通知', '您的企业资料已审核通过，可正常发布供应与采购。'],
                ['询盘提醒', '有买家对您的「羟基硅油107胶」发起询盘，请及时回复。'],
                ['VIP到期提醒', '您的高级VIP将于一年后到期，可在会员升级页续费（演示）。'],
                ['推广效果周报', '本周排名推广带来访问 326 次，询盘 8 条。'],
                ['安全提示', '请勿脱离平台私下转账，谨防诈骗。'],
            ] as $i => [$t, $c]) {
                Message::create([
                    'user_id' => $demo->id,
                    'from_user_id' => $admin->id,
                    'title' => $t,
                    'content' => $c,
                    'is_read' => $i > 1,
                    'created_at' => now()->subDays($i),
                    'updated_at' => now()->subDays($i),
                ]);
            }

            $peer = $sellers[0];
            $session = ChatSession::updateOrCreate(
                ['user_id' => $demo->id, 'peer_id' => $peer->id],
                ['last_message_at' => now()->subMinutes(12)]
            );
            ChatSession::updateOrCreate(
                ['user_id' => $peer->id, 'peer_id' => $demo->id],
                ['last_message_at' => now()->subMinutes(12)]
            );
            ChatMessage::query()->where('chat_session_id', $session->id)->delete();
            $chatLines = [
                [$peer->id, $demo->id, '沈总您好，看到贵司107胶供应，还有货吗？'],
                [$demo->id, $peer->id, '有的，粘度稳定，吨级可谈。'],
                [$peer->id, $demo->id, '先来 200kg 样品，价格怎么算？'],
                [$demo->id, $peer->id, '样品价优惠，我发份报价单给您。'],
                [$peer->id, $demo->id, '好的，麻烦尽快，谢谢！'],
            ];
            foreach ($chatLines as $j => [$from, $to, $text]) {
                ChatMessage::create([
                    'chat_session_id' => $session->id,
                    'from_user_id' => $from,
                    'to_user_id' => $to,
                    'content' => $text,
                    'is_read' => $j < 3,
                    'created_at' => now()->subMinutes(30 - $j * 5),
                    'updated_at' => now()->subMinutes(30 - $j * 5),
                ]);
            }

            TradeAlert::updateOrCreate(
                ['user_id' => $demo->id, 'keyword' => 'DMC'],
                ['module' => 'sell', 'area' => '广东', 'price_min' => 20000, 'price_max' => 35000, 'is_active' => true]
            );
            TradeAlert::updateOrCreate(
                ['user_id' => $demo->id, 'keyword' => '107胶'],
                ['module' => 'buy', 'area' => null, 'is_active' => true]
            );

            foreach (array_slice($sellers, 0, 5) as $friend) {
                if ($friend->id === $demo->id) {
                    continue;
                }
                Friendship::updateOrCreate(
                    ['user_id' => $demo->id, 'friend_id' => $friend->id],
                    ['status' => 'accepted']
                );
            }

            $supplies = Supply::query()->latest()->limit(5)->get();
            foreach ($supplies as $s) {
                Favorite::updateOrCreate(
                    [
                        'user_id' => $demo->id,
                        'favoritable_type' => Supply::class,
                        'favoritable_id' => $s->id,
                    ],
                    []
                );
            }

            Address::updateOrCreate(
                ['user_id' => $demo->id, 'phone' => '13800000001'],
                [
                    'name' => '沈先生',
                    'province' => '广东省',
                    'city' => '东莞市',
                    'district' => '虎门镇',
                    'address' => '连升北路188号德鑫工业园3栋',
                    'is_default' => true,
                ]
            );

            $products = Product::published()->limit(4)->get();
            CartItem::query()->where('user_id', $demo->id)->delete();
            foreach ($products as $p) {
                CartItem::create([
                    'user_id' => $demo->id,
                    'product_id' => $p->id,
                    'quantity' => rand(1, 3),
                ]);
            }

            if ($products->isNotEmpty()) {
                Order::query()->where('user_id', $demo->id)->delete();
                foreach ([0, 1, 2] as $k) {
                    $p = $products[$k % $products->count()];
                    $qty = 1 + $k;
                    $amount = round((float) $p->price * $qty, 2);
                    $order = Order::create([
                        'order_no' => Order::generateNo().$k,
                        'user_id' => $demo->id,
                        'amount' => $amount,
                        'status' => ['paid', 'shipped', 'pending'][$k],
                        'payment_method' => 'alipay_mock',
                        'receiver_name' => '沈先生',
                        'receiver_phone' => '13800000001',
                        'receiver_address' => '广东省东莞市虎门镇连升北路188号',
                        'remark' => '演示订单',
                        'paid_at' => $k < 2 ? now()->subDays(3 - $k) : null,
                    ]);
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $p->id,
                        'title' => $p->title,
                        'price' => $p->price,
                        'quantity' => $qty,
                    ]);
                }
            }

            FinanceRecord::query()->where('user_id', $demo->id)->delete();
            $bal = 12880.50;
            foreach ([
                ['charge', 10000, '账户充值（模拟）'],
                ['charge', 5000, '账户充值（模拟）'],
                ['deposit', -2000, '缴纳保证金'],
                ['credit', -500, '购买积分 5000'],
                ['spread', -800, '排名推广扣费'],
                ['order', -819.5, '商城订单支付'],
            ] as $i => [$type, $amt, $title]) {
                $bal = round($bal, 2);
                FinanceRecord::create([
                    'user_id' => $demo->id,
                    'type' => $type,
                    'amount' => $amt,
                    'balance_after' => $bal,
                    'title' => $title,
                    'remark' => '客户演示流水',
                    'created_at' => now()->subDays(10 - $i),
                    'updated_at' => now()->subDays(10 - $i),
                ]);
            }

            Inquiry::updateOrCreate(
                ['user_id' => $demo->id, 'title' => '询价：DMC现货吨级'],
                [
                    'to_user_id' => $sellers[1]->id ?? $admin->id,
                    'target_type' => 'supply',
                    'target_id' => Supply::query()->value('id'),
                    'contact_name' => '沈先生',
                    'contact_phone' => '13800000001',
                    'contact_email' => $demo->email,
                    'content' => '需要 DMC 现货 20 吨，请报价含税到东莞。',
                    'status' => 'new',
                ]
            );
            Inquiry::updateOrCreate(
                ['to_user_id' => $demo->id, 'title' => '咨询贵司107胶供应'],
                [
                    'user_id' => $sellers[2]->id ?? $admin->id,
                    'contact_name' => '采购张工',
                    'contact_phone' => '13900001111',
                    'content' => '想要 107 胶样品 50kg，粘度要求说明一下。',
                    'status' => 'processing',
                ]
            );
        }

        $this->command?->info('ClientDemoSeeder done.');
    }
}
