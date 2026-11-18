<?php

use think\Response;

if (!function_exists('json_data')) {
    function json_data(mixed $data = null, int $code = 200): Response
    {
        return json(['data' => $data], $code);
    }
}

if (!function_exists('json_message')) {
    function json_message(string $message, int $code = 200, array $extra = []): Response
    {
        return json(array_merge(['message' => $message], $extra), $code);
    }
}

if (!function_exists('json_error')) {
    function json_error(string $message, int $code = 400, array $errors = []): Response
    {
        $payload = ['message' => $message];
        if ($errors) {
            $payload['errors'] = $errors;
        }

        return json($payload, $code);
    }
}

if (!function_exists('paginate_laravel')) {
    /**
     * @param \think\Paginator $paginator
     */
    function paginate_laravel($paginator, ?callable $map = null): array
    {
        $items = $paginator->items();
        if ($map) {
            $items = array_map($map, $items);
        } else {
            $items = array_map(static function ($row) {
                return is_object($row) && method_exists($row, 'toArray') ? $row->toArray() : (array) $row;
            }, $items);
        }

        return [
            'data' => array_values($items),
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'last_page'    => (int) $paginator->lastPage(),
                'per_page'     => (int) $paginator->listRows(),
                'total'        => (int) $paginator->total(),
            ],
            'links' => [
                'first' => null,
                'last'  => null,
                'prev'  => $paginator->currentPage() > 1 ? $paginator->currentPage() - 1 : null,
                'next'  => $paginator->currentPage() < $paginator->lastPage() ? $paginator->currentPage() + 1 : null,
            ],
        ];
    }
}

if (!function_exists('request_bearer_token')) {
    function request_bearer_token(): ?string
    {
        $header = request()->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', trim($header), $m)) {
            return trim($m[1]);
        }

        return null;
    }
}
