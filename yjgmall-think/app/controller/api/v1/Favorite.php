<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\model\Favorite as FavoriteModel;
use app\model\JobPost;
use app\model\Product;
use app\model\Purchase;
use app\model\QuoteProduct;
use app\model\Resume;
use app\model\Supply;
use think\Response;

class Favorite extends BaseController
{
    private function normalizeType(?string $type): string
    {
        $type = (string) $type;
        $map = [
            'supply' => 'supply',
            'purchase' => 'purchase',
            'product' => 'product',
            'quote' => 'quote',
            'resume' => 'resume',
            'job' => 'job',
            'App\\Models\\Supply' => 'supply',
            'App\\Models\\Purchase' => 'purchase',
            'App\\Models\\Product' => 'product',
            'App\\Models\\QuoteProduct' => 'quote',
            'App\\Models\\Resume' => 'resume',
            'App\\Models\\JobPost' => 'job',
        ];
        return $map[$type] ?? strtolower($type);
    }

    private function enrich(array $row): array
    {
        $type = $this->normalizeType($row['favoritable_type'] ?? '');
        $tid = (int) ($row['favoritable_id'] ?? 0);
        $title = '';
        if ($type === 'purchase') {
            $title = (string) (Purchase::where('id', $tid)->value('title') ?: '');
        } elseif ($type === 'product') {
            $title = (string) (Product::where('id', $tid)->value('title') ?: '');
        } elseif ($type === 'quote') {
            $title = (string) (QuoteProduct::where('id', $tid)->value('name') ?: '');
        } elseif ($type === 'resume') {
            $r = Resume::find($tid);
            if ($r) {
                $name = trim((string) ($r->truename ?? ''));
                $pos = trim((string) ($r->title ?? ''));
                $mask = $name !== '' ? (mb_substr($name, 0, 1) . '**') : '求职者';
                $title = $pos !== '' ? ($mask . ' · ' . $pos) : $mask;
            }
        } elseif ($type === 'job') {
            $j = JobPost::find($tid);
            if ($j) {
                $co = trim((string) ($j->company_name ?? ''));
                $t = trim((string) ($j->title ?? ''));
                $title = $t !== '' ? ($co !== '' ? $t . ' · ' . $co : $t) : ('职位#' . $tid);
            }
        } else {
            $type = 'supply';
            $title = (string) (Supply::where('id', $tid)->value('title') ?: '');
        }
        $row['type'] = $type;
        $row['target_id'] = $tid;
        $row['title'] = $title !== '' ? $title : ('收藏#' . ($row['id'] ?? $tid));
        return $row;
    }

    public function index(): Response
    {
        $q = FavoriteModel::where('user_id', $this->request->user->id)->order('id', 'desc');
        $type = $this->normalizeType((string) $this->request->param('type', ''));
        if (in_array($type, ['supply', 'purchase', 'product', 'quote', 'resume', 'job'], true)) {
            $q->where('favoritable_type', $type);
        }
        $paginator = $q->paginate(['list_rows' => 20]);
        $result = paginate_laravel($paginator);
        $result['data'] = array_map(fn ($row) => $this->enrich(is_array($row) ? $row : $row->toArray()), $result['data'] ?? []);
        return json($result);
    }

    public function check(): Response
    {
        $type = $this->normalizeType((string) $this->request->param('type', ''));
        $id = (int) $this->request->param('id');
        if (!$type || !$id) {
            return json_error('参数错误', 422);
        }
        $row = FavoriteModel::where('user_id', $this->request->user->id)
            ->where('favoritable_type', $type)
            ->where('favoritable_id', $id)
            ->find();

        return json_data([
            'favorited' => (bool) $row,
            'id' => $row ? (int) $row->id : null,
        ]);
    }

    public function store(): Response
    {
        $type = $this->request->post('favoritable_type') ?: $this->request->post('type');
        $id = (int) ($this->request->post('favoritable_id') ?: $this->request->post('id'));
        if (!$type || !$id) {
            return json_error('参数错误', 422);
        }
        $type = $this->normalizeType($type);
        if (!in_array($type, ['supply', 'purchase', 'product', 'quote', 'resume', 'job'], true)) {
            return json_error('不支持的收藏类型', 422);
        }
        if ($type === 'quote' && !QuoteProduct::where('id', $id)->where('is_active', 1)->find()) {
            return json_error('行情品种不存在', 404);
        }
        if ($type === 'resume' && !Resume::where('id', $id)->whereIn('status', ['approved', 'published'])->find()) {
            return json_error('简历不存在或未公开', 404);
        }
        if ($type === 'job' && !JobPost::where('id', $id)->where('status', 'approved')->find()) {
            return json_error('职位不存在', 404);
        }
        $exists = FavoriteModel::where('user_id', $this->request->user->id)
            ->where('favoritable_type', $type)
            ->where('favoritable_id', $id)
            ->find();
        if ($exists) {
            return json(['data' => $this->enrich($exists->toArray()), 'message' => '已收藏'], 200);
        }
        $item = FavoriteModel::create([
            'user_id' => $this->request->user->id,
            'favoritable_type' => $type,
            'favoritable_id' => $id,
        ]);
        return json(['data' => $this->enrich($item->toArray()), 'message' => '已收藏'], 201);
    }

    public function destroy($id): Response
    {
        $item = FavoriteModel::where('user_id', $this->request->user->id)->find($id);
        if (!$item) {
            return json_error('Not found', 404);
        }
        $item->delete();
        return json_message('ok');
    }
}
