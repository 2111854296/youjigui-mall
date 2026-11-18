<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ApiResource;
use app\common\ModuleGate;
use app\model\Address;
use app\model\Brand;
use app\model\ClubPost;
use app\model\Company;
use app\model\Exhibit;
use app\model\Friendship;
use app\model\Gift;
use app\model\GiftOrder;
use app\model\GroupDeal;
use app\model\GroupOrder;
use app\model\JobApplication;
use app\model\JobPost;
use app\model\KnowAnswer;
use app\model\KnowQuestion;
use app\model\Photo;
use app\model\Resume;
use app\model\Special;
use app\model\SpecialItem;
use app\model\User;
use app\model\Video;
use think\Response;

class ExtraModule extends BaseController
{
    /** 模型 → 模块开关键 */
    private const MODEL_MODULE = [
        Photo::class => 'photo',
        Video::class => 'video',
        GroupDeal::class => 'group',
        Exhibit::class => 'exhibit',
        Brand::class => 'brand',
        KnowQuestion::class => 'know',
        ClubPost::class => 'club',
    ];

    private function refuseModel(string $model): ?Response
    {
        $key = self::MODEL_MODULE[$model] ?? null;
        return $key ? ModuleGate::refuse($key) : null;
    }

    private function listPublic(string $model, $status, int $per = 16): Response
    {
        if ($e = $this->refuseModel($model)) return $e;
        $q = $model::order('id', 'desc');
        if (is_array($status)) {
            $q->whereIn('status', $status);
        } else {
            $q->where('status', $status);
        }
        if ($kw = $this->request->param('q')) {
            if ($model === Brand::class) {
                $q->whereLike('name', '%' . $kw . '%');
            } else {
                $q->whereLike('title', '%' . $kw . '%');
            }
        }
        return json(paginate_laravel($q->paginate(['list_rows' => $per])));
    }

    private function showPublic(string $model, $id, $status): Response
    {
        if ($e = $this->refuseModel($model)) return $e;
        $q = $model::where('id', $id);
        if (is_array($status)) {
            $q->whereIn('status', $status);
        } else {
            $q->where('status', $status);
        }
        $item = $q->find();
        if (!$item) return json_error('Not found', 404);
        if (isset($item->views)) { $item->views = (int)$item->views + 1; $item->save(); }
        return json_data($item->toArray());
    }

    private function memberCrudList(string $model): Response
    {
        if ($e = $this->refuseModel($model)) return $e;
        $user = $this->request->user;
        $paginator = $model::where('user_id', $user->id)->order('id', 'desc')->paginate(['list_rows' => 15]);
        return json(paginate_laravel($paginator));
    }

    private function memberStore(string $model, array $fields, string $defaultStatus = 'pending'): Response
    {
        if ($e = $this->refuseModel($model)) return $e;
        $user = $this->request->user;
        $data = $this->request->only($fields);
        $data['user_id'] = $user->id;
        $data['status'] = $defaultStatus;
        $item = $model::create($data);
        return json(['data' => $item->toArray()], 201);
    }

