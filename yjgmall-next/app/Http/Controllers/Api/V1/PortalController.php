<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\AdPlace;
use App\Models\Announcement;
use App\Models\Category;
use App\Models\FriendLink;
use Illuminate\Http\Request;

class PortalController extends Controller
{
    public function ads(Request $request)
    {
        $query = Ad::active()->orderBy('sort');
        if ($request->filled('position')) {
            $query->where('position', $request->string('position'));
        }

        return response()->json([
            'data' => $query->get()->map(fn (Ad $ad) => [
                'id' => $ad->id,
                'title' => $ad->title,
                'position' => $ad->position,
                'type' => $ad->type,
                'image' => $ad->image,
                'link' => $ad->link,
                'content' => $ad->content,
                'price' => $ad->price !== null ? (string) $ad->price : null,
            ]),
        ]);
    }

    public function adPositions()
    {
        $places = AdPlace::query()->where('is_active', true)->orderBy('sort')->get();
        if ($places->isEmpty()) {
            $places = collect([
                ['code' => 'home_banner', 'name' => '首页轮播', 'size' => '720x329', 'type' => 'image', 'price_note' => '面议'],
                ['code' => 'home_right', 'name' => '首页右侧', 'size' => '260x200', 'type' => 'image', 'price_note' => '面议'],
                ['code' => 'sell_right', 'name' => '供应右侧', 'size' => '240x400', 'type' => 'code', 'price_note' => '面议'],
                ['code' => 'mall_top', 'name' => '商城通栏', 'size' => '1190x80', 'type' => 'image', 'price_note' => '面议'],
                ['code' => 'news_top', 'name' => '资讯顶部', 'size' => '1200x90', 'type' => 'image', 'price_note' => '面议'],
            ])->map(fn ($p) => (object) $p);
        }

        $counts = Ad::active()->selectRaw('position, count(*) as c')->groupBy('position')->pluck('c', 'position');

        return response()->json([
            'data' => $places->map(fn ($p) => [
                'code' => $p->code,
                'name' => $p->name,
                'type' => $p->type ?? 'image',
                'size' => $p->size,
                'price_note' => $p->price_note ?? '面议',
                'active_count' => (int) ($counts[$p->code] ?? 0),
            ]),
        ]);
    }

    public function links()
    {
        return response()->json([
            'data' => FriendLink::active()->orderBy('sort')->get(['id', 'name', 'url', 'logo']),
        ]);
    }

    public function announcements()
    {
        return response()->json([
            'data' => Announcement::active()->orderByDesc('sort')->orderByDesc('id')->limit(20)
                ->get(['id', 'title', 'content', 'link', 'created_at']),
        ]);
    }

    public function hotwords()
    {
        return response()->json([
            'data' => \App\Models\Hotword::query()->where('is_active', true)->orderBy('sort')->get(['word', 'module']),
        ]);
    }

    public function categories(Request $request)
    {
        $module = $request->string('module', 'sell');
        $items = Category::query()
            ->whereNull('parent_id')
            ->where('module', $module)
            ->where('is_active', true)
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort')])
            ->orderBy('sort')
            ->get();

        return response()->json([
            'data' => $items->map(fn (Category $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'children' => $c->children->map(fn ($ch) => [
                    'id' => $ch->id,
                    'name' => $ch->name,
                    'slug' => $ch->slug,
                ]),
            ]),
        ]);
    }
}
