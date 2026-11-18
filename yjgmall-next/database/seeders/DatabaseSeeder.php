<?php

namespace Database\Seeders;

use App\Models\Ad;
use App\Models\Announcement;
use App\Models\Article;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Company;
use App\Models\FriendLink;
use App\Models\Message;
use App\Models\Page;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'name' => '管理员',
            'username' => 'admin',
            'email' => 'admin@yjgmall.test',
            'password' => Hash::make('admin123'),
            'status' => 'active',
            'is_admin' => true,
            'level' => 1,
            'points' => 100,
        ]);

        $seller = User::create([
            'name' => '沈',
            'username' => '2111854296',
            'email' => 'seller@yjgmall.test',
            'mobile' => '13800000001',
            'company_name' => '东莞示例有机硅材料有限公司',
            'password' => Hash::make('123456'),
            'status' => 'active',
            'points' => 20,
            'level' => 1,
            'vip_expire_at' => now()->addYear(),
            'is_certified' => true,
        ]);

        $cats = [
            '有机硅单体/中间体' => ['DMC', 'D4', '甲基单体', '苯基单体'],
            '硅油' => ['甲基硅油', '羟基硅油', '含氢硅油', '改性硅油'],
            '硅橡胶' => ['混炼胶', '液体硅橡胶', '模具硅橡胶'],
            '有机硅助剂/填料' => ['铂金催化剂', '白炭黑', '色母'],
            '机械设备' => ['捏合机', '反应釜'],
            '硅烷' => ['偶联剂', '交联剂'],
            '胶粘剂' => ['硅酮密封胶', '电子灌封胶'],
            '硅树脂' => ['MQ硅树脂', '甲基苯基硅树脂'],
        ];

        $i = 0;
        $firstChildId = null;
        foreach ($cats as $parent => $children) {
            $p = Category::create([
                'name' => $parent,
                'slug' => Str::slug($parent).'-'.(++$i),
                'module' => 'sell',
                'sort' => $i,
            ]);
            foreach ($children as $j => $child) {
                $c = Category::create([
                    'parent_id' => $p->id,
                    'name' => $child,
                    'slug' => Str::slug($child).'-'.$i.'-'.$j,
                    'module' => 'sell',
                    'sort' => $j,
                ]);
                $firstChildId ??= $c->id;
            }
        }

        for ($n = 1; $n <= 8; $n++) {
            Banner::create([
                'title' => "首页轮播 Banner {$n}",
                'image' => '',
                'link' => '/sell',
                'position' => 'home',
                'sort' => $n,
            ]);
        }

        Company::create([
            'user_id' => $seller->id,
            'name' => $seller->company_name,
            'slug' => 'demo-silicone-co',
            'category_id' => $firstChildId,
            'contact' => '沈先生',
            'phone' => '13800000001',
            'website' => 'https://www.yjgmall.com',
            'address' => '广东省东莞市',
            'area' => '广东东莞',
            'business' => 'DMC、107胶、混炼胶、硅油',
            'intro' => '专注有机硅材料供应与技术服务',
            'content' => "公司长期供应 DMC、107胶、混炼胶等产品。\n欢迎询价合作。",
            'status' => 'approved',
            'is_featured' => true,
            'is_certified' => true,
        ]);

        $supplyTitles = [
            '羟基硅油低粘度107胶', '二甲基环硅氧烷DMC', '氟硅混炼胶通用型',
            '甲基苯基硅油', '铂金硫化剂', '真空捏合机', '氨丙基三乙氧基硅烷', '电子灌封胶',
        ];
        foreach ($supplyTitles as $title) {
            Supply::create([
                'user_id' => $seller->id,
                'category_id' => $firstChildId,
                'title' => $title,
                'price' => rand(80, 350) / 10,
                'unit' => 'kg',
                'brand' => '示例品牌',
                'summary' => $title.'优质货源，现货供应',
                'content' => "产品名称：{$title}\n包装：正规包装\n发货地：广东",
                'status' => 'approved',
                'refreshed_at' => now()->subHours(rand(1, 48)),
            ]);
        }

        foreach (['求购DMC现货', '求购107胶吨级', '采购混炼胶样品'] as $title) {
            Purchase::create([
                'user_id' => $seller->id,
                'category_id' => $firstChildId,
                'title' => $title,
                'budget' => rand(10000, 80000),
                'quantity' => rand(1, 20).'吨',
                'summary' => '长期合作优先',
                'content' => '请有货商家联系，需提供质检报告。',
                'status' => 'approved',
                'refreshed_at' => now(),
            ]);
        }

        foreach (['加成型液体硅橡胶套装', '气相白炭黑工业级', '硅烷偶联剂小包装'] as $i => $title) {
            Product::create([
                'user_id' => $seller->id,
                'category_id' => $firstChildId,
                'title' => $title,
                'price' => 99 + $i * 50,
                'stock' => 100,
                'unit' => '件',
                'summary' => '商城在售 '.$title,
                'content' => "规格齐全，支持在线下单。\n支持支付宝担保交易（演示为模拟支付）。",
                'status' => 'approved',
            ]);
        }

        Article::create([
            'user_id' => $admin->id,
            'title' => date('n月j日').'有机硅DMC价格动态',
            'author' => '行情编辑',
            'summary' => '今日DMC报价整理',
            'content' => "今日中间体市场观望为主，成交按需。\n具体价格请联系供应商确认。",
            'type' => 'quote',
            'status' => 'published',
            'published_at' => now(),
            'is_top' => true,
        ]);
        Article::create([
            'user_id' => $admin->id,
            'title' => '有机硅行业供需简析',
            'author' => '资讯中心',
            'summary' => '上游装置与下游需求综述',
            'content' => "装置检修与终端采购节奏仍是短期主线。\n建议关注单体厂报价与物流时效。",
            'type' => 'news',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        foreach ([
            'about' => ['关于我们', "有机硅商城专注有机硅垂直电商服务。\n团队深耕行业多年，服务供需双方。"],
            'contact' => ['联系方式', "财富热线：13537887304\n邮箱：service@yjgmall.test\n地址：中国·广东"],
            'privacy' => ['隐私政策', "我们仅在业务必要范围内处理个人信息，不会出售您的数据。"],
            'terms' => ['用户协议', "注册即表示您同意遵守平台交易与信息发布规则。"],
            'help' => ['帮助中心', "常见问题：如何发布供应？如何开通旺铺？如何找回密码？"],
            'safety' => ['交易安全', "买家保障、商家认证、投诉举报等规则说明。"],
            'vip' => ['VIP服务', "VIP享有排名推广、更多发布额度与专属客服。"],
        ] as $slug => [$title, $content]) {
            Page::create(compact('slug', 'title', 'content'));
        }

        Message::create([
            'user_id' => $seller->id,
            'from_user_id' => $admin->id,
            'title' => '欢迎入驻有机硅商城',
            'content' => '请完善企业资料并发布优质供应信息。',
        ]);

        foreach ([
            ['title' => '首页右侧广告位A', 'position' => 'home_right', 'link' => '/pages/vip'],
            ['title' => '供应右侧推广', 'position' => 'sell_right', 'link' => '/register'],
            ['title' => '商城顶部横幅', 'position' => 'mall_top', 'link' => '/mall'],
            ['title' => '资讯顶部横幅', 'position' => 'news_top', 'link' => '/news'],
        ] as $i => $ad) {
            Ad::create([
                ...$ad,
                'type' => 'text',
                'content' => $ad['title'].' · 联系开通',
                'sort' => $i,
                'is_active' => true,
                'price' => 3000 + $i * 500,
            ]);
        }

        foreach (['有机硅原料网', '硅胶产业网', '化工交易平台', '东莞橡塑协会'] as $i => $name) {
            FriendLink::create([
                'name' => $name,
                'url' => 'https://www.yjgmall.com',
                'sort' => $i,
                'is_active' => true,
            ]);
        }

        foreach ([
            '平台升级公告：商务中心支持询盘与收藏',
            '严打虚假信息，请如实填写企业资料',
            'VIP会员可享首页推荐位优先展示',
        ] as $i => $title) {
            Announcement::create([
                'title' => $title,
                'content' => $title,
                'link' => '/pages/help',
                'sort' => 10 - $i,
                'is_active' => true,
            ]);
        }

        // Fix Chinese category slugs
        foreach (Category::all() as $cat) {
            $cat->update(['slug' => 'cat-'.$cat->id]);
        }
    }
}
