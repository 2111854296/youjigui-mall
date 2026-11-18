<?php

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user ?? null;
        if (!$user || !(int) ($user->is_admin ?? 0)) {
            return json_error('Forbidden.', 403);
        }

        return $next($request);
    }
}
