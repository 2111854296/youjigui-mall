<?php

namespace app\middleware;

use app\model\PersonalAccessToken;
use app\model\User;
use Closure;
use think\Request;
use think\Response;

/**
 * Soft auth: attach user when Bearer token valid; never block the request.
 */
class OptionalApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = request_bearer_token();
        if ($plain && str_contains($plain, '|')) {
            [$id, $token] = explode('|', $plain, 2);
            $row = PersonalAccessToken::where('id', (int) $id)
                ->where('tokenable_type', 'App\\Models\\User')
                ->find();
            if ($row && hash_equals((string) $row->token, hash('sha256', $token))) {
                $user = User::find($row->tokenable_id);
                if ($user && !($user->isBanned())) {
                    $request->user = $user;
                    $request->accessToken = $row;
                }
            }
        }

        return $next($request);
    }
}
