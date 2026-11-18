<?php
namespace app\controller\api\v1;

use app\BaseController;
use think\Response;

class Upload extends BaseController
{
    public function store(): Response
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json_error('请上传文件', 422);
        }

        $origName = method_exists($file, 'getOriginalName') ? (string)$file->getOriginalName() : (string)$file->getFilename();
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if ($ext === '' || !in_array($ext, $allowed, true)) {
            return json_error('仅支持 jpg/png/gif/webp 图片', 422);
        }

        $size = method_exists($file, 'getSize') ? (int)$file->getSize() : 0;
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            return json_error('图片大小需在 5MB 以内', 422);
        }

        $root = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads';
        $subdir = date('Ymd');
        $dir = $root . DIRECTORY_SEPARATOR . $subdir;
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return json_error('上传目录不可写', 500);
        }

        $filename = date('His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $abs = $dir . DIRECTORY_SEPARATOR . $filename;

        $tmp = method_exists($file, 'getPathname') ? (string)$file->getPathname() : '';
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            // ThinkPHP UploadedFile may already relocate tmp in some setups
            $tmp = method_exists($file, 'getRealPath') ? (string)$file->getRealPath() : $tmp;
        }
        if ($tmp === '' || !is_file($tmp)) {
            return json_error('临时文件无效', 422);
        }

        if (!@move_uploaded_file($tmp, $abs) && !@rename($tmp, $abs) && !@copy($tmp, $abs)) {
            return json_error('保存失败，请检查目录权限', 500);
        }
        @chmod($abs, 0644);

        $rel = 'uploads/' . $subdir . '/' . $filename;
        $url = '/storage/' . $rel;
        return json(['data' => ['url' => $url, 'path' => $rel]]);
    }
}
