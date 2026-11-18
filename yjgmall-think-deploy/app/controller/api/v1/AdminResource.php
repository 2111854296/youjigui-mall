<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\model\InvestProject; use app\model\Product; use app\model\User; use app\model\VipGrade;
use think\Response;

class AdminResource extends BaseController
{
    private const HIDDEN_MENU = [
        'jobs','know','club','brands','downloads','exhibits','gifts',
        'group-deals','photos','videos','specials','invest-projects',
        'invest-leads','article-topics',
    ];

    public function menu(): Response
    {
        $groups = [];
        foreach ($this->allCfg() as $key => $cfg) {
            if (($cfg['menu'] ?? true) === false || in_array($key, self::HIDDEN_MENU, true)) continue;
            $group = $cfg['group'] ?? '其他';
            $groups[$group][] = ['key'=>$key,'label'=>$cfg['label'],'to'=>'/admin/r/'.$key];
        }
        $menu = [];
        foreach ($groups as $name => $items) $menu[] = ['group'=>$name,'items'=>$items];
        return json_data($menu);
    }

    public function meta($resource): Response
    {
        $cfg = $this->cfg($resource);
        return json_data([
            'key'=>$resource,'label'=>$cfg['label'],'columns'=>$cfg['columns'],
            'status'=>$cfg['status']??null,'actions'=>$cfg['actions']??[],
            'editable'=>$cfg['editable']??[],'create'=>(bool)($cfg['create']??false),
            'fillable'=>$cfg['fillable']??[],'fields'=>$cfg['fields']??[],
            'default_status'=>$cfg['default_status']??null,
            'hint'=>$cfg['hint']??null,
        ]);
    }

    public function index($resource): Response
    {
        $cfg = $this->cfg($resource);
        $model = $cfg['model'];
        $q = $model::order('id','desc');
        if (!empty($cfg['with'])) {
            $with = array_map(fn($r) => explode(':', $r)[0], (array)$cfg['with']);
            $q->with($with);
        }
        $statusField = $cfg['status']['field'] ?? null;
        $isBool = (bool)($cfg['status']['bool'] ?? false);
        if ($this->request->param('status') !== null && $this->request->param('status') !== '' && $statusField) {
            $val = $this->request->param('status');
            if ($isBool) $q->where($statusField, filter_var($val, FILTER_VALIDATE_BOOLEAN) || $val === '1' || $val === 1 ? 1 : 0);
            else $q->where($statusField, $val);
        } elseif ($this->request->param('pending_only') && !empty($cfg['default_status']) && $statusField) {
            $q->where($statusField, $cfg['default_status']);
        }
        if ($kw = $this->request->param('q')) {
            $like = '%'.$kw.'%';
            $cols = $cfg['search'] ?? [];
            if ($cols) {
                $q->where(function ($w) use ($cols, $like) {
                    foreach ($cols as $i => $col) {
                        $i === 0 ? $w->whereLike($col, $like) : $w->whereOr($col, 'like', $like);
                    }
                });
            }
        }
        $paginator = $q->paginate(['list_rows' => 20]);
        $data = array_map(fn($row) => $this->serialize($row, $cfg), $paginator->items());
        return json([
            'data' => array_values($data),
            'meta' => [
                'current_page' => (int)$paginator->currentPage(),
                'last_page' => (int)$paginator->lastPage(),
                'total' => (int)$paginator->total(),
            ],
        ]);
    }

    public function show($resource, $id): Response
    {
        $cfg = $this->cfg($resource);
        $model = $cfg['model'];
        $q = $model::where('id', $id);
        if (!empty($cfg['with'])) {
            $with = array_map(fn($r) => explode(':', $r)[0], (array)$cfg['with']);
            $q->with($with);
        }
        $item = $q->find();
        if (!$item) return json_error('Not found', 404);
        return json_data($this->serialize($item, $cfg));
    }

    public function store($resource): Response
    {
        $cfg = $this->cfg($resource);
        if (empty($cfg['create'])) return json_error('该资源不允许新建', 403);
        $fillable = $cfg['fillable'] ?? $cfg['editable'] ?? [];
        $data = $this->normalizePayload($this->request->only($fillable), $resource, 'create');
        if (!empty($cfg['default_status']) && empty($data[$cfg['status']['field'] ?? 'status'])) {
            $data[$cfg['status']['field'] ?? 'status'] = $cfg['default_status'];
        }
        $model = $cfg['model'];
        $item = $model::create($data);
        return json(['data' => $this->serialize($item->refresh(), $cfg), 'message' => '已创建'], 201);
    }

