<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\model\ClubPost; use app\model\Exhibit; use app\model\Friendship; use app\model\Gift; use app\model\GiftOrder;
use app\model\GroupDeal; use app\model\JobPost; use app\model\KnowAnswer; use app\model\KnowQuestion;
use app\model\Photo; use app\model\Special; use app\model\Video;
use think\Response;

class ExtraModule extends BaseController
{
    private function listPublic(string $model, string $status, int $per = 16): Response
    {
        $q = $model::where('status', $status)->order('id', 'desc');
        if ($kw = $this->request->param('q')) {
            $q->whereLike('title', '%' . $kw . '%');
        }
        return json(paginate_laravel($q->paginate(['list_rows' => $per])));
    }

    private function showPublic(string $model, $id, string $status): Response
    {
        $item = $model::where('status', $status)->find($id);
        if (!$item) return json_error('Not found', 404);
        if (isset($item->views)) { $item->views = (int)$item->views + 1; $item->save(); }
        return json_data($item->toArray());
    }

    private function memberCrudList(string $model): Response
    {
        $user = $this->request->user;
        $paginator = $model::where('user_id', $user->id)->order('id', 'desc')->paginate(['list_rows' => 15]);
        return json(paginate_laravel($paginator));
    }

    private function memberStore(string $model, array $fields, string $defaultStatus = 'pending'): Response
    {
        $user = $this->request->user;
        $data = $this->request->only($fields);
        $data['user_id'] = $user->id;
        $data['status'] = $defaultStatus;
        $item = $model::create($data);
        return json(['data' => $item->toArray()], 201);
    }

    private function memberShow(string $model, $id): Response
    {
        $user = $this->request->user;
        $item = $model::where('user_id', $user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        return json_data($item->toArray());
    }

    private function memberUpdate(string $model, $id, array $fields): Response
    {
        $user = $this->request->user;
        $item = $model::where('user_id', $user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->save($this->request->only($fields));
        return json_data($item->refresh()->toArray());
    }

    private function memberDestroy(string $model, $id): Response
    {
        $user = $this->request->user;
        $item = $model::where('user_id', $user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->delete();
        return json_message('ok');
    }

    public function jobs(): Response { return $this->listPublic(JobPost::class, 'approved'); }
    public function jobShow($id): Response { return $this->showPublic(JobPost::class, $id, 'approved'); }
    public function knows(): Response {
        $q = KnowQuestion::order('id', 'desc');
        if ($kw = $this->request->param('q')) $q->whereLike('title', '%' . $kw . '%');
        return json(paginate_laravel($q->paginate(['list_rows' => 16])));
    }
    public function knowShow($id): Response {
        $item = KnowQuestion::find($id);
        if (!$item) return json_error('Not found', 404);
        $answers = KnowAnswer::where('question_id', $item->id)->order('id', 'desc')->select()->toArray();
        $data = $item->toArray();
        $data['answers'] = $answers;
        return json_data($data);
    }
    public function answerKnow($id): Response {
        $q = KnowQuestion::find($id);
        if (!$q) return json_error('Not found', 404);
        $user = $this->request->user ?? null;
        $ans = KnowAnswer::create([
            'question_id' => $id,
            'user_id' => $user->id ?? null,
            'content' => $this->request->post('content', ''),
            'status' => 'approved',
        ]);
        return json(['data' => $ans->toArray()], 201);
    }
    public function clubs(): Response { return $this->listPublic(ClubPost::class, 'approved'); }
    public function clubShow($id): Response { return $this->showPublic(ClubPost::class, $id, 'approved'); }
    public function specials(): Response { return $this->listPublic(Special::class, 'published'); }
    public function specialShow($id): Response { return $this->showPublic(Special::class, $id, 'published'); }
    public function photos(): Response { return $this->listPublic(Photo::class, 'approved'); }
    public function photoShow($id): Response { return $this->showPublic(Photo::class, $id, 'approved'); }
    public function videos(): Response { return $this->listPublic(Video::class, 'approved'); }
    public function videoShow($id): Response { return $this->showPublic(Video::class, $id, 'approved'); }
    public function groups(): Response { return $this->listPublic(GroupDeal::class, 'approved'); }
    public function groupShow($id): Response { return $this->showPublic(GroupDeal::class, $id, 'approved'); }
    public function exhibits(): Response { return $this->listPublic(Exhibit::class, 'published'); }
    public function exhibitShow($id): Response { return $this->showPublic(Exhibit::class, $id, 'published'); }
    public function gifts(): Response { return $this->listPublic(Gift::class, 'published'); }
    public function giftShow($id): Response { return $this->showPublic(Gift::class, $id, 'published'); }

    public function redeemGift($id): Response {
        $gift = Gift::where('status', 'published')->find($id);
        if (!$gift) return json_error('Not found', 404);
        $user = $this->request->user;
        $points = (int)($gift->points ?? $gift->price ?? 0);
        if ((int)$user->points < $points) return json_error('积分不足', 422);
        $user->points = (int)$user->points - $points;
        $user->save();
        $order = GiftOrder::create([
            'gift_id' => $gift->id,
            'user_id' => $user->id,
            'points' => $points,
            'status' => 'pending',
        ]);
        return json(['data' => $order->toArray(), 'message' => '兑换成功'], 201);
    }

    public function memberJobs(): Response { return $this->memberCrudList(JobPost::class); }
    public function storeJob(): Response { return $this->memberStore(JobPost::class, ['title','content','salary','area','contact','phone','company_name'], 'pending'); }
    public function memberJobShow($id): Response { return $this->memberShow(JobPost::class, $id); }
    public function updateJob($id): Response { return $this->memberUpdate(JobPost::class, $id, ['title','content','salary','area','contact','phone','company_name']); }
    public function destroyJob($id): Response { return $this->memberDestroy(JobPost::class, $id); }

    public function memberKnows(): Response { return $this->memberCrudList(KnowQuestion::class); }
    public function storeKnow(): Response { return $this->memberStore(KnowQuestion::class, ['title','content'], 'pending'); }
    public function memberKnowShow($id): Response { return $this->memberShow(KnowQuestion::class, $id); }
    public function updateKnow($id): Response { return $this->memberUpdate(KnowQuestion::class, $id, ['title','content']); }
    public function destroyKnow($id): Response { return $this->memberDestroy(KnowQuestion::class, $id); }

    public function memberClubs(): Response { return $this->memberCrudList(ClubPost::class); }
    public function storeClub(): Response { return $this->memberStore(ClubPost::class, ['title','content','thumb'], 'pending'); }
    public function memberClubShow($id): Response { return $this->memberShow(ClubPost::class, $id); }
    public function updateClub($id): Response { return $this->memberUpdate(ClubPost::class, $id, ['title','content','thumb']); }
    public function destroyClub($id): Response { return $this->memberDestroy(ClubPost::class, $id); }

    public function friends(): Response {
        $user = $this->request->user;
        $rows = Friendship::where('user_id', $user->id)->whereOr('friend_id', $user->id)->order('id', 'desc')->select();
        return json_data($rows->toArray());
    }
    public function addFriend(): Response {
        $user = $this->request->user;
        $friendId = (int)$this->request->post('friend_id', 0);
        if (!$friendId || $friendId === (int)$user->id) return json_error('无效好友', 422);
        $row = Friendship::create(['user_id' => $user->id, 'friend_id' => $friendId, 'status' => 'accepted']);
        return json(['data' => $row->toArray()], 201);
    }
}