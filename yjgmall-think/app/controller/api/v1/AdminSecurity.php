<?php

namespace app\controller\api\v1;

use app\BaseController;
use app\common\AdminRecycle;
use app\common\AdminSecondPassword;
use app\model\QuoteProductPrice;
use think\Response;

class AdminSecurity extends BaseController
{
    private function admin()
    {
        return $this->request->user ?? null;
    }

    public function status(): Response
    {
        $admin = $this->admin();
        $uid = (int) ($admin->id ?? 0);

        return json_data([
            'has_second_password' => AdminSecondPassword::isSet(),
            'verified' => AdminSecondPassword::isVerified($uid),
            'verified_until' => AdminSecondPassword::verifiedUntil($uid),
            'ttl_hours' => (int) (AdminSecondPassword::TTL / 3600),
        ]);
    }

    public function setPassword(): Response
    {
        $admin = $this->admin();
        $plain = trim((string) $this->request->param('password', ''));
        $old = trim((string) $this->request->param('old_password', ''));
        if (AdminSecondPassword::isSet()) {
            if ($old === '' || !AdminSecondPassword::check($old)) {
                return json_error('原二级密码不正确', 422);
            }
        }
        try {
            AdminSecondPassword::set($plain);
        } catch (\InvalidArgumentException $e) {
            return json_error($e->getMessage(), 422);
        }
        AdminSecondPassword::clearVerified((int) ($admin->id ?? 0));

        return json_message('二级密码已保存');
    }

    public function verify(): Response
    {
        $admin = $this->admin();
        if (!AdminSecondPassword::isSet()) {
            return json_error('尚未设置二级密码，请先到站点设置中设置', 423);
        }
        $plain = trim((string) $this->request->param('password', ''));
        if (!AdminSecondPassword::check($plain)) {
            return json_error('二级密码错误', 422);
        }
        $uid = (int) ($admin->id ?? 0);
        AdminSecondPassword::markVerified($uid);

        return json_data([
            'verified' => true,
            'verified_until' => AdminSecondPassword::verifiedUntil($uid),
            'message' => '验证成功，24 小时内删除无需再输入',
        ]);
    }

    public function recycleIndex(): Response
    {
        $page = max(1, (int) $this->request->param('page', 1));
        $q = trim((string) $this->request->param('q', ''));

        return json(AdminRecycle::list($page, 20, $q));
    }

    public function recycleRestore($id): Response
    {
        // 还原只需登录后台，不要求二级密码；删除/彻底删除才要
        try {
            AdminRecycle::restore((int) $id, function (string $resource, array $payload) {
                $this->restoreRecord($resource, $payload);
            });
        } catch (\RuntimeException $e) {
            return json_error($e->getMessage(), (int) ($e->getCode() ?: 422));
        } catch (\Throwable $e) {
            return json_error('还原失败：'.$e->getMessage(), 422);
        }

        return json_message('已还原');
    }

    public function recyclePurge($id): Response
    {
        $admin = $this->admin();
        try {
            AdminSecondPassword::assertCanDelete($admin);
        } catch (\RuntimeException $e) {
            return json_error($e->getMessage(), (int) ($e->getCode() ?: 403));
        }
        AdminRecycle::purge((int) $id);

        return json_message('已彻底删除');
    }

    private function restoreRecord(string $resource, array $payload): void
    {
        unset($payload['password'], $payload['pay_password'], $payload['remember_token']);
        foreach ($payload as $k => $v) {
            if (is_array($v)) {
                unset($payload[$k]);
            }
        }

        if ($resource === 'quote-product-prices') {
            $id = (int) ($payload['id'] ?? 0);
            if ($id > 0 && QuoteProductPrice::find($id)) {
                throw new \RuntimeException('原记录 ID 仍存在，无法按原 ID 还原', 422);
            }
            QuoteProductPrice::create($payload);

            return;
        }

        $all = include root_path().'config/admin_resources.php';
        if (empty($all[$resource]['model'])) {
            throw new \RuntimeException('未知资源，无法还原', 422);
        }
        $modelClass = $all[$resource]['model'];
        $id = (int) ($payload['id'] ?? 0);
        if ($id > 0 && $modelClass::find($id)) {
            throw new \RuntimeException('原记录 ID 仍存在，无法按原 ID 还原，请联系技术处理', 422);
        }
        $modelClass::create($payload);
    }
}
