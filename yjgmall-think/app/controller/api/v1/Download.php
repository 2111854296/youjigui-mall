<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ModuleGate;
use app\model\Download as DownloadModel;
use app\model\DownloadLog;
use app\model\User;
use think\Response;

class Download extends BaseController
{
    private function ensurePublicOpen(): ?Response
    {
        return ModuleGate::refuse('downloads');
    }

    /** 会员组等级：数字越大权限越高 */
    private function groupRank(?string $code): int
    {
        $map = [
            'guest' => 0,
            'pending' => 1,
            'personal' => 2,
            'enterprise' => 3,
            'company' => 3,
            'vip' => 4,
            'admin' => 9,
        ];
        return $map[$code ?? 'guest'] ?? 0;
    }

    public function index(): Response
    {
        if ($e = $this->ensurePublicOpen()) return $e;
        $q = $this->request->param('q');
        $query = DownloadModel::where('status', 'published')->order('id', 'desc');
        if ($q) $query->whereLike('title', '%' . $q . '%');
        return json(paginate_laravel($query->paginate(['list_rows' => 20])));
    }

    public function show($id): Response
    {
        if ($e = $this->ensurePublicOpen()) return $e;
        $item = DownloadModel::where('status', 'published')->find($id);
        if (!$item) return json_error('Not found', 404);
        $data = $item->toArray();
        // 详情不直接暴露收费文件真实地址给未授权用户
        $needAuth = (int)($item->require_login ?? 0) === 1 || (int)($item->points_cost ?? 0) > 0 || ($item->min_group ?? 'guest') !== 'guest';
        if ($needAuth) {
            unset($data['file_url']);
            $data['need_auth'] = true;
            $data['points_cost'] = (int)($item->points_cost ?? 0);
            $data['min_group'] = $item->min_group ?: 'guest';
            $data['require_login'] = (int)($item->require_login ?? 0);
        }
        return json_data($data);
    }

    /** 兼容旧点击统计（免费且无需登录） */
    public function hit($id): Response
    {
        if ($e = $this->ensurePublicOpen()) return $e;
        $item = DownloadModel::where('status', 'published')->find($id);
        if (!$item) return json_error('Not found', 404);
        $points = (int)($item->points_cost ?? 0);
        $requireLogin = (int)($item->require_login ?? 0) === 1;
        $minGroup = (string)($item->min_group ?: 'guest');
        if ($points > 0 || $requireLogin || $minGroup !== 'guest') {
            return json_error('请登录后通过正式下载接口获取', 401);
        }
        $item->downloads = (int)($item->downloads ?? 0) + 1;
        $item->save();
        return json(['message' => 'ok', 'data' => ['downloads' => (int)$item->downloads, 'file_url' => $item->file_url]]);
    }

    /** 正式下载：校验登录/会员组/积分 */
    public function fetch($id): Response
    {
        if ($e = $this->ensurePublicOpen()) return $e;
        $item = DownloadModel::where('status', 'published')->find($id);
        if (!$item) return json_error('Not found', 404);

        $points = (int)($item->points_cost ?? 0);
        $requireLogin = (int)($item->require_login ?? 0) === 1 || $points > 0;
        $minGroup = (string)($item->min_group ?: 'guest');
        $user = $this->request->user ?? null;

        if (($requireLogin || $minGroup !== 'guest') && !$user) {
            return json_error('请先登录后再下载', 401);
        }

        if ($user && $this->groupRank($user->group_code ?? 'personal') < $this->groupRank($minGroup)) {
            return json_error('当前会员组无权下载（需要：' . $minGroup . '）', 403);
        }

        if ($points > 0) {
            if (!$user) return json_error('请先登录', 401);
            if ((int)$user->points < $points) {
                return json_error('积分不足，需要 ' . $points . ' 积分', 422);
            }
            // 同一用户同一资料只扣一次
            $paid = DownloadLog::where('download_id', $item->id)->where('user_id', $user->id)->where('points', '>', 0)->find();
            if (!$paid) {
                $user->points = (int)$user->points - $points;
                $user->save();
                DownloadLog::create([
                    'download_id' => $item->id,
                    'user_id' => $user->id,
                    'points' => $points,
                ]);
            }
        } elseif ($user) {
            DownloadLog::create([
                'download_id' => $item->id,
                'user_id' => $user->id,
                'points' => 0,
            ]);
        }

        $item->downloads = (int)($item->downloads ?? 0) + 1;
        $item->save();

        return json([
            'message' => 'ok',
            'data' => [
                'file_url' => $item->file_url,
                'downloads' => (int)$item->downloads,
                'points_spent' => $points,
                'points_left' => $user ? (int)$user->points : null,
            ],
        ]);
    }
}
