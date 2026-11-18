<?php

namespace app\controller\api\v1;

use app\BaseController;
use app\model\Ad;
use app\model\AdPlace;
use app\model\Announcement;
use app\model\Banner;
use app\model\Category;
use app\model\FriendLink;
use app\model\Hotword;
use think\Response;

class Portal extends BaseController
{
    public function ads(): Response
    {
        $position = (string) $this->request->param('position', '');
        $now = date('Y-m-d H:i:s');
        $query = Ad::where('is_active', 1)->order('sort', 'asc');
        if ($position !== '') {
            $query->where('position', $position);
        }
        $rows = $query->select()->filter(function ($ad) use ($now) {
            if ($ad->starts_at && $ad->starts_at > $now) {
                return false;
            }
            if ($ad->ends_at && $ad->ends_at < $now) {
                return false;
            }
            return Ad::usableImage($ad->image);
        });

        $data = array_values(array_map(fn ($ad) => Ad::toSlideArray($ad), $rows->all()));
        if (!$data && $position !== '') {
            $bannerPos = $position === 'home_banner' ? ['home', 'home_banner'] : [$position];
            $banners = Banner::where('is_active', 1)
                ->whereIn('position', $bannerPos)
                ->order('sort', 'asc')
                ->limit(8)
                ->select();
            foreach ($banners as $b) {
                if (!Ad::usableImage($b->image)) {
                    continue;
                }
                $data[] = [
                    'id' => (int) $b->id,
                    'title' => $b->title,
                    'position' => $position,
                    'type' => 'image',
                    'image' => $b->image,
                    'link' => $b->link ?: '',
                    'content' => null,
                    'price' => null,
                    'sort' => (int) $b->sort,
                ];
            }
        }

        return json_data($data);
    }

    public function adPositions(): Response
    {
        $places = AdPlace::where('is_active', 1)->order('sort', 'asc')->select();
        if ($places->isEmpty()) {
            $places = [
                (object) ['code' => 'home_banner', 'name' => '首页轮播', 'size' => '720x329', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'home_portal', 'name' => '首页分类展位', 'size' => '240x135', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'mall_top', 'name' => '商城轮播', 'size' => '965x366', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'sample_top', 'name' => '样品专区轮播', 'size' => '965x366', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'company_top', 'name' => '企业库轮播', 'size' => '960x280', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'news_top', 'name' => '资讯轮播', 'size' => '960x280', 'type' => 'image', 'price_note' => '面议'],
            ];
        }

        $counts = [];
        foreach (Ad::where('is_active', 1)->field('position, count(*) as c')->group('position')->select() as $row) {
            $counts[$row->position] = (int) $row->c;
        }

        $data = [];
        foreach ($places as $p) {
            $data[] = [
                'code' => $p->code,
                'name' => $p->name,
                'type' => $p->type ?? 'image',
                'size' => $p->size,
                'price_note' => $p->price_note ?? '面议',
                'active_count' => (int) ($counts[$p->code] ?? 0),
            ];
        }

        return json_data($data);
    }

    public function links(): Response
    {
        return json_data(FriendLink::where('is_active', 1)->order('sort', 'asc')->field('id,name,url,logo')->select()->toArray());
    }

    public function announcements(): Response
    {
        return json_data(
            Announcement::where('is_active', 1)->order('sort', 'desc')->order('id', 'desc')->limit(20)
                ->field('id,title,content,link,created_at')->select()->toArray()
        );
    }

    public function hotwords(): Response
    {
        return json_data(Hotword::where('is_active', 1)->order('sort', 'asc')->field('word,module')->select()->toArray());
    }

    public function categories(): Response
    {
        $module = $this->request->param('module', 'sell');
        $items = Category::whereNull('parent_id')->where('module', $module)->where('is_active', 1)
            ->with(['children'])->order('sort', 'asc')->select();

        $data = [];
        foreach ($items as $c) {
            $data[] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'children' => array_map(fn ($ch) => [
                    'id' => (int) $ch->id,
                    'name' => $ch->name,
                    'slug' => $ch->slug,
                ], $c->children ? $c->children->all() : []),
            ];
        }

        return json_data($data);
    }
}
