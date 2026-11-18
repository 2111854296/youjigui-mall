<?php

namespace app\controller\api;

use app\BaseController;
use app\model\InvestProject;
use app\model\Product;
use app\model\User;
use app\model\VipGrade;
use think\facade\Config;
use think\Response;

class AdminResource extends BaseController
{
    private const HIDDEN_MENU = [
        'jobs', 'know', 'club', 'brands', 'downloads', 'exhibits', 'gifts',
        'group-deals', 'photos', 'videos', 'specials', 'invest-projects',
        'invest-leads', 'article-topics',
    ];

    public function menu(): Response
    {
        $groups = [];
        foreach (Config::get('admin_resources', []) as $key => $cfg) {
            if (($cfg['menu'] ?? true) === false || in_array($key, self::HIDDEN_MENU, true)) {
                continue;
            }
            $group = $cfg['group'] ?? '其他';
            $groups[$group][] = [
                'key' => $key,
                'label' => $cfg['label'],
                'to' => '/admin/r/'.$key,
            ];
        }
        $menu = [];
        foreach ($groups as $name => $items) {
            $menu[] = ['group' => $name, 'items' => $items];
        }

        return json_data($menu);
    }

    public function meta($resource): Response
    {
        try {
            $cfg = $this->cfg($resource);
        } catch (\Throwable $e) {
            return json_error($e->getMessage(), 404);
        }

        return json_data([
            'key' => $resource,
            'label' => $cfg['label'],
            'columns' => $cfg['columns'],
            'status' => $cfg['status'] ?? null,
            'actions' => $cfg['actions'] ?? [],
            'editable' => $cfg['editable'] ?? [],
            'create' => (bool) ($cfg['create'] ?? false),
            'fillable' => $cfg['fillable'] ?? [],
            'fields' => $cfg['fields'] ?? [],
            'default_status' => $cfg['default_status'] ?? null,
            'hint' => $cfg['hint'] ?? null,
        ]);
    }

    public function index($resource): Response
    {
        try {
            $cfg = $this->cfg($resource);
        } catch (\Throwable $e) {
            return json_error($e->getMessage(), 404);
        }
        $model = $cfg['model'];
        $q = $model::order('id', 'desc');
        if (!empty($cfg['with'])) {
            $q->with($cfg['with']);
        }

        $statusField = $cfg['status']['field'] ?? null;
        $isBool = (bool) ($cfg['status']['bool'] ?? false);
        if ($this->request->param('status') !== null && $this->request->param('status') !== '' && $statusField) {
            $val = $this->request->param('status');
            if ($isBool) {
                $q->where($statusField, filter_var($val, FILTER_VALIDATE_BOOLEAN) || $val === '1' || $val === 1 ? 1 : 0);
            } else {
                $q->where($statusField, $val);
            }
        } elseif ($this->request->param('pending_only') && !empty($cfg['default_status']) && $statusField) {
            $q->where($statusField, $cfg['default_status']);
        }

        if ($this->request->param('q') && !empty($cfg['search'])) {
            $kw = '%'.$this->request->param('q').'%';
            $cols = $cfg['search'];
            $q->where(function ($w) use ($cols, $kw) {
                foreach ($cols as $i => $col) {
                    $i === 0 ? $w->whereLike($col, $kw) : $w->whereOr($col, 'like', $kw);
                }
            });
        }

        $paginator = $q->paginate(['list_rows' => 20, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, fn ($row) => $this->serialize($row, $cfg)));
    }

    public function show($resource, $id): Response
    {
        try {
            $cfg = $this->cfg($resource);
        } catch (\Throwable $e) {
            return json_error($e->getMessage(), 404);
        }
        $modelClass = $cfg['model'];
        $q = $modelClass::where('id', $id);
        if (!empty($cfg['with'])) {
            $q->with($cfg['with']);
        }
        $item = $q->find();
        if (!$item) {
            return json_error('Not Found', 404);
        }

        return json_data($this->serialize($item, $cfg));
    }

    public function store($resource): Response
    {
        try {
            $cfg = $this->cfg($resource);
        } catch (\Throwable $e) {
            return json_error($e->getMessage(), 404);
        }
        if (empty($cfg['create'])) {
            return json_error('该资源不允许新建', 403);
        }
        $fillable = $cfg['fillable'] ?? $cfg['editable'] ?? [];
        $data = $this->normalizePayload($this->request->only($fillable), $resource, 'create');
        if ($resource === 'users' && empty($data['password'])) {
            return json_error('请设置密码', 422);
        }
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $model = $cfg['model'];
        $item = $model::create($data);

        return json(['data' => $this->serialize($item->fresh(), $cfg), 'message' => '已创建'], 201);
    }

