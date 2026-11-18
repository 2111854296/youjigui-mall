<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleTopic;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Download;
use App\Models\Hotword;
use App\Models\InvestProject;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class PhaseCSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            '行业动态' => 'news-dynamic',
            '行情综述' => 'news-quote',
            '市场分析' => 'news-analysis',
            '企业动态' => 'news-company',
        ] as $name => $slug) {
            Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'module' => 'article', 'sort' => 0, 'is_active' => true, 'parent_id' => null]
            );
        }

        $catIds = Category::query()->where('module', 'article')->pluck('id');
        if ($catIds->isNotEmpty()) {
            Article::query()->whereNull('category_id')->each(function (Article $a) use ($catIds) {
                $a->update(['category_id' => $catIds->random()]);
            });
        }

        foreach ([
            ['name' => 'DMC行情专题', 'slug' => 'dmc-topic', 'summary' => 'DMC价格与供需专题'],
            ['name' => '硅橡胶专题', 'slug' => 'rubber-topic', 'summary' => '混炼胶与液体胶专题'],
        ] as $t) {
            $topic = ArticleTopic::firstOrCreate(
                ['slug' => $t['slug']],
                [...$t, 'is_active' => true, 'sort' => 0]
            );
            $ids = Article::published()->inRandomOrder()->limit(3)->pluck('id');
            $topic->articles()->syncWithoutDetaching($ids);
        }

        foreach ([
            ['name' => '瓦克化学', 'slug' => 'wacker', 'country' => '德国', 'intro' => '全球有机硅材料龙头', 'is_featured' => true],
            ['name' => '道康宁', 'slug' => 'dowcorning', 'country' => '美国', 'intro' => '有机硅解决方案', 'is_featured' => true],
            ['name' => '新安股份', 'slug' => 'xinan', 'country' => '中国', 'intro' => '国内有机硅领先企业', 'is_featured' => false],
            ['name' => '合盛硅业', 'slug' => 'hesheng', 'country' => '中国', 'intro' => '工业硅与有机硅一体化', 'is_featured' => true],
        ] as $i => $b) {
            Brand::updateOrCreate(
                ['slug' => $b['slug']],
                [...$b, 'content' => '<p>'.$b['intro'].'</p>', 'sort' => $i, 'status' => 'published']
            );
        }

        foreach ([
            ['title' => '有机硅产品手册', 'file_url' => '/files/silicone-handbook.pdf', 'file_type' => 'PDF', 'file_size' => '2.1MB', 'summary' => '产品选型手册', 'sort' => 1],
            ['title' => 'DMC质量标准', 'file_url' => '/files/dmc-spec.pdf', 'file_type' => 'PDF', 'file_size' => '0.8MB', 'summary' => 'DMC质量指标参考', 'sort' => 2],
            ['title' => '混炼胶加工指南', 'file_url' => '/files/rubber-guide.pdf', 'file_type' => 'PDF', 'file_size' => '1.5MB', 'summary' => '加工工艺说明', 'sort' => 3],
        ] as $d) {
            Download::updateOrCreate(['title' => $d['title']], [...$d, 'status' => 'published']);
        }

        InvestProject::updateOrCreate(['title' => '华南有机硅产业园招商'], [
            'area' => '广东东莞',
            'amount' => '面议',
            'summary' => '产业园标准厂房与仓储配套',
            'content' => '<p>欢迎上下游企业入驻。</p>',
            'contact_name' => '招商部',
            'contact_phone' => '0769-88886666',
            'status' => 'published',
            'sort' => 1,
        ]);
        InvestProject::updateOrCreate(['title' => '有机硅助剂生产线合作'], [
            'area' => '江苏镇江',
            'amount' => '500万起',
            'summary' => '寻求助剂产线合作伙伴',
            'content' => '<p>提供技术与渠道支持。</p>',
            'contact_name' => '项目部',
            'contact_phone' => '0511-88887777',
            'status' => 'published',
            'sort' => 2,
        ]);

        foreach ([['107胶', 'sell'], ['DMC', 'sell'], ['混炼胶', 'sell'], ['硅油', 'sell'], ['液体胶', 'mall']] as $i => $w) {
            Hotword::updateOrCreate(['word' => $w[0], 'module' => $w[1]], ['sort' => $i, 'is_active' => true]);
        }

        $user = User::query()->where('username', '2111854296')->first();
        $product = Product::published()->first();
        if ($user && $product) {
            ProductReview::updateOrCreate(
                ['product_id' => $product->id, 'user_id' => $user->id],
                ['rating' => 5, 'content' => '质量稳定，发货及时，值得复购。', 'status' => 'published']
            );
        }
    }
}
