<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ClubPost;
use App\Models\Exhibit;
use App\Models\Friendship;
use App\Models\Gift;
use App\Models\GiftOrder;
use App\Models\GroupDeal;
use App\Models\JobPost;
use App\Models\KnowAnswer;
use App\Models\KnowQuestion;
use App\Models\Photo;
use App\Models\Special;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExtraModuleController extends Controller
{
    public function jobs(Request $request)
    {
        $q = JobPost::query()->where('status', 'approved')->latest();
        if ($request->filled('q')) {
            $q->where('title', 'like', '%'.$request->string('q').'%');
        }
        if ($request->filled('area')) {
            $q->where('area', 'like', '%'.$request->string('area').'%');
        }

        return $this->paginate($q, fn (JobPost $i) => [
            'id' => $i->id, 'title' => $i->title, 'company_name' => $i->company_name,
            'area' => $i->area, 'salary' => $i->salary, 'education' => $i->education,
            'experience' => $i->experience, 'views' => $i->views,
            'created_at' => $i->created_at?->toDateTimeString(),
        ]);
    }

    public function jobShow(JobPost $job)
    {
        abort_unless($job->status === 'approved', 404);
        $job->increment('views');

        return response()->json(['data' => $job]);
    }

    public function knows(Request $request)
    {
        $q = KnowQuestion::query()->latest();
        if ($request->filled('q')) {
            $q->where('title', 'like', '%'.$request->string('q').'%');
        }

        return $this->paginate($q, fn (KnowQuestion $i) => [
            'id' => $i->id, 'title' => $i->title, 'reward' => $i->reward,
            'status' => $i->status, 'views' => $i->views, 'answers_count' => $i->answers_count,
            'created_at' => $i->created_at?->toDateTimeString(),
        ]);
    }

    public function knowShow(KnowQuestion $know)
    {
        $know->increment('views');
        $answers = KnowAnswer::query()->where('know_question_id', $know->id)
            ->where('status', 'published')->with('user:id,name,username')->latest()->get();

        return response()->json([
            'data' => $know,
            'answers' => $answers->map(fn ($a) => [
                'id' => $a->id, 'content' => $a->content, 'is_best' => (bool) $a->is_best,
                'user' => $a->user?->only(['id', 'name', 'username']),
                'created_at' => $a->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    public function answerKnow(Request $request, KnowQuestion $know)
    {
        $data = $request->validate(['content' => 'required|string|max:5000']);
        $ans = KnowAnswer::create([
            'know_question_id' => $know->id,
            'user_id' => $request->user()?->id,
            'content' => $data['content'],
            'status' => 'published',
        ]);
        $know->increment('answers_count');

        return response()->json(['data' => ['id' => $ans->id]], 201);
    }

    public function clubs(Request $request)
    {
        $q = ClubPost::query()->where('status', 'approved')->latest();
        if ($request->filled('circle')) {
            $q->where('circle', $request->string('circle'));
        }
        if ($request->filled('q')) {
            $q->where('title', 'like', '%'.$request->string('q').'%');
        }

        return $this->paginate($q, fn (ClubPost $i) => [
            'id' => $i->id, 'title' => $i->title, 'circle' => $i->circle,
            'views' => $i->views, 'replies' => $i->replies,
            'created_at' => $i->created_at?->toDateTimeString(),
        ]);
    }

    public function clubShow(ClubPost $club)
    {
        abort_unless($club->status === 'approved', 404);
        $club->increment('views');

        return response()->json(['data' => $club]);
    }

    public function specials()
    {
        $items = Special::query()->where('status', 'published')->orderBy('sort')->latest()->paginate(16);

        return $this->fromPaginator($items, fn (Special $i) => [
            'id' => $i->id, 'name' => $i->name, 'slug' => $i->slug, 'thumb' => $i->thumb,
            'summary' => $i->summary, 'views' => $i->views,
        ]);
    }

    public function specialShow(string $special)
    {
        $model = Special::query()->where('slug', $special)->orWhere('id', $special)->firstOrFail();
        abort_unless($model->status === 'published', 404);
        $model->increment('views');

        return response()->json(['data' => $model]);
    }

    public function photos(Request $request)
    {
        $q = Photo::query()->where('status', 'approved')->latest();
        if ($request->filled('q')) {
            $q->where('title', 'like', '%'.$request->string('q').'%');
        }

        return $this->paginate($q, fn (Photo $i) => [
            'id' => $i->id, 'title' => $i->title, 'thumb' => $i->thumb,
            'summary' => $i->summary, 'views' => $i->views,
        ]);
    }

    public function photoShow(Photo $photo)
    {
        abort_unless($photo->status === 'approved', 404);
        $photo->increment('views');

        return response()->json(['data' => $photo]);
    }

    public function videos(Request $request)
    {
        $q = Video::query()->where('status', 'approved')->latest();
        if ($request->filled('q')) {
            $q->where('title', 'like', '%'.$request->string('q').'%');
        }

        return $this->paginate($q, fn (Video $i) => [
            'id' => $i->id, 'title' => $i->title, 'thumb' => $i->thumb,
            'summary' => $i->summary, 'views' => $i->views,
        ]);
    }

    public function videoShow(Video $video)
    {
        abort_unless($video->status === 'approved', 404);
        $video->increment('views');

        return response()->json(['data' => $video]);
    }

    public function groups(Request $request)
    {
        $q = GroupDeal::query()->where('status', 'approved')->latest();
        if ($request->filled('q')) {
            $q->where('title', 'like', '%'.$request->string('q').'%');
        }

        return $this->paginate($q, fn (GroupDeal $i) => [
            'id' => $i->id, 'title' => $i->title, 'thumb' => $i->thumb,
            'price' => (string) $i->price, 'market_price' => $i->market_price !== null ? (string) $i->market_price : null,
            'stock' => $i->stock, 'sold' => $i->sold, 'ends_at' => optional($i->ends_at)?->toDateTimeString(),
            'views' => $i->views,
        ]);
    }

    public function groupShow(GroupDeal $group)
    {
        abort_unless($group->status === 'approved', 404);
        $group->increment('views');

        return response()->json(['data' => $group]);
    }

    public function exhibits(Request $request)
    {
        $q = Exhibit::query()->where('status', 'published')->latest('starts_on');
        if ($request->filled('q')) {
            $q->where('title', 'like', '%'.$request->string('q').'%');
        }

        return $this->paginate($q, fn (Exhibit $i) => [
            'id' => $i->id, 'title' => $i->title, 'area' => $i->area, 'venue' => $i->venue,
            'starts_on' => optional($i->starts_on)?->toDateString(),
            'ends_on' => optional($i->ends_on)?->toDateString(),
            'organizer' => $i->organizer, 'summary' => $i->summary, 'views' => $i->views,
        ]);
    }

    public function exhibitShow(Exhibit $exhibit)
    {
        abort_unless($exhibit->status === 'published', 404);
        $exhibit->increment('views');

        return response()->json(['data' => $exhibit]);
    }

    public function gifts()
    {
        $items = Gift::query()->where('status', 'published')->orderBy('points')->paginate(16);

        return $this->fromPaginator($items, fn (Gift $i) => [
            'id' => $i->id, 'title' => $i->title, 'thumb' => $i->thumb,
            'points' => $i->points, 'stock' => $i->stock, 'summary' => $i->summary,
        ]);
    }

    public function giftShow(Gift $gift)
    {
        abort_unless($gift->status === 'published', 404);

        return response()->json(['data' => $gift]);
    }

    public function redeemGift(Request $request, Gift $gift)
    {
        abort_unless($gift->status === 'published', 404);
        abort_if($gift->stock < 1, 422, '库存不足');
        $data = $request->validate([
            'receiver_name' => 'required|string|max:50',
            'receiver_phone' => 'required|string|max:30',
            'receiver_address' => 'required|string|max:255',
        ]);
        $user = $request->user();
        abort_if((int) $user->points < (int) $gift->points, 422, '积分不足');

        $user->points = (int) $user->points - (int) $gift->points;
        $user->save();
        $gift->decrement('stock');
        $order = GiftOrder::create([
            ...$data,
            'user_id' => $user->id,
            'gift_id' => $gift->id,
            'points' => $gift->points,
            'status' => 'pending',
        ]);

        return response()->json(['data' => ['id' => $order->id], 'message' => '兑换成功'], 201);
    }

    public function storeJob(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'company_name' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:50',
            'salary' => 'nullable|string|max:50',
            'education' => 'nullable|string|max:50',
            'experience' => 'nullable|string|max:50',
            'headcount' => 'nullable|integer|min:1',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'contact_name' => 'nullable|string|max:50',
            'contact_phone' => 'nullable|string|max:30',
        ]);
        $item = JobPost::create([...$data, 'user_id' => $request->user()->id, 'status' => 'pending']);

        return response()->json(['data' => ['id' => $item->id], 'message' => '招聘已提交，等待审核'], 201);
    }

    public function memberJobs(Request $request)
    {
        $items = JobPost::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return $this->fromPaginator($items, fn (JobPost $j) => [
            'id' => $j->id,
            'title' => $j->title,
            'company_name' => $j->company_name,
            'area' => $j->area,
            'salary' => $j->salary,
            'status' => $j->status,
            'views' => (int) $j->views,
            'updated_at' => optional($j->updated_at)?->toDateTimeString(),
        ]);
    }

    public function memberJobShow(Request $request, JobPost $job)
    {
        abort_unless((int) $job->user_id === (int) $request->user()->id, 403);

        return response()->json(['data' => $job]);
    }

    public function updateJob(Request $request, JobPost $job)
    {
        abort_unless((int) $job->user_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'company_name' => 'nullable|string|max:100',
            'area' => 'nullable|string|max:50',
            'salary' => 'nullable|string|max:50',
            'education' => 'nullable|string|max:50',
            'experience' => 'nullable|string|max:50',
            'headcount' => 'nullable|integer|min:1',
            'summary' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'contact_name' => 'nullable|string|max:50',
            'contact_phone' => 'nullable|string|max:30',
        ]);
        $job->update([...$data, 'status' => 'pending']);

        return response()->json(['data' => $job->fresh(), 'message' => '已更新，重新进入审核']);
    }

    public function destroyJob(Request $request, JobPost $job)
    {
        abort_unless((int) $job->user_id === (int) $request->user()->id, 403);
        $job->delete();

        return response()->json(['message' => 'ok']);
    }

    public function storeKnow(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'content' => 'nullable|string',
            'reward' => 'nullable|integer|min:0',
        ]);
        $item = KnowQuestion::create([...$data, 'user_id' => $request->user()->id, 'status' => 'open']);

        return response()->json(['data' => ['id' => $item->id]], 201);
    }

    public function memberKnows(Request $request)
    {
        $items = KnowQuestion::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return $this->fromPaginator($items, fn (KnowQuestion $k) => [
            'id' => $k->id,
            'title' => $k->title,
            'reward' => (int) $k->reward,
            'status' => $k->status,
            'answers_count' => (int) $k->answers_count,
            'views' => (int) $k->views,
            'updated_at' => optional($k->updated_at)?->toDateTimeString(),
        ]);
    }

    public function memberKnowShow(Request $request, KnowQuestion $know)
    {
        abort_unless((int) $know->user_id === (int) $request->user()->id, 403);

        return response()->json(['data' => $know]);
    }

    public function updateKnow(Request $request, KnowQuestion $know)
    {
        abort_unless((int) $know->user_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'content' => 'nullable|string',
            'reward' => 'nullable|integer|min:0',
        ]);
        $know->update($data);

        return response()->json(['data' => $know->fresh()]);
    }

    public function destroyKnow(Request $request, KnowQuestion $know)
    {
        abort_unless((int) $know->user_id === (int) $request->user()->id, 403);
        $know->delete();

        return response()->json(['message' => 'ok']);
    }

    public function storeClub(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'circle' => 'nullable|string|max:50',
            'content' => 'nullable|string',
        ]);
        $item = ClubPost::create([...$data, 'user_id' => $request->user()->id, 'status' => 'pending']);

        return response()->json(['data' => ['id' => $item->id], 'message' => '帖子已提交，等待审核'], 201);
    }

    public function memberClubs(Request $request)
    {
        $items = ClubPost::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return $this->fromPaginator($items, fn (ClubPost $c) => [
            'id' => $c->id,
            'title' => $c->title,
            'circle' => $c->circle,
            'status' => $c->status,
            'views' => (int) $c->views,
            'replies' => (int) $c->replies,
            'updated_at' => optional($c->updated_at)?->toDateTimeString(),
        ]);
    }

    public function memberClubShow(Request $request, ClubPost $club)
    {
        abort_unless((int) $club->user_id === (int) $request->user()->id, 403);

        return response()->json(['data' => $club]);
    }

    public function updateClub(Request $request, ClubPost $club)
    {
        abort_unless((int) $club->user_id === (int) $request->user()->id, 403);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'circle' => 'nullable|string|max:50',
            'content' => 'nullable|string',
        ]);
        $club->update([...$data, 'status' => 'pending']);

        return response()->json(['data' => $club->fresh(), 'message' => '已更新，重新进入审核']);
    }

    public function destroyClub(Request $request, ClubPost $club)
    {
        abort_unless((int) $club->user_id === (int) $request->user()->id, 403);
        $club->delete();

        return response()->json(['message' => 'ok']);
    }

    public function friends(Request $request)
    {
        $ids = Friendship::query()->where('user_id', $request->user()->id)->where('status', 'accepted')->pluck('friend_id');
        $users = \App\Models\User::query()->whereIn('id', $ids)->get(['id', 'name', 'username', 'company_name']);

        return response()->json(['data' => $users]);
    }

    public function addFriend(Request $request)
    {
        $data = $request->validate(['friend_id' => 'required|exists:users,id']);
        abort_if((int) $data['friend_id'] === (int) $request->user()->id, 422, '不能添加自己');
        Friendship::firstOrCreate([
            'user_id' => $request->user()->id,
            'friend_id' => $data['friend_id'],
        ], ['status' => 'accepted']);

        return response()->json(['message' => '已添加好友'], 201);
    }

    private function paginate($query, callable $map)
    {
        $items = $query->paginate(16);

        return $this->fromPaginator($items, $map);
    }

    private function fromPaginator($items, callable $map)
    {
        return response()->json([
            'data' => collect($items->items())->map($map)->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }
}
