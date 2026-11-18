<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Company;
use app\model\Inquiry;
use app\model\Order;
use app\model\Product;
use app\model\Purchase;
use app\model\Supply;
use app\model\User;
use think\Response;

class Admin extends BaseController
{
    public function dashboard(): Response
    {
        return json_data([
            'users' => (int) User::count(),
            'users_pending' => (int) User::where(function ($q) {
                $q->where('status', 'pending')->whereOr('group_code', 'pending');
            })->count(),
            'supplies' => (int) Supply::count(),
            'supplies_pending' => (int) Supply::where('status', 'pending')->count(),
            'purchases' => (int) Purchase::count(),
            'purchases_pending' => (int) Purchase::where('status', 'pending')->count(),
            'products' => (int) Product::count(),
            'orders' => (int) Order::count(),
            'companies' => (int) Company::count(),
            'inquiries' => (int) Inquiry::count(),
        ]);
    }

    public function users(): Response
    {
        $q = User::order('id', 'desc');
        if ($this->request->param('q')) {
            $kw = '%'.$this->request->param('q').'%';
            $q->where(function ($w) use ($kw) {
                $w->whereLike('username', $kw)
                    ->whereOr('name', 'like', $kw)
                    ->whereOr('email', 'like', $kw)
                    ->whereOr('mobile', 'like', $kw)
                    ->whereOr('company_name', 'like', $kw);
            });
        }
        if ($this->request->param('status')) {
            $q->where('status', $this->request->param('status'));
        }
        if ($this->request->param('member_type')) {
            $q->where('member_type', $this->request->param('member_type'));
        }
        $paginator = $q->paginate(['list_rows' => 20, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, static function ($u) {
            return [
                'id' => (int) $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'mobile' => $u->mobile,
                'company_name' => $u->company_name,
                'member_type' => $u->member_type,
                'group_code' => $u->group_code,
                'status' => $u->status,
                'is_admin' => (bool) $u->is_admin,
                'is_certified' => (bool) $u->is_certified,
                'created_at' => $u->created_at,
            ];
        }));
    }

    public function updateUser($id): Response
    {
        $user = User::find($id);
        if (!$user) {
            return json_error('Not Found', 404);
        }
        $data = $this->request->put() ?: $this->request->post();
        $allowed = ['status', 'group_code', 'member_type', 'is_certified', 'is_admin', 'level'];
        $patch = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $data)) {
                $patch[$k] = $data[$k];
            }
        }
        if (($patch['status'] ?? null) === 'active' && empty($patch['group_code'])) {
            $patch['group_code'] = (($user->member_type === 'company') || (($patch['member_type'] ?? '') === 'company'))
                ? 'enterprise' : 'personal';
        }
        $user->save($patch);

        return json_message('已更新', 200, ['data' => $user->fresh()->toApiArray()]);
    }

    public function supplies(): Response
    {
        return $this->paginateModeration(Supply::with(['user'])->order('id', 'desc'), static function ($s) {
            return [
                'id' => (int) $s->id,
                'title' => $s->title,
                'status' => $s->status,
                'price' => $s->price,
                'area' => $s->area,
                'user' => $s->user ? [
                    'id' => (int) $s->user->id,
                    'name' => $s->user->name,
                    'username' => $s->user->username,
                    'company_name' => $s->user->company_name,
                ] : null,
                'created_at' => $s->created_at,
            ];
        });
    }

    public function updateSupply($id): Response
    {
        return $this->updateStatus(Supply::find($id), ['pending', 'approved', 'rejected', 'offline']);
    }

    public function purchases(): Response
    {
        return $this->paginateModeration(Purchase::with(['user'])->order('id', 'desc'), static function ($p) {
            return [
                'id' => (int) $p->id,
                'title' => $p->title,
                'status' => $p->status,
                'area' => $p->area,
                'user' => $p->user ? [
                    'id' => (int) $p->user->id,
                    'name' => $p->user->name,
                    'username' => $p->user->username,
                    'company_name' => $p->user->company_name,
                ] : null,
                'created_at' => $p->created_at,
            ];
        });
    }

    public function updatePurchase($id): Response
    {
        return $this->updateStatus(Purchase::find($id), ['pending', 'approved', 'rejected', 'offline']);
    }

    public function products(): Response
    {
        return $this->paginateModeration(Product::with(['user'])->order('id', 'desc'), static function ($p) {
            return [
                'id' => (int) $p->id,
                'title' => $p->title,
                'status' => $p->status,
                'price' => $p->price,
                'stock' => $p->stock,
                'user' => $p->user ? [
                    'id' => (int) $p->user->id,
                    'name' => $p->user->name,
                    'username' => $p->user->username,
                    'company_name' => $p->user->company_name,
                ] : null,
                'created_at' => $p->created_at,
            ];
        });
    }

    public function updateProduct($id): Response
    {
        return $this->updateStatus(Product::find($id), ['pending', 'approved', 'rejected', 'offline', 'published']);
    }

    public function orders(): Response
    {
        $q = Order::with(['user', 'items'])->order('id', 'desc');
        if ($this->request->param('status')) {
            $q->where('status', $this->request->param('status'));
        }
        $paginator = $q->paginate(['list_rows' => 20, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, static function ($o) {
            return [
                'id' => (int) $o->id,
                'order_no' => $o->order_no ?? ('#'.$o->id),
                'status' => $o->status,
                'total_amount' => $o->amount,
                'user' => $o->user ? [
                    'id' => (int) $o->user->id,
                    'name' => $o->user->name,
                    'username' => $o->user->username,
                ] : null,
                'created_at' => $o->created_at,
            ];
        }));
    }

    public function updateOrder($id): Response
    {
        $order = Order::find($id);
        if (!$order) {
            return json_error('Not Found', 404);
        }
        $status = (string) ($this->request->put('status') ?: $this->request->post('status', ''));
        if ($status === '') {
            return json_error('status 必填', 422);
        }
        $order->status = $status;
        $order->save();

        return json_message('已更新', 200, ['data' => ['id' => (int) $order->id, 'status' => $order->status]]);
    }

    private function paginateModeration($query, callable $map): Response
    {
        if ($this->request->param('status')) {
            $query->where('status', $this->request->param('status'));
        }
        if ($this->request->param('q')) {
            $query->whereLike('title', '%'.$this->request->param('q').'%');
        }
        $paginator = $query->paginate(['list_rows' => 20, 'page' => max(1, (int) $this->request->param('page/d', 1))]);

        return json(paginate_laravel($paginator, $map));
    }

    private function updateStatus($model, array $allowed): Response
    {
        if (!$model) {
            return json_error('Not Found', 404);
        }
        $status = (string) ($this->request->put('status') ?: $this->request->post('status', ''));
        if (!in_array($status, $allowed, true)) {
            return json_error('status 无效', 422);
        }
        $model->status = $status;
        $model->save();

        return json_message('已更新', 200, ['data' => ['id' => (int) $model->id, 'status' => $model->status]]);
    }
}