    public function update($resource, $id): Response
    {
        $cfg = $this->cfg($resource);
        $model = $cfg['model'];
        $item = $model::find($id);
        if (!$item) return json_error('Not found', 404);
        $editable = $cfg['editable'] ?? [];
        $data = $this->normalizePayload($this->request->only($editable), $resource, 'update');
        if ($this->request->param('action') && !empty($cfg['actions'])) {
            $action = (string)$this->request->param('action');
            if (!array_key_exists($action, $cfg['actions'])) return json_error('未知操作', 422);
            $field = $cfg['status']['field'] ?? 'status';
            $value = $cfg['actions'][$action];
            $data[$field] = !empty($cfg['status']['bool']) ? (int)(bool)$value : $value;
        }
        if ($resource === 'users' && ($data['status'] ?? null) === 'active' && empty($data['group_code'])) {
            $data['group_code'] = (($item->member_type ?? '') === 'company') ? 'enterprise' : 'personal';
        }
        $item->save($data);
        if (($cfg['hook'] ?? null) === 'vip_approve' && ($data['status'] ?? null) === 'approved') {
            $this->approveVip($item);
        }
        return json(['data' => $this->serialize($item->refresh(), $cfg), 'message' => '已更新']);
    }

    public function destroy($resource, $id): Response
    {
        $cfg = $this->cfg($resource);
        $allow = !empty($cfg['create']) || !empty($cfg['destroy'])
            || in_array($resource, ['pages','hotwords','friend-links','announcements','banners','ads','categories'], true);
        if (!$allow) return json_error('Forbidden', 403);
        $model = $cfg['model'];
        $item = $model::find($id);
        if (!$item) return json_error('Not found', 404);
        $item->delete();
        return json_message('已删除');
    }

    public function options($relation): Response
    {
        $q = trim((string)$this->request->param('q', ''));
        $limit = min(50, max(5, (int)$this->request->param('limit', 30)));
        $map = [
            'user' => [User::class, ['name','username','company_name','mobile','email']],
            'toUser' => [User::class, ['name','username','company_name','mobile','email']],
            'product' => [Product::class, ['title']],
            'project' => [InvestProject::class, ['title']],
            'grade' => [VipGrade::class, ['name']],
        ];
        if (!isset($map[$relation])) return json_error('关联不存在', 404);
        [$model, $search] = $map[$relation];
        $query = $model::order('id', 'desc')->limit($limit);
        if ($q !== '') {
            $kw = '%'.$q.'%';
            $query->where(function ($w) use ($search, $kw) {
                foreach ($search as $i => $col) {
                    $i === 0 ? $w->whereLike($col, $kw) : $w->whereOr($col, 'like', $kw);
                }
            });
        }
        $rows = [];
        foreach ($query->select() as $row) {
            $label = match ($relation) {
                'user','toUser' => trim(($row->company_name ?: $row->name ?: $row->username).' #'.$row->id),
                'product','project' => ($row->title ?? $row->name).' #'.$row->id,
                'grade' => ($row->name ?? '').' #'.$row->id,
                default => '#'.$row->id,
            };
            $rows[] = ['id' => (int)$row->id, 'label' => $label];
        }
        return json_data($rows);
    }

    private function approveVip($app): void
    {
        $userId = $app->user_id ?? null;
        if (!$userId) return;
        $user = User::find($userId);
        if (!$user) return;
        $user->save([
            'level' => max((int)$user->level, 2),
            'vip_expire_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'group_code' => 'vip',
            'status' => 'active',
            'is_certified' => 1,
        ]);
    }

    private function allCfg(): array
    {
        $resources = include root_path().'config/admin_resources.php';
        $fields = include root_path().'config/admin_resource_fields.php';
        foreach ($resources as $key => &$cfg) {
            if (isset($fields[$key])) {
                $cfg['fields'] = array_values($fields[$key]);
            }
        }
        unset($cfg);
        return $resources;
    }

    private function cfg(string $resource): array
    {
        $all = $this->allCfg();
        if (!isset($all[$resource])) {
            throw new \think\exception\HttpException(404, '资源不存在');
        }
        return $all[$resource];
    }

    private function serialize($row, array $cfg): array
    {
        $arr = $row->toArray();
        unset($arr['password'], $arr['pay_password'], $arr['remember_token']);
        foreach ($cfg['columns'] ?? [] as $col) {
            $key = $col['key'] ?? '';
            if (str_contains($key, '.')) {
                $parts = explode('.', $key);
                $val = $arr;
                foreach ($parts as $p) {
                    $val = is_array($val) ? ($val[$p] ?? null) : null;
                }
                $arr[$key] = $val;
            }
        }
        return $arr;
    }

    private function normalizePayload(array $data, string $resource, string $mode): array
    {
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
            if (in_array($k, ['is_admin','is_certified','is_active','is_featured','is_top','is_urgent','is_elite','mobile_verified','bank_verified','truename_verified'], true)) {
                $data[$k] = filter_var($v, FILTER_VALIDATE_BOOLEAN) || $v === '1' || $v === 1 ? 1 : 0;
            }
            if ($k === 'password') {
                if (!$v) unset($data[$k]);
                else $data[$k] = password_hash((string)$v, PASSWORD_BCRYPT);
            }
        }
        if ($mode === 'create' && $resource === 'users' && empty($data['password'])) {
            throw new \think\exception\HttpException(422, '请设置密码');
        }
        return $data;
    }
}