    private function memberShow(string $model, $id): Response
    {
        if ($e = $this->refuseModel($model)) return $e;
        $user = $this->request->user;
        $item = $model::where('user_id', $user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        return json_data($item->toArray());
    }

    private function memberUpdate(string $model, $id, array $fields, bool $rePending = true): Response
    {
        if ($e = $this->refuseModel($model)) return $e;
        $user = $this->request->user;
        $item = $model::where('user_id', $user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $data = $this->request->only($fields);
        // 改文重新进审，避免已通过内容被悄悄改掉仍挂在前台
        if ($rePending) {
            $data['status'] = 'pending';
        }
        $item->save($data);
        return json_data($item->refresh()->toArray());
    }

    private function memberDestroy(string $model, $id): Response
    {
        if ($e = $this->refuseModel($model)) return $e;
        $user = $this->request->user;
        $item = $model::where('user_id', $user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $item->delete();
        return json_message('ok');
    }

    private function expireStickyJobs(): void
    {
        try {
            JobPost::where('is_sticky', 1)
                ->whereNotNull('sticky_until')
                ->where('sticky_until', '<', date('Y-m-d H:i:s'))
                ->update(['is_sticky' => 0]);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /** Apply shared list filters for jobs query builder. */
    private function applyJobListFilters($q): void
    {
        if ($kw = $this->request->param('q')) {
            $q->where(function ($w) use ($kw) {
                $w->whereLike('j.title', '%' . $kw . '%')
                    ->whereOr('j.company_name', 'like', '%' . $kw . '%')
                    ->whereOr('j.area', 'like', '%' . $kw . '%')
                    ->whereOr('j.summary', 'like', '%' . $kw . '%')
                    ->whereOr('j.content', 'like', '%' . $kw . '%')
                    ->whereOr('j.job_category', 'like', '%' . $kw . '%');
            });
        }
        if ($area = $this->request->param('area')) {
            $q->whereLike('j.area', '%' . $area . '%');
        }
        if ($edu = $this->request->param('education')) {
            $q->whereLike('j.education', '%' . $edu . '%');
        }
        if ($exp = $this->request->param('experience')) {
            if ($exp === '应届' || $exp === '应届生') {
                $q->where(function ($w) {
                    $w->whereLike('j.experience', '%应届%')->whereOr('j.experience', 'like', '%实习%');
                });
            } elseif ($exp === '10+' || $exp === '10年以上') {
                $q->where(function ($w) {
                    $w->whereLike('j.experience', '%10%')->whereOr('j.experience', 'like', '%十年%');
                });
            } else {
                // 与企业表单取值一致：1-3年 / 3-5年 …
                $q->whereLike('j.experience', '%' . $exp . '%');
            }
        }
        if ($cat = $this->request->param('category')) {
            if ($cat !== '全部' && $cat !== '') {
                $q->where(function ($w) use ($cat) {
                    $w->where('j.job_category', $cat)
                        ->whereOr('j.title', 'like', '%' . $cat . '%')
                        ->whereOr('j.summary', 'like', '%' . $cat . '%')
                        ->whereOr('j.content', 'like', '%' . $cat . '%');
                });
            }
        }
        // 职位职能：与企业发布「职位类别」job_role 对齐（只认入库字段，避免标题模糊误伤）
        if ($role = $this->request->param('role')) {
            if ($role !== '全部' && $role !== '') {
                $q->where('j.job_role', $role);
            }
        }
        if ($nature = $this->request->param('nature')) {
            if ($nature !== '全部' && $nature !== '') {
                $q->where(function ($w) use ($nature) {
                    $w->where('j.job_nature', $nature)->whereOr('j.job_nature', 'like', '%' . $nature . '%');
                });
            }
        }
        if ($size = $this->request->param('size')) {
            if ($size !== '全部' && $size !== '') {
                if ($size === '20-') {
                    $q->where(function ($w) {
                        $w->whereIn('j.company_size', ['20-', '20人以下', '20-99'])
                            ->whereOr('j.company_size', 'like', '%20%');
                    });
                } else {
                    $q->where(function ($w) use ($size) {
                        $w->where('j.company_size', $size)->whereOr('j.company_size', 'like', '%' . $size . '%');
                    });
                }
            }
        }
        $salary = (string) $this->request->param('salary', '');
        if ($salary !== '' && $salary !== '全部') {
            $this->applySalaryBandFilter($q, $salary, 'j.salary_min', 'j.salary_max', 'j.salary');
        }
    }

    /** salary band: 5k / 5-8 / 8-12 / 12-20 / 20+ (unit: K) */
    private function applySalaryBandFilter($q, string $band, string $minCol, string $maxCol, string $textCol): void
    {
        if ($band === '5k') {
            $q->where(function ($w) use ($maxCol, $minCol, $textCol) {
                $w->where(function ($x) use ($maxCol) {
                    $x->whereNotNull($maxCol)->where($maxCol, '<', 5);
                })->whereOr(function ($x) use ($minCol) {
                    $x->whereNotNull($minCol)->where($minCol, '<', 5);
                })->whereOr(function ($x) use ($minCol, $maxCol, $textCol) {
                    $x->whereNull($minCol)->whereNull($maxCol)
                        ->where(function ($y) use ($textCol) {
                            $y->whereLike($textCol, '%3K%')->whereOr($textCol, 'like', '%4K%')
                                ->whereOr($textCol, 'like', '%5K以下%')->whereOr($textCol, 'like', '%面议%');
                        });
                });
            });
            return;
        }
        $ranges = [
            '5-8' => [5, 8],
            '8-12' => [8, 12],
            '12-20' => [12, 20],
        ];
        if (isset($ranges[$band])) {
            [$lo, $hi] = $ranges[$band];
            $q->where(function ($w) use ($minCol, $maxCol, $lo, $hi, $textCol) {
                $w->where(function ($x) use ($minCol, $maxCol, $lo, $hi) {
                    $x->whereNotNull($minCol)->whereNotNull($maxCol)
                        ->where($minCol, '<=', $hi)->where($maxCol, '>=', $lo);
                })->whereOr(function ($x) use ($minCol, $textCol, $lo) {
                    $x->whereNull($minCol)->whereLike($textCol, '%' . $lo . '%');
                });
            });
            return;
        }
        if ($band === '20+') {
            $q->where(function ($w) use ($minCol, $maxCol, $textCol) {
                $w->where(function ($x) use ($minCol) {
                    $x->whereNotNull($minCol)->where($minCol, '>=', 20);
                })->whereOr(function ($x) use ($maxCol) {
                    $x->whereNotNull($maxCol)->where($maxCol, '>=', 20);
                })->whereOr(function ($x) use ($minCol, $textCol) {
                    $x->whereNull($minCol)->where(function ($y) use ($textCol) {
                        $y->whereLike($textCol, '%20%')->whereOr($textCol, 'like', '%25%')
                            ->whereOr($textCol, 'like', '%30%')->whereOr($textCol, 'like', '%40%');
                    });
                });
            });
        }
    }

    public function jobs(): Response
    {
        $this->expireStickyJobs();
        $now = date('Y-m-d H:i:s');
        $q = JobPost::alias('j')
            ->leftJoin('users u', 'j.user_id = u.id')
            ->where('j.status', 'approved')
            ->field('j.*');
        $this->applyJobListFilters($q);

        $sort = (string) $this->request->param('sort', 'time');
        $q->orderRaw("CASE WHEN j.is_sticky = 1 AND (j.sticky_until IS NULL OR j.sticky_until >= '{$now}') THEN 1 ELSE 0 END DESC");
        $q->orderRaw("CASE WHEN u.vip_expire_at IS NOT NULL AND u.vip_expire_at >= '{$now}' THEN CASE u.vip_grade_code WHEN 'diamond' THEN 30 WHEN 'gold' THEN 20 WHEN 'silver' THEN 10 ELSE 0 END ELSE 0 END DESC");
        if ($sort === 'salary') {
            $q->orderRaw('IFNULL(j.salary_max,0) DESC')->orderRaw('IFNULL(j.salary_min,0) DESC');
        }
        $q->order('j.id', 'desc');

        $per = max(1, min(50, (int) $this->request->param('per_page', 16)));
        $paginator = $q->paginate(['list_rows' => $per]);
        $rows = $paginator->items();
        $uidList = [];
        foreach ($rows as $row) {
            $uid = (int) ($row->user_id ?? 0);
            if ($uid > 0) {
                $uidList[$uid] = true;
            }
        }
        $nameList = [];
        foreach ($rows as $row) {
            $name = trim((string) ($row->company_name ?? ''));
            if ($name !== '') {
                $nameList[$name] = true;
            }
        }
        $companyByUser = [];
        $companyByName = [];
        try {
            if ($uidList) {
                $cos = Company::whereIn('user_id', array_keys($uidList))
                    ->whereIn('status', ['approved', 'offline'])
                    ->order('id', 'desc')
                    ->select();
                foreach ($cos as $co) {
                    $uid = (int) ($co->user_id ?? 0);
                    if ($uid && !isset($companyByUser[$uid])) {
                        $companyByUser[$uid] = $co;
                    }
                }
            }
            if ($nameList) {
                $cos = Company::whereIn('name', array_keys($nameList))
                    ->whereIn('status', ['approved', 'offline'])
                    ->order('id', 'desc')
                    ->select();
                foreach ($cos as $co) {
                    $name = trim((string) ($co->name ?? ''));
                    if ($name !== '' && !isset($companyByName[$name])) {
                        $companyByName[$name] = $co;
                    }
                }
            }
        } catch (\Throwable $e) {
            $companyByUser = [];
            $companyByName = [];
        }
        $payload = paginate_laravel($paginator, function ($item) use ($companyByUser, $companyByName) {
            $arr = $item->toArray();
            $arr['contact_phone'] = \app\common\VipEntitlement::maskMobile($arr['contact_phone'] ?? null);
            $arr['contact_masked'] = true;
            $arr['is_sticky_active'] = !empty($arr['is_sticky']) && (
                empty($arr['sticky_until']) || strtotime((string) $arr['sticky_until']) >= time()
            );
            $uid = (int) ($arr['user_id'] ?? 0);
            $name = trim((string) ($arr['company_name'] ?? ''));
            $co = $uid ? ($companyByUser[$uid] ?? null) : null;
            if (!$co && $name !== '') {
                $co = $companyByName[$name] ?? null;
            }
            if ($co) {
                $arr['company_id'] = (int) $co->id;
                $arr['company_slug'] = (string) ($co->slug ?: $co->id);
            } else {
                $arr['company_id'] = null;
                $arr['company_slug'] = null;
            }

            return $arr;
        });

        // category facets (approved only; ignore current category filter)
        $facetQ = JobPost::where('status', 'approved');
        if ($area = $this->request->param('area')) {
            $facetQ->whereLike('area', '%' . $area . '%');
        }
        $allCount = (int) (clone $facetQ)->count();
        $byCat = [];
        try {
            $rows = (clone $facetQ)
                ->field('job_category, COUNT(*) AS cnt')
                ->group('job_category')
                ->select()
                ->toArray();
            foreach ($rows as $r) {
                $key = trim((string) ($r['job_category'] ?? '')) ?: '其他';
                $byCat[$key] = (int) ($r['cnt'] ?? 0);
            }
        } catch (\Throwable $e) {
            $byCat = [];
        }
        $payload['facets'] = ['total' => $allCount, 'categories' => $byCat];

        return json($payload);
    }

    public function jobShow($id): Response
    {
        $this->expireStickyJobs();
        $item = JobPost::where('id', $id)->where('status', 'approved')->find();
        if (!$item) {
            return json_error('Not found', 404);
        }
        if (isset($item->views)) {
            $item->views = (int) $item->views + 1;
            $item->save();
        }
        $viewer = $this->request->user ?? null;
        $arr = $item->toArray();
        $unlocked = $viewer && \app\common\VipEntitlement::hasUnlocked($viewer, 'job', (int) $item->id);
        $isOwner = $viewer && (int) $viewer->id === (int) ($item->user_id ?? 0);
        if (!$unlocked && !$isOwner) {
            $arr['contact_phone'] = \app\common\VipEntitlement::maskMobile($arr['contact_phone'] ?? null);
            $arr['contact_masked'] = true;
            $arr['contact_unlocked'] = false;
        } else {
            $arr['contact_masked'] = false;
            $arr['contact_unlocked'] = true;
        }

        return json_data($arr);
    }

    /** Talent pool: approved resumes, only for certified enterprises. */
    public function talents(): Response
    {
        if ($deny = $this->denyTalentViewer()) {
            return $deny;
        }
        $q = Resume::whereIn('status', ['approved', 'published'])
            ->where(function ($w) {
                $w->whereNull('open')
                    ->whereOr('open', 'public')
                    ->whereOr('open', '1')
                    ->whereOr('open', 1)
                    ->whereOr('open', '');
            });
        if ($kw = $this->request->param('q')) {
            $q->where(function ($w) use ($kw) {
                $w->whereLike('title', '%' . $kw . '%')
                    ->whereOr('summary', 'like', '%' . $kw . '%')
                    ->whereOr('area', 'like', '%' . $kw . '%')
                    ->whereOr('truename', 'like', '%' . $kw . '%')
                    ->whereOr('content', 'like', '%' . $kw . '%');
            });
        }
        if ($area = $this->request->param('area')) {
            $q->whereLike('area', '%' . $area . '%');
        }
        if ($edu = $this->request->param('education')) {
            $q->whereLike('education', '%' . $edu . '%');
        }
        if ($exp = $this->request->param('experience')) {
            if ($exp === '应届') {
                $q->where(function ($w) {
                    $w->whereLike('experience', '%应届%')->whereOr('experience', 'like', '%实习%');
                });
            } else {
                $q->whereLike('experience', '%' . $exp . '%');
            }
        }
        if ($cat = $this->request->param('category')) {
            if ($cat !== '全部' && $cat !== '') {
                $q->where(function ($w) use ($cat) {
                    $w->where('job_category', $cat)
                        ->whereOr('title', 'like', '%' . $cat . '%')
                        ->whereOr('summary', 'like', '%' . $cat . '%')
                        ->whereOr('content', 'like', '%' . $cat . '%');
                });
            }
        }
        // 与企业招人筛选「职位」、简历「职位类别」job_role 对齐
        if ($role = $this->request->param('role')) {
            if ($role !== '全部' && $role !== '') {
                $q->where('job_role', $role);
            }
        }
        $salary = (string) $this->request->param('salary', '');
        if ($salary !== '' && $salary !== '全部') {
            // resume minsalary/maxsalary may be yuan or K — normalize in SQL roughly
            $this->applyResumeSalaryBand($q, $salary);
        }
        $sort = (string) $this->request->param('sort', 'time');
        if ($sort === 'salary') {
            $q->orderRaw('IFNULL(maxsalary,0) DESC')->orderRaw('IFNULL(minsalary,0) DESC');
        }
        $q->order('id', 'desc');

        $viewer = $this->request->user ?? null;
        $full = \app\common\VipEntitlement::resumeFullVisible($viewer);
        $per = max(1, min(50, (int) $this->request->param('per_page', 16)));
        $paginator = $q->paginate(['list_rows' => $per]);
        $payload = paginate_laravel($paginator, function ($item) use ($viewer, $full) {
            return $this->presentTalent($item->toArray(), $viewer, $full, false);
        });
        $payload['facets'] = ['total' => (int) ($payload['meta']['total'] ?? 0)];

        return json($payload);
    }

    private function applyResumeSalaryBand($q, string $band): void
    {
        // Treat values: if > 200 assume yuan and convert to K for compare
        $toK = function (string $col) {
            return "CASE WHEN {$col} > 200 THEN FLOOR({$col}/1000) ELSE {$col} END";
        };
        $minK = $toK('minsalary');
        $maxK = $toK('maxsalary');
        if ($band === '5k') {
            $q->whereRaw("({$maxK} < 5 OR {$minK} < 5)");
            return;
        }
        $ranges = ['5-8' => [5, 8], '8-12' => [8, 12], '12-20' => [12, 20]];
        if (isset($ranges[$band])) {
            [$lo, $hi] = $ranges[$band];
            $q->whereRaw("({$minK} <= {$hi} AND {$maxK} >= {$lo})");
            return;
        }
        if ($band === '20+') {
            $q->whereRaw("({$minK} >= 20 OR {$maxK} >= 20)");
        }
    }

    /** 人才库仅已认证企业可看。 */
    private function denyTalentViewer(): ?Response
    {
        $viewer = optional_request_user();
        if (!$viewer || !$viewer->canBrowseTalents()) {
            return json_error('企业认证通过后才能查看人才', 403);
        }

        return null;
    }

    public function talentShow($id): Response
    {
        if ($deny = $this->denyTalentViewer()) {
            return $deny;
        }
        $item = Resume::where('id', $id)->whereIn('status', ['approved', 'published'])->find();
        if (!$item) {
            return json_error('Not found', 404);
        }
        $open = strtolower(trim((string) ($item->open ?? 'public')));
        $viewer = $this->request->user ?? null;
        $isOwner = $viewer && (int) ($viewer->id ?? 0) === (int) ($item->user_id ?? 0);
        if (!$isOwner && in_array($open, ['private', '0', 'hide', 'hidden'], true)) {
            return json_error('简历未公开', 404);
        }
        if (isset($item->views)) {
            $item->views = (int) $item->views + 1;
            $item->save();
        }
        $full = \app\common\VipEntitlement::resumeFullVisible($viewer);

        return json_data($this->presentTalent($item->toArray(), $viewer, $full, true));
    }

    /** @param array<string,mixed> $arr */
    private function presentTalent(array $arr, $viewer, bool $full, bool $detail): array
    {
        $id = (int) ($arr['id'] ?? 0);
        $isOwner = $viewer && (int) ($viewer->id ?? 0) === (int) ($arr['user_id'] ?? 0);
        $unlocked = $viewer && \app\common\VipEntitlement::hasUnlocked($viewer, 'resume', $id);
        $out = [
            'id' => $id,
            'user_id' => (int) ($arr['user_id'] ?? 0),
            'peer_user_id' => (int) ($arr['user_id'] ?? 0),
            'title' => $arr['title'] ?? '',
            'area' => $arr['area'] ?? '',
            'education' => $arr['education'] ?? '',
            'experience' => $arr['experience'] ?? '',
            'minsalary' => $arr['minsalary'] ?? null,
            'maxsalary' => $arr['maxsalary'] ?? null,
            'job_category' => $arr['job_category'] ?? null,
            'job_role' => $arr['job_role'] ?? null,
            'summary' => $arr['summary'] ?? '',
            'gender' => $arr['gender'] ?? null,
            'thumb' => $arr['thumb'] ?? null,
            'updated_at' => $arr['updated_at'] ?? null,
            'created_at' => $arr['created_at'] ?? null,
            'resume_full_visible' => $full || $isOwner,
        ];
        if ($full || $isOwner) {
            $out['truename'] = $arr['truename'] ?? '';
            $out['content'] = $detail ? ($arr['content'] ?? '') : mb_substr((string) ($arr['content'] ?? ''), 0, 200);
        } else {
            $name = (string) ($arr['truename'] ?? '');
            $out['truename'] = $name !== '' ? (mb_substr($name, 0, 1) . '**') : '求职者';
            $out['content'] = null;
            $out['summary'] = mb_substr((string) ($arr['summary'] ?? ''), 0, 80);
            $out['mask_notice'] = '普通会员仅可查看脱敏摘要，升级白银及以上可看完整简历';
        }
        if ($unlocked || $isOwner) {
            $out['mobile'] = $arr['mobile'] ?? null;
            $out['email'] = $arr['email'] ?? null;
            $out['contact_masked'] = false;
            $out['contact_unlocked'] = true;
        } else {
            $out['mobile'] = \app\common\VipEntitlement::maskMobile($arr['mobile'] ?? null);
            $out['email'] = null;
            $out['contact_masked'] = true;
            $out['contact_unlocked'] = false;
        }

        return $out;
    }
    public function knows(): Response {
        if ($e = ModuleGate::refuse('know')) return $e;
        // 仅展示已开放问题；会员投稿为 pending，运营通过后变为 open
        $q = KnowQuestion::whereIn('status', ['open', 'approved'])->order('id', 'desc');
        if ($kw = $this->request->param('q')) $q->whereLike('title', '%' . $kw . '%');
        return json(paginate_laravel($q->paginate(['list_rows' => 16])));
    }
    public function knowShow($id): Response {
        if ($e = ModuleGate::refuse('know')) return $e;
        $item = KnowQuestion::whereIn('status', ['open', 'approved'])->find($id);
        if (!$item) return json_error('Not found', 404);
        if (isset($item->views)) { $item->views = (int)$item->views + 1; $item->save(); }
        $answers = KnowAnswer::where('know_question_id', $item->id)
            ->where('status', 'published')->order('id', 'desc')->select()->toArray();
        return json(['data' => $item->toArray(), 'answers' => $answers]);
    }
    public function answerKnow($id): Response {
        if ($e = ModuleGate::refuse('know')) return $e;
        $user = $this->request->user ?? null;
        if (!$user) return json_error('请先登录', 401);
        $q = KnowQuestion::whereIn('status', ['open', 'approved'])->find($id);
        if (!$q) return json_error('问题不存在或未开放', 404);
        $content = trim((string) $this->request->post('content', ''));
        if ($content === '') return json_error('请填写回答内容', 422);
        $ans = KnowAnswer::create([
            'know_question_id' => $id,
            'user_id' => $user->id,
            'content' => $content,
            'status' => 'published',
        ]);
        $q->answers_count = (int)($q->answers_count ?? 0) + 1;
        $q->save();
        return json(['data' => $ans->toArray()], 201);
    }
    public function clubs(): Response { return $this->listPublic(ClubPost::class, 'approved'); }
    public function clubShow($id): Response { return $this->showPublic(ClubPost::class, $id, 'approved'); }
    public function specials(): Response { return $this->listPublic(Special::class, 'published'); }
    public function specialShow($id): Response {
        $item = Special::where('status', 'published')->where(function ($q) use ($id) {
            $q->where('id', $id)->whereOr('slug', $id);
        })->find();
        if (!$item) {
            $item = Special::where('status', 'published')->find($id);
        }
        if (!$item) return json_error('Not found', 404);
        if (isset($item->views)) { $item->views = (int)$item->views + 1; $item->save(); }
        $items = SpecialItem::where('special_id', $item->id)
            ->whereIn('status', ['published', 'approved'])
            ->order('sort', 'asc')->order('id', 'desc')
            ->select()->toArray();
        foreach ($items as &$row) {
            if (empty($row['link']) && !empty($row['item_type']) && !empty($row['item_id'])) {
                $row['link'] = match ($row['item_type']) {
                    'article', 'news' => '/news/' . $row['item_id'],
                    'product', 'mall' => '/mall/' . $row['item_id'],
                    'supply', 'sell' => '/sell/' . $row['item_id'],
                    'company' => '/companies/' . $row['item_id'],
                    'download' => '/download/' . $row['item_id'],
                    default => $row['link'] ?? '',
                };
            }
        }
        unset($row);
        $data = $item->toArray();
        $data['items'] = $items;
        return json_data($data);
    }
    public function photos(): Response { return $this->listPublic(Photo::class, ['approved', 'published']); }
    public function photoShow($id): Response { return $this->showPublic(Photo::class, $id, ['approved', 'published']); }
    public function videos(): Response { return $this->listPublic(Video::class, ['approved', 'published']); }
    public function videoShow($id): Response { return $this->showPublic(Video::class, $id, ['approved', 'published']); }
    public function groups(): Response { return $this->listPublic(GroupDeal::class, ['approved', 'published']); }
    public function groupShow($id): Response { return $this->showPublic(GroupDeal::class, $id, ['approved', 'published']); }
    public function exhibits(): Response { return $this->listPublic(Exhibit::class, ['published', 'approved']); }
    public function exhibitShow($id): Response { return $this->showPublic(Exhibit::class, $id, ['published', 'approved']); }
    public function gifts(): Response { return $this->listPublic(Gift::class, 'published'); }
    public function giftShow($id): Response { return $this->showPublic(Gift::class, $id, 'published'); }

    public function redeemGift($id): Response {
        $user = $this->request->user;
        $name = trim((string) $this->request->post('receiver_name', ''));
        $phone = trim((string) $this->request->post('receiver_phone', ''));
        $address = trim((string) $this->request->post('receiver_address', ''));
        if ($name === '' || $phone === '' || $address === '') {
            return json_error('请填写完整收货信息', 422);
        }
        try {
            $order = \think\facade\Db::transaction(function () use ($id, $user, $name, $phone, $address) {
                $gift = Gift::where('status', 'published')->lock(true)->find($id);
                if (!$gift) {
                    throw new \RuntimeException('礼品不存在或已下架', 404);
                }
                $stock = (int) ($gift->stock ?? 0);
                if ($stock <= 0) {
                    throw new \RuntimeException('库存不足', 422);
                }
                $fresh = \app\model\User::where('id', $user->id)->lock(true)->find();
                if (!$fresh) {
                    throw new \RuntimeException('用户不存在', 404);
                }
                $points = (int) ($gift->points ?? 0);
                if ((int) $fresh->points < $points) {
                    throw new \RuntimeException('积分不足', 422);
                }
                $fresh->points = (int) $fresh->points - $points;
                $fresh->save();
                $gift->stock = $stock - 1;
                $gift->save();
                return GiftOrder::create([
                    'gift_id' => $gift->id,
                    'user_id' => $fresh->id,
                    'points' => $points,
                    'status' => 'pending',
                    'receiver_name' => $name,
                    'receiver_phone' => $phone,
                    'receiver_address' => $address,
                ]);
            });
        } catch (\RuntimeException $e) {
            $code = (int) ($e->getCode() ?: 422);
            if ($code < 400 || $code > 599) {
                $code = 422;
            }
            return json_error($e->getMessage(), $code);
        }
        return json(['data' => $order->toArray(), 'message' => '兑换成功，请等待发货'], 201);
    }

    // --- jobs CRUD ---
    private function jobFields(): array
    {
        return [
            'title','content','salary','area','company_name','education','experience','headcount','summary',
            'contact_name','contact_phone','job_category','job_role','job_nature','company_size','salary_min','salary_max',
        ];
    }

    /** Parse salary text into [minK, maxK]. */
    private function parseSalaryK(?string $raw): array
    {
        $s = trim((string) $raw);
        if ($s === '') {
            return [null, null];
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*[-~～到至]\s*(\d+(?:\.\d+)?)/u', $s, $m)) {
            $a = (float) $m[1];
            $b = (float) $m[2];
            if (preg_match('/万/u', $s)) {
                $a *= 10;
                $b *= 10;
            } elseif (!preg_match('/[kK千]/u', $s) && $a >= 1000) {
                $a = round($a / 1000);
                $b = round($b / 1000);
            }
            return [(int) round($a), (int) round($b)];
        }
        if (preg_match('/(\d+(?:\.\d+)?)/u', $s, $m)) {
            $n = (float) $m[1];
            if (preg_match('/万/u', $s)) {
                $n *= 10;
            } elseif (!preg_match('/[kK千]/u', $s) && $n >= 1000) {
                $n = round($n / 1000);
            }
            return [(int) round($n), (int) round($n)];
        }
        return [null, null];
    }

    private function normalizeJobPayload(array $data): array
    {
        if (empty($data['job_nature'])) {
            $data['job_nature'] = '全职';
        }
        $min = array_key_exists('salary_min', $data) ? $data['salary_min'] : null;
        $max = array_key_exists('salary_max', $data) ? $data['salary_max'] : null;
        if (($min === null || $min === '') && ($max === null || $max === '') && !empty($data['salary'])) {
            [$min, $max] = $this->parseSalaryK((string) $data['salary']);
        }
        if ($min !== null && $min !== '') {
            $data['salary_min'] = (int) $min;
        }
        if ($max !== null && $max !== '') {
            $data['salary_max'] = (int) $max;
        }
        return $data;
    }
    public function memberJobs(): Response
    {
        $user = $this->request->user;
        $paginator = JobPost::where('user_id', $user->id)->order('id', 'desc')->paginate(['list_rows' => 15]);
        $ent = \app\common\VipEntitlement::entitlementSummary($user);
        $rows = paginate_laravel($paginator);
        $data = $rows['data'] ?? [];
        foreach ($data as &$row) {
            $jid = (int) ($row['id'] ?? 0);
            $row['received_count'] = $jid
                ? (int) JobApplication::where('job_post_id', $jid)->count()
                : 0;
        }
        unset($row);
        $rows['data'] = $data;

        return json(array_merge($rows, [
            'entitlement' => [
                'jobs_online_limit' => $ent['jobs_online_limit'],
                'jobs_online_used' => $ent['jobs_online_used'],
                'jobs_online_remain' => $ent['jobs_online_remain'],
                'top_price' => $ent['top_price'],
                'job_sticky_per_month' => $ent['job_sticky_per_month'],
                'job_sticky_remain_month' => $ent['job_sticky_remain_month'],
                'resume_contact_remain_total' => $ent['resume_contact_remain_total'],
            ],
        ]));
    }
    public function storeJob(): Response
    {
        if ($e = ApiResource::requireCertifiedEnterprise()) {
            return $e;
        }
        $user = $this->request->user;
        $data = $this->normalizeJobPayload($this->request->only($this->jobFields()));
        $data['user_id'] = $user->id;
        // 平台后台先审，通过后才在招聘专区展示；商户后台不能自行上站
        $data['status'] = 'pending';
        $item = JobPost::create($data);
        return json(['data' => $item->toArray(), 'message' => '已提交，平台审核通过后在招聘专区展示'], 201);
    }
    public function memberJobShow($id): Response
    {
        $user = $this->request->user;
        $item = JobPost::where('user_id', $user->id)->find($id);
        if (!$item) {
            return json_error('Not found', 404);
        }
        $arr = $item->toArray();
        $arr['received_count'] = (int) JobApplication::where('job_post_id', (int) $id)->count();

        return json_data($arr);
    }
    public function updateJob($id): Response
    {
        if ($e = ApiResource::requireCertifiedEnterprise()) {
            return $e;
        }
        $user = $this->request->user;
        $item = JobPost::where('user_id', $user->id)->find($id);
        if (!$item) {
            return json_error('Not found', 404);
        }
        $data = $this->normalizeJobPayload($this->request->only($this->jobFields()));
        $data['status'] = 'pending';
        $item->save($data);
        return json_data($item->refresh()->toArray());
    }
    public function destroyJob($id): Response { return $this->memberDestroy(JobPost::class, $id); }

    /** online=approved / offline */
    public function setJobStatus($id): Response
    {
        $user = $this->request->user;
        $item = JobPost::where('user_id', $user->id)->find($id);
        if (!$item) {
            return json_error('Not found', 404);
        }
        $status = (string) $this->request->post('status', '');
        if (!in_array($status, ['approved', 'offline'], true)) {
            return json_error('状态仅支持上架(approved)或下架(offline)', 422);
        }
        if ($status === 'approved') {
            if (!in_array((string) $item->status, ['approved', 'offline', 'pending'], true)) {
                return json_error('当前状态不可上架', 422);
            }
            // pending still needs admin; allow re-online from offline only
            if ((string) $item->status === 'pending') {
                return json_error('待审核职位请等待后台审核通过', 422);
            }
            $check = \app\common\VipEntitlement::assertCanGoOnline($user, (int) $item->id);
            if (!$check['ok']) {
                return json_error($check['message'] ?? '在线职位已达上限', 422, $check);
            }
        }
        $item->save(['status' => $status]);

        return json_data([
            'job' => $item->refresh()->toArray(),
            'entitlement' => \app\common\VipEntitlement::entitlementSummary($user),
        ]);
    }

    public function memberKnows(): Response { return $this->memberCrudList(KnowQuestion::class); }
    public function storeKnow(): Response { return $this->memberStore(KnowQuestion::class, ['title','content'], 'pending'); }
    public function memberKnowShow($id): Response { return $this->memberShow(KnowQuestion::class, $id); }
    public function updateKnow($id): Response { return $this->memberUpdate(KnowQuestion::class, $id, ['title','content']); }
    public function destroyKnow($id): Response { return $this->memberDestroy(KnowQuestion::class, $id); }

    public function memberClubs(): Response { return $this->memberCrudList(ClubPost::class); }
    public function storeClub(): Response { return $this->memberStore(ClubPost::class, ['title','content','thumb'], 'pending'); }
    public function memberClubShow($id): Response { return $this->memberShow(ClubPost::class, $id); }
    public function updateClub($id): Response { return $this->memberUpdate(ClubPost::class, $id, ['title','content','thumb']); }
    public function destroyClub($id): Response { return $this->memberDestroy(ClubPost::class, $id); }

    // --- photo / video / group / exhibit / brand member publish ---
    public function memberPhotos(): Response { return $this->memberCrudList(Photo::class); }
    public function storePhoto(): Response { return $this->memberStore(Photo::class, ['title','thumb','images','summary','content'], 'pending'); }
    public function memberPhotoShow($id): Response { return $this->memberShow(Photo::class, $id); }
    public function updatePhoto($id): Response { return $this->memberUpdate(Photo::class, $id, ['title','thumb','images','summary','content']); }
    public function destroyPhoto($id): Response { return $this->memberDestroy(Photo::class, $id); }

    public function memberVideos(): Response { return $this->memberCrudList(Video::class); }
    public function storeVideo(): Response { return $this->memberStore(Video::class, ['title','thumb','video_url','summary','content'], 'pending'); }
    public function memberVideoShow($id): Response { return $this->memberShow(Video::class, $id); }
    public function updateVideo($id): Response { return $this->memberUpdate(Video::class, $id, ['title','thumb','video_url','summary','content']); }
    public function destroyVideo($id): Response { return $this->memberDestroy(Video::class, $id); }

    public function memberGroups(): Response { return $this->memberCrudList(GroupDeal::class); }
    public function storeGroup(): Response {
        return $this->memberStore(GroupDeal::class, ['title','thumb','price','market_price','stock','ends_at','summary','content'], 'pending');
    }
    public function memberGroupShow($id): Response { return $this->memberShow(GroupDeal::class, $id); }
    public function updateGroup($id): Response {
        return $this->memberUpdate(GroupDeal::class, $id, ['title','thumb','price','market_price','stock','ends_at','summary','content']);
    }
    public function destroyGroup($id): Response { return $this->memberDestroy(GroupDeal::class, $id); }

    public function memberExhibits(): Response { return $this->memberCrudList(Exhibit::class); }
    public function storeExhibit(): Response {
        return $this->memberStore(Exhibit::class, ['title','thumb','area','venue','starts_on','ends_on','organizer','summary','content'], 'pending');
    }
    public function memberExhibitShow($id): Response { return $this->memberShow(Exhibit::class, $id); }
    public function updateExhibit($id): Response {
        return $this->memberUpdate(Exhibit::class, $id, ['title','thumb','area','venue','starts_on','ends_on','organizer','summary','content']);
    }
    public function destroyExhibit($id): Response { return $this->memberDestroy(Exhibit::class, $id); }

    public function memberBrands(): Response { return $this->memberCrudList(Brand::class); }
    public function storeBrand(): Response {
        if ($e = ModuleGate::refuse('brand')) return $e;
        $user = $this->request->user;
        $data = $this->request->only(['name','logo','country','website','intro','content']);
        $name = trim((string)($data['name'] ?? ''));
        if ($name === '') return json_error('请填写品牌名称', 422);
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
        $slug = trim($slug ?: ('b' . $user->id . time()), '-');
        $data['slug'] = $slug;
        $data['user_id'] = $user->id;
        $data['status'] = 'pending';
        $item = Brand::create($data);
        return json(['data' => $item->toArray(), 'message' => '已提交，平台审核通过后在品牌馆展示'], 201);
    }
    public function memberBrandShow($id): Response { return $this->memberShow(Brand::class, $id); }
    public function updateBrand($id): Response {
        return $this->memberUpdate(Brand::class, $id, ['name','logo','country','website','intro','content']);
    }
    public function destroyBrand($id): Response { return $this->memberDestroy(Brand::class, $id); }

    // --- group buy loop ---
    public function buyGroup($id): Response
    {
        if ($e = ModuleGate::refuse('group')) return $e;
        $deal = GroupDeal::whereIn('status', ['approved', 'published'])->find($id);
        if (!$deal) return json_error('团购不存在或未上架', 404);
        if (!empty($deal->ends_at) && strtotime((string)$deal->ends_at) < time()) {
            return json_error('团购已结束', 422);
        }
        $user = $this->request->user;
        if ((int)($deal->user_id ?? 0) === (int)$user->id) {
            return json_error('不能购买自己发布的团购', 422);
        }
        $qty = max(1, (int)$this->request->post('quantity', 1));
        // 0 表示售罄；最终扣减在财务审核入账时加锁完成
        $stock = (int)($deal->stock ?? 0);
        if ($stock <= 0 || $qty > $stock) return json_error('库存不足', 422);

        $name = trim((string)$this->request->post('receiver_name', ''));
        $phone = trim((string)$this->request->post('receiver_phone', ''));
        $address = trim((string)$this->request->post('receiver_address', ''));
        $addrId = (int)$this->request->post('address_id', 0);
        if ($addrId) {
            $addr = Address::where('user_id', $user->id)->find($addrId);
            if ($addr) {
                $name = $name ?: (string)($addr->name ?? '');
                $phone = $phone ?: (string)($addr->phone ?? '');
                if ($address === '') {
                    $address = trim(implode(' ', array_filter([
                        (string)($addr->province ?? ''),
                        (string)($addr->city ?? ''),
                        (string)($addr->district ?? ''),
                        (string)($addr->address ?? ''),
                    ])));
                }
            }
        }
        if ($name === '' || $phone === '' || mb_strlen($address) < 5) {
            return json_error('请填写完整收货信息', 422);
        }

        $price = (float)$deal->price;
        $amount = round($price * $qty, 2);
        $result = \app\common\FundPay::createIntent(
            $user,
            \app\common\FundPay::TYPE_GROUP,
            $amount,
            '团购：'.(string)$deal->title,
            'group:'.(int)$deal->id,
            [
                'group_deal_id' => (int)$deal->id,
                'quantity' => $qty,
                'receiver_name' => $name,
                'receiver_phone' => $phone,
                'receiver_address' => $address,
                'remark' => (string)$this->request->post('remark', ''),
            ],
            (string)$deal->title
        );
        if (empty($result['ok']) || empty($result['order'])) {
            return json_error($result['message'] ?? '下单失败', 422);
        }
        return json([
            'data' => \app\common\FundPay::serialize($result['order']),
            'message' => !empty($result['reused']) ? '已有进行中的付款订单' : '请对公转账并上传付款凭证',
        ], 201);
    }

    public function memberGroupOrders(): Response
    {
        if ($e = ModuleGate::refuse('group')) return $e;
        $user = $this->request->user;
        $paginator = GroupOrder::where('buyer_user_id', $user->id)->order('id', 'desc')->paginate(['list_rows' => 15]);
        return json(paginate_laravel($paginator));
    }

    public function memberGroupOrderShow($id): Response
    {
        if ($e = ModuleGate::refuse('group')) return $e;
        $user = $this->request->user;
        $item = GroupOrder::find($id);
        if (!$item) return json_error('Not found', 404);
        $uid = (int) $user->id;
        if ((int) $item->buyer_user_id !== $uid && (int) $item->seller_user_id !== $uid) {
            return json_error('Not found', 404);
        }
        return json_data($item->toArray());
    }

    public function memberGroupSellerOrders(): Response
    {
        if ($e = ModuleGate::refuse('group')) return $e;
        $user = $this->request->user;
        $paginator = GroupOrder::where('seller_user_id', $user->id)->order('id', 'desc')->paginate(['list_rows' => 15]);
        return json(paginate_laravel($paginator));
    }

    public function updateGroupOrderStatus($id): Response
    {
        if ($e = ModuleGate::refuse('group')) return $e;
        $user = $this->request->user;
        $item = GroupOrder::find($id);
        if (!$item) return json_error('Not found', 404);
        $status = (string)$this->request->post('status', '');
        $action = (string)$this->request->post('action', '');
        $map = [
            'ship' => 'shipped', 'shipped' => 'shipped',
            'complete' => 'completed', 'confirm' => 'completed', 'completed' => 'completed',
            'cancel' => 'cancelled', 'cancelled' => 'cancelled',
            'request_refund' => 'refund_requested', 'refund_requested' => 'refund_requested',
            'approve_refund' => 'refunded', 'refunded' => 'refunded',
            'reject_refund' => 'reject_refund',
        ];
        if ($action !== '' && isset($map[$action])) $status = $map[$action];
        elseif ($status !== '' && isset($map[$status])) $status = $map[$status];

        $isBuyer = (int)$item->buyer_user_id === (int)$user->id;
        $isSeller = (int)$item->seller_user_id === (int)$user->id;
        $cur = (string)$item->status;
        $reason = trim((string)$this->request->post('refund_reason', ''));

        if ($status === 'shipped') {
            if (!$isSeller) return json_error('仅卖家可发货', 403);
            if ($cur !== 'paid') return json_error('仅已支付可发货', 422);
            $track = trim((string)$this->request->post('tracking_no', ''));
            if ($track !== '') $item->tracking_no = $track;
        } elseif ($status === 'completed') {
            if (!$isBuyer && !$isSeller) return json_error('无权操作', 403);
            if (!in_array($cur, ['paid', 'shipped'], true)) return json_error('当前状态不可完成', 422);
        } elseif ($status === 'cancelled') {
            if (!$isBuyer && !$isSeller) return json_error('无权操作', 403);
            if (!in_array($cur, ['paid', 'pending_pay'], true)) return json_error('当前状态不可取消', 422);
            // 已支付取消需还库存（库存在财务审核通过时已扣）
            if ($cur === 'paid') {
                $deal = GroupDeal::where('id', $item->group_deal_id)->lock(true)->find();
                if ($deal) {
                    $deal->stock = (int)($deal->stock ?? 0) + (int)$item->quantity;
                    $deal->sold = max(0, (int)($deal->sold ?? 0) - (int)$item->quantity);
                    $deal->save();
                }
            }
        } elseif ($status === 'refund_requested') {
            if (!$isBuyer) return json_error('仅买家可申请退款', 403);
            if (!in_array($cur, ['paid', 'shipped'], true)) return json_error('当前状态不可申请退款', 422);
            if ($reason !== '') $item->refund_reason = $reason;
        } elseif ($status === 'refunded') {
            if (!$isSeller) return json_error('仅卖家可同意退款', 403);
            if ($cur !== 'refund_requested') return json_error('无待处理退款', 422);
            // 退回库存
            $deal = GroupDeal::find($item->group_deal_id);
            if ($deal) {
                $deal->stock = (int)($deal->stock ?? 0) + (int)$item->quantity;
                $deal->sold = max(0, (int)($deal->sold ?? 0) - (int)$item->quantity);
                $deal->save();
            }
        } elseif ($status === 'reject_refund') {
            if (!$isSeller) return json_error('仅卖家可拒绝退款', 403);
            if ($cur !== 'refund_requested') return json_error('无待处理退款', 422);
            $back = (string)$this->request->post('back_to', 'paid');
            $status = in_array($back, ['paid', 'shipped'], true) ? $back : 'paid';
        } else {
            return json_error('无效状态', 422);
        }

        $item->status = $status;
        $item->save();
        return json_data($item->toArray());
    }

    // --- resumes + apply ---
    private function resumeFields(): array
    {
        return ['title','truename','gender','education','experience','minsalary','maxsalary','mobile','email','area','job_category','job_role','summary','content','thumb','open','is_default'];
    }
    public function memberResumes(): Response
    {
        $user = $this->request->user;
        $payload = json_decode($this->memberCrudList(Resume::class)->getContent(), true) ?: [];
        $favored = $user->unseenResumeFavoredCount();
        if (!isset($payload['meta']) || !is_array($payload['meta'])) {
            $payload['meta'] = [];
        }
        $payload['meta']['favored'] = $favored;

        return json($payload);
    }
    private function resumeInputError(array $data): ?string
    {
        $name = trim((string) ($data['truename'] ?? ''));
        if ($name !== '' && !\app\common\InputCheck::realName($name)) {
            return '请用真实姓名';
        }
        $mobile = trim((string) ($data['mobile'] ?? ''));
        if ($mobile !== '' && !\app\common\InputCheck::mobile($mobile)) {
            return '请填写11位手机号';
        }
        $email = trim((string) ($data['email'] ?? ''));
        if ($email !== '' && !\app\common\InputCheck::email($email)) {
            return '请填写正确的邮箱';
        }

        return null;
    }

    public function storeResume(): Response
    {
        $user = $this->request->user;
        $data = $this->request->only($this->resumeFields());
        if ($err = $this->resumeInputError($data)) {
            return json_error($err, 422);
        }
        $data['user_id'] = $user->id;
        // 平台后台先审，通过后才可上人才库 / 用于投递
        $data['status'] = 'pending';
        $open = strtolower(trim((string) ($data['open'] ?? 'public')));
        $data['open'] = in_array($open, ['private', '0', 'hide', 'hidden'], true) ? 'private' : 'public';
        $isDefault = !empty($data['is_default']);
        $data['is_default'] = $isDefault ? 1 : 0;
        $item = Resume::create($data);
        if ($isDefault) {
            Resume::where('user_id', $user->id)->where('id', '<>', $item->id)->update(['is_default' => 0]);
        }
        return json(['data' => $item->toArray(), 'message' => '已提交，平台审核通过后可上人才库并用于投递'], 201);
    }
    public function memberResumeShow($id): Response { return $this->memberShow(Resume::class, $id); }
    public function updateResume($id): Response
    {
        $user = $this->request->user;
        $item = Resume::where('user_id', $user->id)->find($id);
        if (!$item) {
            return json_error('Not found', 404);
        }
        $data = $this->request->only($this->resumeFields());
        if ($err = $this->resumeInputError($data)) {
            return json_error($err, 422);
        }
        if (array_key_exists('open', $data)) {
            $open = strtolower(trim((string) $data['open']));
            $data['open'] = in_array($open, ['private', '0', 'hide', 'hidden'], true) ? 'private' : 'public';
        }
        if (array_key_exists('is_default', $data)) {
            $data['is_default'] = !empty($data['is_default']) ? 1 : 0;
        }
        // 商户/会员不可自行上站，内容变更一律重新进平台审核
        $data['status'] = 'pending';
        $item->save($data);
        if (!empty($data['is_default'])) {
            Resume::where('user_id', $user->id)->where('id', '<>', $item->id)->update(['is_default' => 0]);
        }
        return json_data($item->refresh()->toArray());
    }
    public function destroyResume($id): Response { return $this->memberDestroy(Resume::class, $id); }

    public function setDefaultResume($id): Response
    {
        $user = $this->request->user;
        $item = Resume::where('user_id', $user->id)->find($id);
        if (!$item) {
            return json_error('Not found', 404);
        }
        Resume::where('user_id', $user->id)->update(['is_default' => 0]);
        $item->save(['is_default' => 1]);
        return json_data($item->refresh()->toArray());
    }

    public function applyJobOptions($id): Response
    {
        $job = JobPost::where('status', 'approved')->find($id);
        if (!$job) return json_error('职位不存在', 404);
        $user = $this->request->user;
        $rq = Resume::where('user_id', $user->id)->whereIn('status', ['approved', 'published']);
        try {
            $rq->order('is_default', 'desc');
        } catch (\Throwable $e) {
            // column may be missing before upgrade
        }
        $resumes = $rq->order('id', 'desc')->select()->toArray();
        $pendingCount = (int) Resume::where('user_id', $user->id)->where('status', 'pending')->count();
        return json(['data' => [
            'job' => $job->toArray(),
            'resumes' => $resumes,
            'pending_resume_count' => $pendingCount,
            'hint' => empty($resumes)
                ? ($pendingCount > 0
                    ? '您有简历正在平台审核中，通过后方可投递'
                    : '请先在会员中心创建简历，提交后由平台审核通过再投递')
                : null,
        ]]);
    }

    public function applyJob($id): Response
    {
        $job = JobPost::where('status', 'approved')->find($id);
        if (!$job) return json_error('职位不存在', 404);
        $user = $this->request->user;
        if ((int)($job->user_id ?? 0) === (int)$user->id) {
            return json_error('不能投递自己发布的职位', 422);
        }
        $resumeId = (int)$this->request->post('resume_id', 0);
        $resume = Resume::where('user_id', $user->id)->whereIn('status', ['approved', 'published'])->find($resumeId);
        if (!$resume) {
            return json_error('请选择已通过平台审核的简历（待审/拒绝中的不可投递）', 422);
        }

        $exists = JobApplication::where('job_post_id', $job->id)->where('applicant_user_id', $user->id)->find();
        if ($exists) return json_error('您已投递过该职位', 422);

        $app = JobApplication::create([
            'job_post_id' => $job->id,
            'resume_id' => $resume->id,
            'employer_user_id' => $job->user_id,
            'applicant_user_id' => $user->id,
            'status' => 'pending',
        ]);
        if (isset($job->apply_count)) {
            $job->apply_count = (int)$job->apply_count + 1;
            $job->save();
        }
        return json(['data' => $app->toArray(), 'message' => '投递成功'], 201);
    }

    private function appliedCompanyName(?array $job, int $employerId): string
    {
        $fromJob = trim((string) ($job['company_name'] ?? ''));
        if ($this->usableCompanyName($fromJob)) {
            return $fromJob;
        }
        if ($employerId > 0) {
            $co = Company::where('user_id', $employerId)->order('id', 'desc')->find();
            $fromCompany = trim((string) ($co->name ?? ''));
            if ($this->usableCompanyName($fromCompany)) {
                return $fromCompany;
            }
            $u = User::find($employerId);
            $fromUser = trim((string) ($u->company_name ?? ''));
            if ($this->usableCompanyName($fromUser)) {
                return $fromUser;
            }
        }
        return '';
    }

    private function usableCompanyName(string $name): bool
    {
        if ($name === '' || preg_match('/^[\*\s·\-—]+$/u', $name)) {
            return false;
        }
        return !in_array($name, ['示例公司', '公司名称', '测试公司', '公司'], true);
    }

    public function memberJobApplications(): Response
    {
        $user = $this->request->user;
        $paginator = JobApplication::where('applicant_user_id', $user->id)->order('id', 'desc')->paginate(['list_rows' => 15]);
        $rows = paginate_laravel($paginator);
        $data = $rows['data'] ?? [];
        foreach ($data as &$row) {
            $job = JobPost::find($row['job_post_id']);
            $row['job'] = $job ? $job->toArray() : null;
            $row['resume'] = Resume::find($row['resume_id'])?->toArray();
            $employerId = (int) ($row['employer_user_id'] ?? ($job?->user_id ?? 0));
            $row['company_name'] = $this->appliedCompanyName($row['job'], $employerId);
        }
        unset($row);
        $rows['data'] = $data;
        return json($rows);
    }

    public function memberReceivedApplications(): Response
    {
        $user = $this->request->user;
        $paginator = JobApplication::where('employer_user_id', $user->id)->order('id', 'desc')->paginate(['list_rows' => 15]);
        $rows = paginate_laravel($paginator);
        $data = $rows['data'] ?? [];
        $full = \app\common\VipEntitlement::resumeFullVisible($user);
        foreach ($data as &$row) {
            $row['job'] = JobPost::find($row['job_post_id'])?->toArray();
            $resume = Resume::find($row['resume_id']);
            if ($resume) {
                $row['resume'] = $this->presentTalent($resume->toArray(), $user, $full, true);
            } else {
                $row['resume'] = null;
            }
            $u = User::find($row['applicant_user_id']);
            if ($u) {
                $ua = $u->toArray();
                unset($ua['password'], $ua['mobile']);
                $row['applicant'] = $ua;
            } else {
                $row['applicant'] = null;
            }
        }
        unset($row);
        $rows['data'] = $data;
        return json($rows);
    }

    public function updateJobApplicationStatus($id): Response
    {
        $user = $this->request->user;
        $item = JobApplication::where('employer_user_id', $user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        $status = (string)$this->request->post('status', '');
        if (!in_array($status, ['pending', 'viewed', 'accepted', 'rejected'], true)) {
            return json_error('无效状态', 422);
        }
        $item->status = $status;
        $item->is_read = $status === 'pending' ? 0 : 1;
        $item->save();
        return json_data($item->toArray());
    }

    public function readJobApplication($id): Response
    {
        $item = JobApplication::where('employer_user_id', $this->request->user->id)->find($id);
        if (!$item) return json_error('Not found', 404);
        if ((int) $item->is_read !== 1) {
            $item->is_read = 1;
            $item->save();
        }
        return json_message('ok');
    }

    public function friends(): Response {
        $user = $this->request->user;
        $uid = (int) $user->id;
        $rows = Friendship::where('user_id', $uid)->whereOr('friend_id', $uid)->order('id', 'desc')->select()->toArray();
        $peerIds = [];
        foreach ($rows as $r) {
            $peerIds[] = ((int) $r['user_id'] === $uid) ? (int) $r['friend_id'] : (int) $r['user_id'];
        }
        $peerIds = array_values(array_unique(array_filter($peerIds)));
        $byId = [];
        if ($peerIds) {
            foreach (User::whereIn('id', $peerIds)->select()->toArray() as $p) {
                $byId[(int) $p['id']] = $p;
            }
        }
        $out = [];
        foreach ($rows as $r) {
            $peerId = ((int) $r['user_id'] === $uid) ? (int) $r['friend_id'] : (int) $r['user_id'];
            if (!isset($byId[$peerId])) {
                continue;
            }
            $p = $byId[$peerId];
            $out[] = [
                'id' => $peerId,
                'friendship_id' => $r['id'] ?? null,
                'name' => $p['name'] ?? ($p['company_name'] ?? ''),
                'username' => $p['username'] ?? '',
                'company_name' => $p['company_name'] ?? '',
                'status' => $r['status'] ?? '',
                'created_at' => $r['created_at'] ?? '',
            ];
        }
        return json_data($out);
    }
    public function addFriend(): Response {
        $user = $this->request->user;
        $friendId = (int)$this->request->post('friend_id', 0);
        if ($friendId <= 0) return json_error('请输入用户ID', 422);
        if ($friendId === (int)$user->id) return json_error('不能添加自己', 422);
        if (!User::where('id', $friendId)->find()) return json_error('ID无效，请输入正确的ID', 422);
        $uid = (int)$user->id;
        $exists = Friendship::where('user_id', $uid)->where('friend_id', $friendId)->find()
            || Friendship::where('user_id', $friendId)->where('friend_id', $uid)->find();
        if ($exists) return json_error('已经是好友了', 422);
        $row = Friendship::create(['user_id' => $user->id, 'friend_id' => $friendId, 'status' => 'accepted']);
        return json(['data' => $row->toArray()], 201);
    }

    public function removeFriend($id): Response
    {
        $uid = (int) $this->request->user->id;
        $peer = (int) $id;
        if ($peer <= 0 || $peer === $uid) {
            return json_error('请选择要删除的好友', 422);
        }
        $mine = Friendship::where('user_id', $uid)->where('friend_id', $peer)->select();
        $theirs = Friendship::where('user_id', $peer)->where('friend_id', $uid)->select();
        if ($mine->isEmpty() && $theirs->isEmpty()) {
            return json_error('好友不存在', 404);
        }
        foreach ($mine as $row) {
            $row->delete();
        }
        foreach ($theirs as $row) {
            $row->delete();
        }
        return json_message('已删除');
    }
}
