<?php

namespace app\middleware;

use app\model\PersonalAccessToken;
use app\model\User;
use Closure;
use think\Request;
use think\Response;

class ApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = request_bearer_token();
        if (!$plain || !str_contains($plain, '|')) {
            return json_error('Unauthenticated.', 401);
        }

        [$id, $token] = explode('|', $plain, 2);
        $row = PersonalAccessToken::where('id', (int) $id)
            ->where('tokenable_type', 'App\\Models\\User')
            ->find();

        if (!$row || !hash_equals((string) $row->token, hash('sha256', $token))) {
            return json_error('Unauthenticated.', 401);
        }

        /** @var User|null $user */
        $user = User::find($row->tokenable_id);
        if (!$user) {
            return json_error('Unauthenticated.', 401);
        }

        if (method_exists($user, 'isBanned') && $user->isBanned()) {
            return json_error('账号已被禁止访问', 403);
        }

        $row->last_used_at = date('Y-m-d H:i:s');
        $row->save();

        $request->user = $user;
        $request->accessToken = $row;

        return $next($request);
    }
}
