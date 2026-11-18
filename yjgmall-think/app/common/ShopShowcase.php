<?php

namespace app\common;

use app\model\Product;
use app\model\User;

/** 商品详情右侧：商家自选的本店商品，按会员档位限数量。 */
class ShopShowcase
{
    public static function limit(?User $user): int
    {
        return match (self::gradeCode($user)) {
            'silver' => 2,
            'gold' => 3,
            'diamond' => 5,
            default => 0,
        };
    }

    /** 跟商品页上的会员标识同一套：白银、黄金、星钻，其余都算普通会员。 */
    public static function gradeCode(?User $user): string
    {
        $code = (string) (VipEntitlement::memberMark($user)['member_grade'] ?? '');

        return in_array($code, ['silver', 'gold', 'diamond'], true) ? $code : 'free';
    }

    public static function gradeName(string $code): string
    {
        return match ($code) {
            'silver' => '白银会员',
            'gold' => '黄金会员',
            'diamond' => '星钻会员',
            default => '普通会员',
        };
    }

    public static function meta(?User $user): array
    {
        $code = self::gradeCode($user);
        $limit = self::limit($user);
        $used = 0;
        if ($user) {
            $used = (int) Product::where('user_id', (int) $user->id)->where('is_showcase', 1)->count();
        }

        return [
            'grade' => $code,
            'grade_name' => self::gradeName($code),
            'limit' => $limit,
            'used' => $used,
        ];
    }

    /** 当前档位内已勾选展示的商品 id（按 id 从早到晚）。 */
    public static function visibleIds(int $userId, int $limit): array
    {
        if ($userId < 1 || $limit < 1) {
            return [];
        }
        $ids = Product::where('user_id', $userId)->where('is_showcase', 1)->order('id', 'asc')->limit($limit)->column('id');

        return array_values(array_map('intval', $ids ?: []));
    }

    /**
     * 详情页展示用：先放已勾选的，不够再补同店已上架商品，总数不超过档位。
     * 普通会员 limit=0，不展示。
     * 同店：同卖家 user_id，或同 company_id（商品未绑公司时用卖家名下公司）。
     *
     * @return list<int>
     */
    public static function displayIds(int $userId, int $limit, int $excludeProductId = 0, ?int $companyId = null): array
    {
        if ($userId < 1 || $limit < 1) {
            return [];
        }
        $pinned = self::visibleIds($userId, $limit);
        if ($excludeProductId > 0) {
            $pinned = array_values(array_filter($pinned, static fn ($id) => $id !== $excludeProductId));
        }
        if (count($pinned) >= $limit) {
            return array_slice($pinned, 0, $limit);
        }
        $need = $limit - count($pinned);
        $except = $pinned;
        if ($excludeProductId > 0) {
            $except[] = $excludeProductId;
        }
        $q = Product::publishedQuery()->where(function ($w) use ($userId, $companyId) {
            $w->where('user_id', $userId);
            if ($companyId && $companyId > 0) {
                $w->whereOr('company_id', $companyId);
            }
        })->order('id', 'desc');
        if ($except) {
            $q->whereNotIn('id', array_values(array_unique($except)));
        }
        $fill = $q->limit($need)->column('id');
        $fill = array_values(array_map('intval', $fill ?: []));
        $ids = array_values(array_slice(array_merge($pinned, $fill), 0, $limit));
        // 同店只剩当前商品时仍展示 1 条，避免 VIP 侧栏整块空白
        if (!$ids && $excludeProductId > 0) {
            $self = Product::publishedQuery()->where(function ($w) use ($userId, $companyId) {
                $w->where('user_id', $userId);
                if ($companyId && $companyId > 0) {
                    $w->whereOr('company_id', $companyId);
                }
            })->where('id', $excludeProductId)->value('id');
            if ($self) {
                return [(int) $self];
            }
        }

        return $ids;
    }

    /** 解析卖家对应公司 id（商品未写 company_id 时也能按旺铺归集）。 */
    public static function companyIdFor(?Product $item, ?User $seller): int
    {
        if ($item && !empty($item->company_id)) {
            return (int) $item->company_id;
        }
        if ($item && !empty($item->company) && !empty($item->company->id)) {
            return (int) $item->company->id;
        }
        if (!$seller) {
            return 0;
        }
        $cid = (int) ($seller->company_id ?? 0);
        if ($cid > 0) {
            return $cid;
        }
        $name = trim((string) ($seller->company_name ?? ''));
        $q = \app\model\Company::where('user_id', (int) $seller->id)->whereIn('status', ['approved', 'offline']);
        if ($name !== '') {
            $q->where('name', $name);
        }
        $co = $q->order('id', 'asc')->find();

        return $co ? (int) $co->id : 0;
    }

    public static function forProduct(Product $item): array
    {
        $seller = $item->user ?? null;

        return self::forSeller((int) ($item->user_id ?? 0), (int) $item->id, $seller, self::companyIdFor($item, $seller));
    }

    /** 供应/商品详情：按卖家会员档位取本店商品（勾选优先，其余补已上架）。 */
    public static function forSeller(int $userId, int $excludeProductId = 0, ?User $seller = null, ?int $companyId = null): array
    {
        if ($userId < 1) {
            return [];
        }
        if (!$seller) {
            $seller = User::find($userId);
        }
        if ($companyId === null) {
            $companyId = self::companyIdFor(null, $seller);
        }
        $ids = self::displayIds($userId, self::limit($seller), $excludeProductId, $companyId > 0 ? $companyId : null);
        if (!$ids) {
            return [];
        }
        $rows = Product::publishedQuery()
            ->with(['category', 'user', 'company'])
            ->whereIn('id', $ids)
            ->select();
        $byId = [];
        foreach ($rows as $p) {
            $byId[(int) $p->id] = ApiResource::product($p);
        }
        $out = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $out[] = $byId[$id];
            }
        }

        return $out;
    }
}
