<?php

namespace app\controller\api\v1;

use app\BaseController;
use app\model\Company;
use app\model\PersonalAccessToken;
use app\model\User;
use app\model\VerificationCode;
use think\Response;

class Auth extends BaseController
{
    public function register(): Response
    {
        $data = $this->request->post();
        if (!$data) {
            $raw = $this->request->getContent();
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
        }
        foreach (['mobile', 'qq', 'area', 'company_name', 'company_type', 'gender', 'invite_code', 'invite'] as $f) {
            if (!isset($data[$f])) {
                continue;
            }
            $data[$f] = trim((string) $data[$f]);
            if ($data[$f] === '') {
                $data[$f] = null;
            }
        }
        foreach (['member_type', 'username', 'name', 'email', 'password'] as $f) {
            if (empty($data[$f])) {
                return json_error("字段 {$f} 必填", 422, [$f => ["{$f} 必填"]]);
            }
        }
        if (!in_array($data['member_type'], ['personal', 'company'], true)) {
            return json_error('member_type 无效', 422);
        }
        if (User::where('username', $data['username'])->find()) {
            return json_error('用户名已存在', 422, ['username' => ['用户名已存在']]);
        }
        if (User::where('email', $data['email'])->find()) {
            return json_error('邮箱已存在', 422, ['email' => ['邮箱已存在']]);
        }
        if (!empty($data['mobile']) && User::where('mobile', $data['mobile'])->find()) {
            return json_error('手机号已存在', 422, ['mobile' => ['手机号已存在']]);
        }
        if (isset($data['password_confirmation']) && $data['password'] !== $data['password_confirmation']) {
            return json_error('两次密码不一致', 422, ['password' => ['两次密码不一致']]);
        }

        $user = User::create([
            'member_type'   => $data['member_type'],
            'username'      => $data['username'],
            'name'          => $data['name'],
            'gender'        => $data['gender'] ?? null,
            'email'         => $data['email'],
            'mobile'        => $data['mobile'] ?? null,
            'qq'            => $data['qq'] ?? null,
            'area'          => $data['area'] ?? null,
            'company_name'  => $data['company_name'] ?? null,
            'company_type'  => $data['company_type'] ?? null,
            'password'      => password_hash($data['password'], PASSWORD_BCRYPT),
            'status'        => 'pending',
            'group_code'    => 'pending',
            'points'        => 20,
            'level'         => 0,
            'online_status' => 'online',
            'sound'         => 1,
            'invite_code'   => strtoupper(substr(bin2hex(random_bytes(4)), 0, 8)),
        ]);

        $inviteCode = strtoupper(trim((string) ($data['invite_code'] ?? $data['invite'] ?? '')));
        if ($inviteCode !== '') {
            $inviter = User::where('invite_code', $inviteCode)->find();
            if ($inviter && (int) $inviter->id !== (int) $user->id) {
                $user->save(['invited_by_user_id' => $inviter->id]);
                \app\common\VipEntitlement::rewardInviteRegister($inviter, $user);
            }
        }

        if ($data['member_type'] === 'company' && !empty($data['company_name'])) {
            Company::create([
                'user_id' => $user->id,
                'name'    => $data['company_name'],
                'slug'    => company_slug_from_name((string) $data['company_name'], (int) $user->id),
                'contact' => $data['name'],
                'phone'   => $data['mobile'] ?? null,
                'area'    => $data['area'] ?? null,
                'status'  => 'pending',
            ]);
        }

        return json(['data' => $user->toApiArray(), 'token' => $user->createToken('web')], 201);
    }

