<?php

namespace app\controller\api;

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
        $query = Ad::activeQuery()->order('sort', 'asc');
        $position = $this->request->param('position');
        if ($position !== null && $position !== '') {
            $query->where('position', $position);
        }

        $data = [];
        foreach ($query->select() as $ad) {
            $data[] = [
                'id' => (int) $ad->id,
                'title' => $ad->title,
                'position' => $ad->position,
                'type' => $ad->type,
                'image' => $ad->image,
                'link' => $ad->link,
                'content' => $ad->content,
                'price' => $ad->price !== null ? (string) $ad->price : null,
            ];
        }

        return json_data($data);
    }

    public function adPositions(): Response
    {
        $places = AdPlace::where('is_active', 1)->order('sort', 'asc')->select();
        if ($places->isEmpty()) {
            $places = [
                (object) ['code' => 'home_banner', 'name' => '首页轮播', 'size' => '720x329', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'mall_top', 'name' => '商城通栏', 'size' => '1190x80', 'type' => 'image', 'price_note' => '面议'],
                (object) ['code' => 'news_top', 'name' => '资讯顶部', 'size' => '1200x90', 'type' => 'image', 'price_note' => '面议'],
            ];
        }

        $counts = [];
        foreach (Ad::activeQuery()->field('position')->select() as $row) {
            $p = (string) $row->position;
            $counts[$p] = ($counts[$p] ?? 0) + 1;
        }

        $data = [];
        foreach ($places as $p) {
            $code = is_array($p) ? $p['code'] : $p->code;
            $data[] = [
                'code' => $code,
                'name' => is_array($p) ? $p['name'] : $p->name,
                'type' => (is_array($p) ? ($p['type'] ?? 'image') : ($p->type ?? 'image')),
                'size' => is_array($p) ? $p['size'] : $p->size,
                'price_note' => is_array($p) ? ($p['price_note'] ?? '面议') : ($p->price_note ?? '面议'),
                'active_count' => (int) ($counts[$code] ?? 0),
            ];
        }

        return json_data($data);
    }

    public function links(): Response
    {
        return json_data(
            FriendLink::activeQuery()->order('sort', 'asc')->field(['id', 'name', 'url', 'logo'])->select()->toArray()
        );
    }

    public function announcements(): Response
    {
        return json_data(
            Announcement::activeQuery()
                ->order('sort', 'desc')
                ->order('id', 'desc')
                ->limit(20)
                ->field(['id', 'title', 'content', 'link', 'created_at'])
                ->select()
                ->toArray()
        );
    }

    public function hotwords(): Response
    {
        return json_data(
            Hotword::where('is_active', 1)->order('sort', 'asc')->field(['word', 'module'])->select()->toArray()
        );
    }

    public function categories(): Response
    {
        $module = (string) $this->request->param('module', 'sell');
        $items = Category::whereNull('parent_id')
            ->where('module', $module)
            ->where('is_active', 1)
            ->with(['children'])
            ->order('sort', 'asc')
            ->select();

        $data = [];
        foreach ($items as $c) {
            $children = [];
            foreach ($c->children ?? [] as $ch) {
                $children[] = ['id' => (int) $ch->id, 'name' => $ch->name, 'slug' => $ch->slug];
            }
            $data[] = [
                'id' => (int) $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'children' => $children,
            ];
        }

        return json_data($data);
    }
}
