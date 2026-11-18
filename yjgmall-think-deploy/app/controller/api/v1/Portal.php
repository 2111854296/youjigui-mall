<?php

namespace app\controller\api\v1;

use app\BaseController;
use app\model\Ad;
use app\model\AdPlace;
use app\model\Announcement;
use app\model\Category;
use app\model\FriendLink;
use app\model\Hotword;
use think\Response;

class Portal extends BaseController
{
    public function ads(): Response
    {
        $query = Ad::where('is_active', 1)->order('sort', 'asc');
        if ($this->request->param('position')) {
            $query->where('position', $this->request->param('position'));
        }
        $now = date('Y-m-d H:i:s');
        $rows = $query->select()->filter(function ($ad) use ($now) {
            if ($ad->starts_at && $ad->starts_at > $now) {
                return false;
            }
            if ($ad->ends_at && $ad->ends_at < $now) {
                return false;
            }

            return true;
        });

        return json_data(array_values(array_map(fn ($ad) => [
            'id' => (int) $ad->id,
            'title' => $ad->title,
            'position' => $ad->position,
            'type' => $ad->type,
            'image' => $ad->image,
            'link' => $ad->link,
            'content' => $ad->content,
            'price' => $ad->price !== null ? (string) $ad->price : null,
        ], $rows->all())));
    }

    public function adPositions(): Response
    {
        $places = AdPlace::where('is_active', 1)->order('sort', 'asc')->select();
        if ($places->isEmpty()) {
            $places = [
                (object) ['code' => 'home_banner', 'name' => '首页轮播', 'size' => '720x329', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'home_right', 'name' => '首页右侧', 'size' => '260x200', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'sell_right', 'name' => '供应右侧', 'size' => '240x400', 'type' => 'code', 'price_note' => '面议'],
                (object) ['code' => 'mall_top', 'name' => '商城通栏', 'size' => '1190x80', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'news_top', 'name' => '资讯顶部', 'size' => '1200x90', 'type' => 'image', 'price_note' => '面议'],
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