    public function update($resource, $id): Response
    {
        try {
            $cfg = $this->cfg($resource);
        } catch (\Throwable $e) {
            return json_error($e->getMessage(), 404);
        }
        $modelClass = $cfg['model'];
        $item = $modelClass::find($id);
        if (!$item) {
            return json_error('Not Found', 404);
        }
        $editable = $cfg['editable'] ?? [];
        $src = $this->request->put() ?: $this->request->post();
        $only = [];
        foreach ($editable as $k) {
            if (array_key_exists($k, $src)) {
                $only[$k] = $src[$k];
            }
        }
        $data = $this->normalizePayload($only, $resource, 'update');

        if (!empty($src['action']) && !empty($cfg['actions'])) {
            $action = (string) $src['action'];
            if (!array_key_exists($action, $cfg['actions'])) {
                return json_error('未知操作', 422);
            }
            $field = $cfg['status']['field'] ?? 'status';
            $value = $cfg['actions'][$action];
            $data[$field] = !empty($cfg['status']['bool']) ? (bool) $value : $value;
        }

        if ($resource === 'users' && ($data['status'] ?? null) === 'active' && empty($data['group_code'])) {
            $data['group_code'] = ($item->member_type ?? '') === 'company' ? 'enterprise' : 'personal';
        }
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $item->save($data);

        if (($cfg['hook'] ?? null) === 'vip_approve' && ($data['status'] ?? null) === 'approved') {
            $this->approveVip($item);
        }

        return json_message('已更新', 200, ['data' => $this->serialize($item->fresh(), $cfg)]);
    }

    public function destroy($resource, $id): Response
    {
        try {
            $cfg = $this->cfg($resource);
        } catch (\Throwable $e) {
            return json_error($e->getMessage(), 404);
        }
        $allow = !empty($cfg['create'])
            || !empty($cfg['destroy'])
            || in_array($resource, ['pages', 'hotwords', 'friend-links', 'announcements', 'banners', 'ads', 'categories'], true);
        if (!$allow) {
            return json_error('Forbidden', 403);
        }
        $modelClass = $cfg['model'];
        $item = $modelClass::find($id);
        if (!$item) {
            return json_error('Not Found', 404);
        }
        $item->delete();

        return json_message('已删除');
    }

    public function options($relation): Response
    {
        $q = trim((string) $this->request->param('q', ''));
        $limit = min(50, max(5, (int) $this->request->param('limit', 30)));
        $map = [
            'user' => [User::class, ['name', 'username', 'company_name', 'mobile', 'email']],
            'toUser' => [User::class, ['name', 'username', 'company_name', 'mobile', 'email']],
            'product' => [Product::class, ['title']],
            'project' => [InvestProject::class, ['title']],
            'grade' => [VipGrade::class, ['name']],
        ];
        if (!isset($map[$relation])) {
            return json_error('关联不存在', 404);
        }
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
                'user', 'toUser' => trim(($row->company_name ?: $row->name ?: $row->username).' #'.$row->id),
                'product', 'project' => ($row->title ?? $row->name).' #'.$row->id,
                'grade' => ($row->name ?? '').' #'.$row->id,
                default => '#'.$row->id,
            };
            $rows[] = ['id' => (int) $row->id, 'label' => $label];
        }

        return json_data($rows);
    }

    private function approveVip($app): void
    {
        $userId = $app->user_id ?? null;
        if (!$userId) {
            return;
        }
        $user = User::find($userId);
        if (!$user) {
            return;
        }
        $user->save([
            'level' => max((int) $user->level, 2),
            'vip_expire_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'group_code' => 'vip',
            'status' => 'active',
            'is_certified' => 1,
        ]);
    }

    private function cfg(string $resource): array
    {
        $all = Config::get('admin_resources', []);
        if (!isset($all[$resource])) {
            throw new \RuntimeException('资源不存在');
        }
        $cfg = $all[$resource];
        $fields = Config::get('admin_resource_fields.'.$resource);
        if (is_array($fields) && $fields !== []) {
            $cfg['fields'] = array_values($fields);
            $editable = [];
            $fillable = [];
            foreach ($fields as $f) {
                if (!empty($f['readonly'])) {
                    continue;
                }
                $name = $f['name'];
                $editable[] = $name;
                if (empty($f['edit_only'])) {
                    $fillable[] = $name;
                }
            }
            $cfg['editable'] = array_values(array_unique(array_merge($cfg['editable'] ?? [], $editable)));
            $cfg['fillable'] = array_values(array_unique(array_merge($cfg['fillable'] ?? [], $fillable)));
        }

        return $cfg;
    }

    private function normalizePayload(array $data, string $resource, string $mode): array
    {
        if (array_key_exists('password', $data) && ($data['password'] === null || $data['password'] === '')) {
            unset($data['password']);
        }
        foreach (['is_active', 'is_top', 'is_featured', 'is_certified', 'is_admin', 'mobile_verified', 'bank_verified', 'truename_verified'] as $boolKey) {
            if (array_key_exists($boolKey, $data)) {
                $data[$boolKey] = filter_var($data[$boolKey], FILTER_VALIDATE_BOOLEAN) || $data[$boolKey] === 1 || $data[$boolKey] === '1' ? 1 : 0;
            }
        }
        foreach (['parent_id', 'category_id', 'user_id', 'to_user_id', 'product_id', 'invest_project_id', 'vip_grade_id'] as $idKey) {
            if (array_key_exists($idKey, $data) && ($data[$idKey] === '' || $data[$idKey] === null)) {
                $data[$idKey] = null;
            }
        }

        return $data;
    }

    private function serialize($row, array $cfg): array
    {
        $out = $row->toArray();
        foreach ($cfg['columns'] as $col) {
            $key = $col['key'];
            if (str_contains($key, '.')) {
                [$rel, $field] = explode('.', $key, 2);
                $out[$key] = $row->$rel->$field ?? null;
            }
        }
        $out['id'] = (int) $row->id;
        unset($out['password'], $out['pay_password'], $out['remember_token']);

        return $out;
    }
}
