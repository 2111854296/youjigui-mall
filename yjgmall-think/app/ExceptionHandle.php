<?php
namespace app;

use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        $path = (string) $request->pathinfo();
        $accept = (string) $request->header('accept', '');
        $wantsJson = $request->isJson()
            || str_contains($accept, 'application/json')
            || str_starts_with($path, 'api');
        if (!$wantsJson || $e instanceof HttpResponseException) {
            return parent::render($request, $e);
        }

        $status = 500;
        $message = '操作失败';
        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: $message;
        } elseif ($e instanceof ValidateException) {
            $status = 422;
            $message = $e->getMessage() ?: $message;
        } else {
            $raw = $e->getMessage();
            if (preg_match("/Duplicate entry '([^']*)'/i", $raw, $m)) {
                $status = 422;
                $dup = $m[1] !== '' ? '「'.$m[1].'」' : '这项内容';
                $message = stripos($raw, 'slug') !== false
                    ? 'Slug 已被占用：'.$dup.'，请换一个别名'
                    : $dup.'已经存在，请换一个';
            } elseif ($this->app->isDebug()) {
                $message = $raw !== '' ? $raw : $message;
            }
        }

        return json(['message' => $message], $status >= 400 ? $status : 500);
    }
}
