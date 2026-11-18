<?php

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->header('Origin', 'http://127.0.0.1:3000');
        $allow = [
            'http://127.0.0.1:3000',
            'http://localhost:3000',
            'http://127.0.0.1:8080',
            'http://g.aifeili.cn',
            'https://g.aifeili.cn',
            'http://www.g.aifeili.cn',
            'https://www.g.aifeili.cn',
        ];
        $useOrigin = in_array($origin, $allow, true) ? $origin : ($allow[0]);

        if (strtoupper($request->method()) === 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        $response->header([
            'Access-Control-Allow-Origin'      => $useOrigin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Headers'     => 'Authorization, Content-Type, X-Requested-With, Accept, X-Quote-Ticket, X-Quote-Ts, X-Quote-Sign, X-Captcha-Token',
            'Access-Control-Allow-Methods'     => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        ]);

        return $response;
    }
}
