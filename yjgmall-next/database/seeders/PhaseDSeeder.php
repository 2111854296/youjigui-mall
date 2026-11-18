<?php

namespace Database\Seeders;

use App\Models\ClubPost;
use App\Models\Exhibit;
use App\Models\Gift;
use App\Models\GroupDeal;
use App\Models\JobPost;
use App\Models\KnowQuestion;
use App\Models\Photo;
use App\Models\Special;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

class PhaseDSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('username', '2111854296')->first();
        $uid = $user?->id;

        JobPost::updateOrCreate(['title' => '有机硅销售经理'], [
            'user_id' => $uid, 'company_name' => '东莞示例有机硅材料有限公司', 'area' => '广东东莞',
            'salary' => '8k-15k', 'education' => '大专', 'experience' => '3年以上', 'headcount' => 2,
            'summary' => '负责华南市场开发', 'content' => '<p>熟悉有机硅产品优先。</p>',
            'contact_name' => '沈先生', 'contact_phone' => '13800000001', 'status' => 'approved',
        ]);
        JobPost::updateOrCreate(['title' => '质检工程师'], [
            'user_id' => $uid, 'company_name' => '东莞示例有机硅材料有限公司', 'area' => '广东东莞',
            'salary' => '6k-10k', 'education' => '本科', 'experience' => '1年以上', 'headcount' => 1,
            'summary' => '负责来料与成品检测', 'content' => '<p>熟悉GC/HPLC优先。</p>', 'status' => 'approved',
        ]);

        KnowQuestion::updateOrCreate(['title' => 'DMC存放要注意什么？'], [
            'user_id' => $uid, 'content' => '夏天仓库温度高，DMC如何安全存放？', 'reward' => 10, 'status' => 'open', 'answers_count' => 0,
        ]);
        KnowQuestion::updateOrCreate(['title' => '107胶粘度怎么选？'], [
            'user_id' => $uid, 'content' => '做模具用107胶，粘度区间如何选择？', 'reward' => 5, 'status' => 'open',
        ]);

        ClubPost::updateOrCreate(['title' => '本周DMC价格交流'], [
            'user_id' => $uid, 'circle' => '行情圈', 'content' => '<p>大家报一下到手价。</p>', 'status' => 'approved',
        ]);
        ClubPost::updateOrCreate(['title' => '混炼胶加工经验分享'], [
            'user_id' => $uid, 'circle' => '技术圈', 'content' => '<p>开炼机温控经验。</p>', 'status' => 'approved',
        ]);

        Special::updateOrCreate(['slug' => 'silicone-2026'], [
            'name' => '2026有机硅产业专题', 'summary' => '产业链全景与价格走势',
            'content' => '<p>覆盖单体、中间体、下游制品。</p>', 'status' => 'published', 'sort' => 1,
        ]);
        Special::updateOrCreate(['slug' => 'export-guide'], [
            'name' => '有机硅出口指南', 'summary' => '报关与认证要点',
            'content' => '<p>REACH、FDA等合规提示。</p>', 'status' => 'published', 'sort' => 2,
        ]);

        Photo::updateOrCreate(['title' => '工厂车间实拍'], [
            'user_id' => $uid, 'summary' => '生产线实景', 'content' => '<p>反应釜与灌装线。</p>', 'status' => 'approved',
            'images' => [],
        ]);
        Photo::updateOrCreate(['title' => '展会现场'], [
            'user_id' => $uid, 'summary' => '行业展会展台', 'status' => 'approved', 'images' => [],
        ]);

        Video::updateOrCreate(['title' => '液体硅橡胶注塑演示'], [
            'user_id' => $uid, 'summary' => 'LSR工艺短片', 'video_url' => 'https://www.w3schools.com/html/mov_bbb.mp4',
            'content' => '<p>演示注塑流程。</p>', 'status' => 'approved',
        ]);

        GroupDeal::updateOrCreate(['title' => 'DMC团购专场'], [
            'user_id' => $uid, 'price' => 12800, 'market_price' => 13500, 'stock' => 50, 'sold' => 8,
            'ends_at' => now()->addDays(10), 'summary' => '满10吨更优惠', 'content' => '<p>限时团购。</p>',
            'status' => 'approved',
        ]);
        GroupDeal::updateOrCreate(['title' => '107胶体验装团购'], [
            'user_id' => $uid, 'price' => 980, 'market_price' => 1200, 'stock' => 100, 'sold' => 20,
            'ends_at' => now()->addDays(7), 'summary' => '25kg/桶', 'status' => 'approved',
        ]);

        Exhibit::updateOrCreate(['title' => '中国国际有机硅展'], [
            'area' => '上海', 'venue' => '国家会展中心', 'starts_on' => now()->addMonths(2)->toDateString(),
            'ends_on' => now()->addMonths(2)->addDays(3)->toDateString(), 'organizer' => '展会组委会',
            'summary' => '行业年度盛会', 'content' => '<p>欢迎参观洽谈。</p>', 'status' => 'published',
        ]);
        Exhibit::updateOrCreate(['title' => '华南橡塑展'], [
            'area' => '广东东莞', 'venue' => '厚街会展中心', 'starts_on' => now()->addMonths(1)->toDateString(),
            'ends_on' => now()->addMonths(1)->addDays(2)->toDateString(), 'organizer' => '华南展会',
            'summary' => '橡塑与有机硅专区', 'status' => 'published',
        ]);

        Gift::updateOrCreate(['title' => '定制保温杯'], [
            'points' => 500, 'stock' => 30, 'summary' => '会员积分兑换', 'content' => '<p>包邮。</p>', 'status' => 'published',
        ]);
        Gift::updateOrCreate(['title' => '行业资料U盘'], [
            'points' => 200, 'stock' => 80, 'summary' => '含产品手册', 'status' => 'published',
        ]);
    }
}
