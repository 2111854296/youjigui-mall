<?php

namespace App\Support;

class MediaUrl
{
    public static function make(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }
        if (str_starts_with($path, '/img/') || str_starts_with($path, '/assets/')) {
            return $path;
        }
        if (str_starts_with($path, '/storage/')) {
            return rtrim((string) config('app.url'), '/').$path;
        }

        return rtrim((string) config('app.url'), '/').'/storage/'.ltrim($path, '/');
    }
}
