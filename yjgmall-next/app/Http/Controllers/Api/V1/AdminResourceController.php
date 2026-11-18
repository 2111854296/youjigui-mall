<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InvestProject;
use App\Models\Product;
use App\Models\User;
use App\Models\VipGrade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class AdminResourceController extends Controller
{
    /** 源码扩展模块：默认不进侧栏（接口仍可用） */
    private const HIDDEN_MENU = [
        'jobs', 'know', 'club', 'brands', 'downloads', 'exhibits', 'gifts',
        'group-deals', 'photos', 'videos', 'specials', 'invest-projects',
        'invest-leads', 'article-topics',
    ];

    public function menu()
    {
        $groups = [];
        foreach (config('admin_resources') as $key => $cfg) {
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

        return response()->json(['data' => $menu]);
    }

    public function meta(string $resource)
    {
        $cfg = $this->cfg($resource);

        return response()->json([
            'data' => [
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
            ],
        ]);
    }

    public function index(Request $request, string $resource)
    {
        $cfg = $this->cfg($resource);
        /** @var Model $model */
        $model = $cfg['model'];
        $q = $model::query()->latest('id');

        if (! empty($cfg['with'])) {
            $q->with($cfg['with']);
        }

        $statusField = $cfg['status']['field'] ?? null;
        $isBool = (bool) ($cfg['status']['bool'] ?? false);

        if ($request->filled('status') && $statusField) {
            $val = $request->input('status');
            if ($isBool) {
                $q->where($statusField, filter_var($val, FILTER_VALIDATE_BOOLEAN) || $val === '1' || $val === 1);
            } else {
                $q->where($statusField, $val);
            }
        } elseif ($request->boolean('pending_only') && ! empty($cfg['default_status']) && $statusField) {
            $q->where($statusField, $cfg['default_status']);
        }

        if ($request->filled('q') && ! empty($cfg['search'])) {
            $kw = '%'.$request->string('q').'%';
            $q->where(function ($w) use ($cfg, $kw) {
                foreach ($cfg['search'] as $i => $col) {
                    $i === 0 ? $w->where($col, 'like', $kw) : $w->orWhere($col, 'like', $kw);
                }
            });
        }

        $items = $q->paginate(20);

        return response()->json([
            'data' => collect($items->items())->map(fn ($row) => $this->serialize($row, $cfg))->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(string $resource, int $id)
    {
        $cfg = $this->cfg($resource);
        /** @var Model $modelClass */
        $modelClass = $cfg['model'];
        $q = $modelClass::query();
        if (! empty($cfg['with'])) {
            $q->with($cfg['with']);
        }
        $item = $q->findOrFail($id);

        return response()->json(['data' => $this->serialize($item, $cfg)]);
    }

    public function store(Request $request, string $resource)
    {
        $cfg = $this->cfg($resource);
        abort_unless(! empty($cfg['create']), 403, '该资源不允许新建');
        $fillable = $cfg['fillable'] ?? $cfg['editable'] ?? [];
        $data = $this->normalizePayload($request->only($fillable), $resource, 'create');
        /** @var Model $model */
        $model = $cfg['model'];
        $item = $model::create($data);

        return response()->json(['data' => $this->serialize($item->fresh(), $cfg), 'message' => '已创建'], 201);
    }

    public function update(Request $request, string $resource, int $id)
    {
        $cfg = $this->cfg($resource);
        /** @var Model $modelClass */
        $modelClass = $cfg['model'];
        $item = $modelClass::query()->findOrFail($id);

        $editable = $cfg['editable'] ?? [];
        $data = $this->normalizePayload($request->only($editable), $resource, 'update');

        if ($request->filled('action') && ! empty($cfg['actions'])) {
            $action = $request->string('action')->toString();
            abort_unless(array_key_exists($action, $cfg['actions']), 422, '未知操作');
            $field = $cfg['status']['field'] ?? 'status';
            $value = $cfg['actions'][$action];
            if (! empty($cfg['status']['bool'])) {
                $data[$field] = (bool) $value;
            } else {
                $data[$field] = $value;
            }
        }

        if ($resource === 'users' && ($data['status'] ?? null) === 'active' && empty($data['group_code'])) {
            $data['group_code'] = ($item->member_type ?? '') === 'company' ? 'enterprise' : 'personal';
        }

        $item->update($data);

        if (($cfg['hook'] ?? null) === 'vip_approve' && ($data['status'] ?? null) === 'approved') {
            $this->approveVip($item);
        }

        return response()->json(['data' => $this->serialize($item->fresh(), $cfg), 'message' => '已更新']);
    }

    public function destroy(string $resource, int $id)
    {
        $cfg = $this->cfg($resource);
        $allow = ! empty($cfg['create'])
            || ! empty($cfg['destroy'])
            || in_array($resource, ['pages', 'hotwords', 'friend-links', 'announcements', 'banners', 'ads', 'categories'], true);
        abort_unless($allow, 403);
        /** @var Model $modelClass */
        $modelClass = $cfg['model'];
        $item = $modelClass::query()->findOrFail($id);
        $item->delete();

        return response()->json(['message' => '已删除']);
    }

    public function options(Request $request, string $relation)
    {
        $q = trim((string) $request->input('q', ''));
        $limit = min(50, max(5, (int) $request->input('limit', 30)));

        $map = [
            'user' => [User::class, ['name', 'username', 'company_name', 'mobile', 'email']],
            'toUser' => [User::class, ['name', 'username', 'company_name', 'mobile', 'email']],
            'product' => [Product::class, ['title']],
            'project' => [InvestProject::class, ['title']],
            'grade' => [VipGrade::class, ['name']],
        ];

        abort_unless(isset($map[$relation]), 404, '关联不存在');
        [$model, $search] = $map[$relation];
        $query = $model::query()->latest('id')->limit($limit);
        if ($q !== '') {
            $kw = '%'.$q.'%';
            $query->where(function ($w) use ($search, $kw) {
                foreach ($search as $i => $col) {
                    $i === 0 ? $w->where($col, 'like', $kw) : $w->orWhere($col, 'like', $kw);
                }
            });
        }

        $rows = $query->get()->map(function ($row) use ($relation) {
            $label = match ($relation) {
                'user', 'toUser' => trim(($row->company_name ?: $row->name ?: $row->username).' #'.$row->id),
                'product', 'project' => ($row->title ?? $row->name).' #'.$row->id,
                'grade' => ($row->name ?? '').' #'.$row->id,
                default => '#'.$row->id,
            };

            return ['id' => $row->id, 'label' => $label];
        });

        return response()->json(['data' => $rows]);
    }

    private function approveVip(Model $app): void
    {
        $userId = $app->getAttribute('user_id');
        if (! $userId) {
            return;
        }
        $user = User::query()->find($userId);
        if (! $user) {
            return;
        }
        $user->update([
            'level' => max((int) $user->level, 2),
            'vip_expire_at' => Carbon::now()->addYear(),
            'group_code' => 'vip',
            'status' => 'active',
            'is_certified' => true,
        ]);
    }

    private function cfg(string $resource): array
    {
        $all = config('admin_resources');
        abort_unless(isset($all[$resource]), 404, '资源不存在');
        $cfg = $all[$resource];
        $fields = config('admin_resource_fields.'.$resource);
        if (is_array($fields) && $fields !== []) {
            $cfg['fields'] = array_values($fields);
            $editable = [];
            $fillable = [];
            foreach ($fields as $f) {
                if (! empty($f['readonly'])) {
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
        if (array_key_exists('password', $data)) {
            if ($data['password'] === null || $data['password'] === '') {
                unset($data['password']);
            }
        }

        foreach (['is_active', 'is_top', 'is_featured', 'is_certified', 'is_admin', 'mobile_verified', 'bank_verified', 'truename_verified'] as $boolKey) {
            if (array_key_exists($boolKey, $data)) {
                $data[$boolKey] = filter_var($data[$boolKey], FILTER_VALIDATE_BOOLEAN) || $data[$boolKey] === 1 || $data[$boolKey] === '1';
            }
        }

        foreach (['parent_id', 'category_id', 'user_id', 'to_user_id', 'product_id', 'invest_project_id', 'vip_grade_id'] as $idKey) {
            if (array_key_exists($idKey, $data) && ($data[$idKey] === '' || $data[$idKey] === null)) {
                $data[$idKey] = null;
            }
        }

        if ($mode === 'create' && $resource === 'users' && empty($data['password'])) {
            abort(422, '请设置密码');
        }

        return $data;
    }

    private function serialize(Model $row, array $cfg): array
    {
        $out = $row->toArray();
        foreach ($cfg['columns'] as $col) {
            $key = $col['key'];
            if (str_contains($key, '.')) {
                Arr::set($out, $key, data_get($row, $key));
            }
        }
        $out['id'] = $row->getKey();
        // never leak password hash to admin form
        unset($out['password'], $out['pay_password'], $out['remember_token']);

        return $out;
    }
}