    public function login(): Response
    {
        $payload = $this->request->post();
        if (!$payload) {
            $raw = $this->request->getContent();
            if ($raw) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $payload = $decoded;
                }
            }
        }
        $login = (string) ($payload['login'] ?? '');
        $password = (string) ($payload['password'] ?? '');
        $captchaToken = (string) ($payload['captcha_token'] ?? '');
        if ($login === '' || $password === '') {
            return json_error('请输入账号和密码', 422, ['login' => ['请输入账号和密码']]);
        }
        if (!\app\common\SlideCaptcha::consumeToken($captchaToken)) {
            return json_error('请先完成滑块验证', 422, ['captcha' => ['请先完成滑块验证']]);
        }

        $user = User::where('username', $login)->find()
            ?: User::where('email', $login)->find()
            ?: User::where('mobile', $login)->find()
            ?: User::where('company_name', $login)->find();

        if (!$user || !password_verify($password, (string) $user->password)) {
            return json_error('用户名或密码错误', 422, ['login' => ['用户名或密码错误']]);
        }
        if ($user->isBanned()) {
            return json_error('账号已被禁止访问', 422, ['login' => ['账号已被禁止访问']]);
        }

        if (($user->online_status ?? '') !== 'invisible') {
            $user->online_status = 'online';
            $user->save();
        }

        return json(['data' => $user->toApiArray(), 'token' => $user->createToken('web')]);
    }

    public function logout(): Response
    {
        $token = $this->request->accessToken ?? null;
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return json_message('ok');
    }

    public function me(): Response
    {
        return json_data($this->request->user->toApiArray());
    }

    /** 发送找回密码验证码（无短信网关时演示环境返回 code） */
    public function sendResetCode(): Response
    {
        $account = trim((string) ($this->request->post('account') ?? $this->request->post('login') ?? ''));
        if ($account === '') {
            return json_error('请填写用户名 / 手机号 / 邮箱', 422);
        }

        $user = User::where(function ($q) use ($account) {
            $q->where('username', $account)->whereOr('email', $account)->whereOr('mobile', $account);
        })->find();
        if (!$user) {
            // 防枚举：统一提示
            return json(['message' => '若账号存在，验证码已发送', 'data' => ['sent' => true]]);
        }

        $channel = $this->resetChannel();
        if (\app\common\SmsCode::gatewayOn()) {
            $mobile = trim((string) $user->mobile);
            if (!preg_match('/^1\d{10}$/', $mobile) && preg_match('/^1\d{10}$/', $account)) {
                $mobile = $account;
            }
            if (!preg_match('/^1\d{10}$/', $mobile)) {
                return json_error('该账号没有可接收短信的手机号，请联系客服重置', 422);
            }
            $sent = \app\common\SmsCode::sendReset($account, $mobile, (int) $user->id, $channel);
            if (empty($sent['ok'])) {
                return json_error($sent['message'] ?? '发送失败', 422);
            }

            return json(['message' => $sent['message'], 'data' => ['sent' => true, 'expires_in' => 900]]);
        }

        // 未开短信网关时禁止找回密码回传验证码（否则等于公开重置任意账号）
        return json_error('短信服务未开通，请联系客服重置密码', 422);
    }

    public function resetPassword(): Response
    {
        $account = trim((string) ($this->request->post('account') ?? $this->request->post('login') ?? ''));
        $code = trim((string) ($this->request->post('code') ?? ''));
        $password = (string) ($this->request->post('password') ?? '');
        $confirm = $this->request->post('password_confirmation');

        if ($account === '' || $code === '' || $password === '') {
            return json_error('请填写账号、验证码和新密码', 422);
        }
        if ($confirm !== null && $confirm !== $password) {
            return json_error('两次密码不一致', 422);
        }
        if (strlen($password) < 6) {
            return json_error('密码至少 6 位', 422);
        }

        $channel = $this->resetChannel();
        $row = VerificationCode::where('channel', $channel)
            ->where('account', $account)
            ->whereNull('used_at')
            ->order('id', 'desc')
            ->find();
        if (!$row || strtotime((string) $row->expires_at) < time()) {
            return json_error('验证码无效或已过期', 422);
        }
        if (!password_verify($code, (string) $row->code_hash)) {
            return json_error('验证码错误', 422);
        }

        $user = User::find($row->user_id);
        if (!$user) {
            $user = User::where(function ($q) use ($account) {
                $q->where('username', $account)->whereOr('email', $account)->whereOr('mobile', $account);
            })->find();
        }
        if (!$user) {
            return json_error('账号不存在', 404);
        }

        if ($channel === 'reset_pay') {
            $user->pay_password = password_hash($password, PASSWORD_BCRYPT);
        } else {
            $user->password = password_hash($password, PASSWORD_BCRYPT);
        }
        $user->save();
        $row->used_at = date('Y-m-d H:i:s');
        $row->save();

        return json(['message' => $channel === 'reset_pay' ? '支付密码已重置' : '登录密码已重置，请使用新密码登录']);
    }

    private function resetChannel(): string
    {
        return (string) $this->request->post('target', 'login') === 'pay' ? 'reset_pay' : 'reset';
    }
}
