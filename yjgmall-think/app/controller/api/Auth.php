<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Company;
use app\model\PersonalAccessToken;
use app\model\User;
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
        $required = ['member_type', 'username', 'name', 'email', 'password'];
        foreach ($required as $f) {
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
        if (($data['password_confirmation'] ?? null) !== null && $data['password'] !== $data['password_confirmation']) {
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
        ]);

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
        if ($login === '' || $password === '') {
            return json_error('请输入账号和密码', 422, ['login' => ['请输入账号和密码']]);
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

        return json([
            'data'  => $user->toApiArray(),
            'token' => $user->createToken('web'),
        ]);
    }

    public function logout(): Response
    {
        $token = $this->request->accessToken ?? null;
        if ($token) {
            $token->delete();
        }

        return json_message('ok');
    }

    public function me(): Response
    {
        return json_data($this->request->user->toApiArray());
    }
}
