<?php

return [
    'alias' => [
        'cors'     => \app\middleware\Cors::class,
        'auth'     => \app\middleware\ApiAuth::class,
        'auth.optional' => \app\middleware\OptionalApiAuth::class,
        'admin'    => \app\middleware\AdminAuth::class,
    ],
    'priority' => [],
];
