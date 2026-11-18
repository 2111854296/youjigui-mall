<?php
namespace app\controller\api\v1;

use app\BaseController;
use app\model\Company;
use app\model\Inquiry as InquiryModel;
use app\model\Product;
use app\model\Purchase;
use app\model\Supply;
use think\Response;

/**
 * 询盘：先入平台后台审核，转发后企业才可见（不再即时直达企业 / 站内聊）。
 */
class Inquiry extends BaseController
{
    /** 企业侧可见状态（new = 平台待审，不进商家询盘箱） */
    private const ENTERPRISE_VISIBLE = ['processing', 'reply_pending', 'replied', 'done', 'closed'];

    public function store(): Response
    {
        $data = $this->request->post();
        if (empty($data['title']) || empty($data['content'])) {
            return json_error('请填写标题和内容', 422);
        }
        $user = $this->request->user ?? null;
        $type = (string) ($data['target_type'] ?? $data['type'] ?? '');
        if ($type === 'sell') {
            $type = 'supply';
        }
        $targetId = (int) ($data['target_id'] ?? $data['id'] ?? $data['supply_id'] ?? $data['product_id'] ?? 0);

        // 解析意向接收企业，但先不通知；由后台「转发企业」后再推送
        $toUserId = $this->resolveToUserId($type, $targetId, $data);

        $item = InquiryModel::create([
            'user_id' => $user->id ?? null,
            'to_user_id' => $toUserId > 0 ? $toUserId : null,
            'target_type' => $type !== '' ? $type : null,
            'target_id' => $targetId > 0 ? $targetId : null,
            'title' => $data['title'],
            'content' => $data['content'],
            'contact_name' => $data['contact_name'] ?? ($user->name ?? null),
            'contact_phone' => $data['contact_phone'] ?? ($user->mobile ?? null),
            'contact_email' => $data['contact_email'] ?? ($user->email ?? null),
            'status' => 'new',
            'is_read' => 0,
        ]);

        return json([
            'data' => $item->toArray(),
            'message' => '已提交，平台审核后将转达企业',
        ], 201);
    }

    public function index(): Response
    {
        $user = $this->request->user;
        $uid = (int) $user->id;
        $vis = self::ENTERPRISE_VISIBLE;
        $placeholders = implode(',', array_fill(0, count($vis), '?'));
        $paginator = InquiryModel::whereRaw(
            "(user_id = ?) OR (to_user_id = ? AND status IN ($placeholders))",
            array_merge([$uid, $uid], $vis)
        )->order('id', 'desc')->paginate(['list_rows' => 20]);

        return json(paginate_laravel($paginator, static function ($item) use ($uid) {
            $row = $item->toArray();
            $status = (string) ($item->status ?? '');
            $isInbox = (int) $item->to_user_id === $uid
                && in_array($status, self::ENTERPRISE_VISIBLE, true);
            $row['direction'] = $isInbox ? 'inbox' : 'outbox';
            $peer = $isInbox ? (int) ($item->user_id ?: 0) : (int) ($item->to_user_id ?: 0);
            $row['peer_user_id'] = $peer > 0 && $peer !== $uid ? $peer : null;
            // 买家侧：企业回复待审时不展示回复正文
            if (!$isInbox && $status === 'reply_pending') {
                $row['reply'] = null;
                $row['replied_at'] = null;
            }
            return $row;
        }));
    }

    public function reply($id): Response
    {
        $user = $this->request->user;
        $item = InquiryModel::find($id);
        if (!$item || !$this->canAccess($item, (int) $user->id)) {
            return json_error('Forbidden', 403);
        }
        $uid = (int) $user->id;
        $status = (string) ($item->status ?? '');
        if ((int) $item->to_user_id === $uid && $status === 'new') {
            return json_error('该询盘尚在平台审核中', 403);
        }
        $reply = trim((string) $this->request->post('reply', ''));
        if ($reply === '') {
            return json_error('请填写回复内容', 422);
        }

        // 企业回复先入平台待审，审核通过后再通知询盘方
        if ((int) $item->to_user_id === $uid) {
            if (!in_array($status, ['processing', 'reply_pending', 'replied'], true)) {
                return json_error('当前状态不可回复', 422);
            }
            $item->reply = $reply;
            $item->status = 'reply_pending';
            $item->replied_at = null;
            $item->save();
            return json([
                'data' => $item->toArray(),
                'message' => '已提交，平台审核后将转达询盘方',
            ]);
        }

        // 询盘方补充说明：仍先 hold，避免绕过审核直达企业
        if ((int) $item->user_id === $uid) {
            return json_error('请等待企业回复；补充说明请重新提交询盘', 422);
        }
        return json_error('Forbidden', 403);
    }

    public function updateStatus($id): Response
    {
        $user = $this->request->user;
        $item = InquiryModel::find($id);
        if (!$item || !$this->canAccess($item, (int) $user->id)) {
            return json_error('Forbidden', 403);
        }
        if ((int) $item->to_user_id === (int) $user->id && (string) $item->status === 'new') {
            return json_error('该询盘尚在平台审核中', 403);
        }
        $item->status = $this->request->post('status', $item->status);
        $item->save();
        return json_data($item->toArray());
    }

    public function read($id): Response
    {
        $user = $this->request->user;
        $item = InquiryModel::find($id);
        if (!$item || (int) $item->to_user_id !== (int) $user->id) {
            return json_error('Forbidden', 403);
        }
        if ((string) $item->status === 'new') {
            return json_error('该询盘尚在平台审核中', 403);
        }
        if ((int) $item->is_read !== 1) {
            $item->is_read = 1;
            $item->save();
            mark_related_messages_read((int) $user->id, [(string) $item->title]);
        }
        return json_message('ok');
    }

    private function canAccess($item, int $uid): bool
    {
        if ((int) $item->user_id === $uid) {
            return true;
        }
        if ((int) $item->to_user_id === $uid && in_array((string) $item->status, self::ENTERPRISE_VISIBLE, true)) {
            return true;
        }
        return false;
    }

    private function resolveToUserId(string $type, int $targetId, array $data): int
    {
        $explicit = (int) ($data['to_user_id'] ?? 0);
        if ($explicit > 0) {
            return $explicit;
        }
        if ($targetId <= 0) {
            return 0;
        }
        try {
            if ($type === 'supply' || $type === 'sell') {
                $row = Supply::find($targetId);
                return (int) ($row->user_id ?? 0);
            }
            if ($type === 'purchase') {
                $row = Purchase::find($targetId);
                return (int) ($row->user_id ?? 0);
            }
            if ($type === 'product') {
                $row = Product::find($targetId);
                return (int) ($row->user_id ?? 0);
            }
            if ($type === 'company') {
                $row = Company::find($targetId);
                return (int) ($row->user_id ?? 0);
            }
        } catch (\Throwable $e) {
            return 0;
        }
        return 0;
    }
}
