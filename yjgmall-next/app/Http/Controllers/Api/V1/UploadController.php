<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp|max:5120',
        ]);

        $file = $request->file('file');
        $dir = 'uploads/'.date('Y/m');
        $name = Str::lower(Str::random(16)).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($dir, $name, 'public');

        $url = Storage::disk('public')->url($path);
        // 保证绝对地址，前端/局域网都能引用当前 API 主机
        if (! str_starts_with($url, 'http')) {
            $url = rtrim($request->getSchemeAndHttpHost(), '/').'/'.ltrim($url, '/');
        }

        return response()->json([
            'data' => [
                'url' => $url,
                'path' => $path,
            ],
        ], 201);
    }
}
