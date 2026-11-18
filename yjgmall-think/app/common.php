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

if (!function_exists('optional_request_user')) {
    /** 公开接口上顺带识别店主，没有登录或凭证无效时返回 null。 */
    function optional_request_user(): ?\app\model\User
    {
        $plain = request_bearer_token();
        if (!$plain || !str_contains($plain, '|')) {
            return null;
        }
        [$id, $token] = explode('|', $plain, 2);
        $row = \app\model\PersonalAccessToken::where('id', (int) $id)
            ->where('tokenable_type', 'App\\Models\\User')
            ->find();
        if (!$row || !hash_equals((string) $row->token, hash('sha256', $token))) {
            return null;
        }
        $user = \app\model\User::find($row->tokenable_id);
        if (!$user || (method_exists($user, 'isBanned') && $user->isBanned())) {
            return null;
        }

        return $user;
    }
}

if (!function_exists('company_slug_from_name')) {
    /** 中文公司名没有可用字母时，用 c加用户编号，避免生成「-20」这种地址。 */
    function company_slug_from_name(string $name, int $userId): string
    {
        $base = trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)), '-');

        return ($base !== '' ? $base.'-' : 'c').$userId;
    }
}

if (!function_exists('floor_children')) {
    function floor_children($cat): array
    {
        $kids = $cat->children ?? null;
        if (!$kids) {
            return [];
        }
        $list = method_exists($kids, 'all') ? $kids->all() : (array) $kids;

        return array_map(static fn ($ch) => [
            'id'   => (int) $ch->id,
            'name' => $ch->name,
            'slug' => $ch->slug ?? null,
        ], $list);
    }
}

if (!function_exists('category_ids_with_children')) {
    function category_ids_with_children(int $catId): array
    {
        $childIds = \app\model\Category::where('parent_id', $catId)->column('id');

        return array_values(array_unique(array_merge([$catId], $childIds ?: [])));
    }
}

if (!function_exists('category_root_id')) {
    function category_root_id(int $catId): ?int
    {
        $cat = \app\model\Category::find($catId);
        if (!$cat) {
            return null;
        }

        return (int) ($cat->parent_id ?: $cat->id);
    }
}

/**
 * 商品多挂在一级楼层上，点二级类（如反应釜）时按类名在父级范围内再搜标题。
 */
if (!function_exists('filter_query_by_category')) {
    function filter_query_by_category($query, int $catId, array $textFields = ['title', 'summary']): void
    {
        $ids = category_ids_with_children($catId);
        $cat = \app\model\Category::find($catId);
        if (!$cat || !$cat->parent_id) {
            $query->whereIn('category_id', $ids ?: [0]);

            return;
        }
        $scope = category_ids_with_children((int) $cat->parent_id);
        $name = trim((string) $cat->name);
        $fields = array_values(array_filter($textFields, static fn ($f) => is_string($f) && preg_match('/^[a-z_]+$/', $f)));
        $query->where(function ($w) use ($ids, $scope, $name, $fields) {
            $w->whereIn('category_id', $ids ?: [0]);
            if ($name === '' || mb_strlen($name) < 2 || !$fields) {
                return;
            }
            $like = '%'.addcslashes($name, '%_\\').'%';
            $w->whereOr(function ($inner) use ($scope, $like, $fields) {
                $inner->whereIn('category_id', $scope ?: [0]);
                $inner->where(function ($t) use ($like, $fields) {
                    foreach ($fields as $i => $field) {
                        if ($i === 0) {
                            $t->whereLike($field, $like);
                        } else {
                            $t->whereOr($field, 'like', $like);
                        }
                    }
                });
            });
        });
    }
}

if (!function_exists('root_categories')) {
    /**
     * @param  string[]  $modules
     */
    function root_categories(array $modules, ?int $limit = null)
    {
        $cats = null;
        foreach ($modules as $module) {
            $q = \app\model\Category::where('module', $module)->whereNull('parent_id')->where('is_active', 1)
                ->with(['children'])->order('sort', 'asc');
            if ($limit) {
                $q->limit($limit);
            }
            $cats = $q->select();
            if (!$cats->isEmpty()) {
                return $cats;
            }
        }

        return $cats ?: new \think\model\Collection([]);
    }
}

if (!function_exists('user_wallet')) {
    function user_wallet($user): array
    {
        return [
            'balance' => (string) ($user->balance ?? 0),
            'deposit' => (string) ($user->deposit ?? 0),
            'points'  => (int) ($user->points ?? 0),
            'credit'  => (int) ($user->credit ?? 0),
        ];
    }
}

if (!function_exists('supply_natural_order')) {
    /** 默认排序：会员等级优先；同等级按询盘、收藏、资料完整度、最近更新，权重只做微调。 */
    function supply_natural_order($query, string $alias = '')
    {
        $col = static function (string $name) use ($alias): string {
            return $alias === '' ? $name : $alias . '.' . $name;
        };
        $query->orderRaw('(SELECT COALESCE(u.level,0) FROM users u WHERE u.id = ' . $col('user_id') . ' LIMIT 1) DESC');
        $query->orderRaw('(' . supply_natural_score_sql($alias) . ') DESC');
        $query->order($col('refreshed_at'), 'desc');
        $query->order($col('id'), 'desc');

        return $query;
    }

    function supply_natural_score_sql(string $alias = ''): string
    {
        $col = static function (string $name) use ($alias): string {
            return $alias === '' ? $name : $alias . '.' . $name;
        };
        $id = $col('id');
        $filled = static function (string $expr): string {
            return '(CASE WHEN IFNULL(' . $expr . ", '') <> '' THEN 1 ELSE 0 END)";
        };
        $pics = $filled($col('thumb')) . ' + ' . $filled($col('thumb1')) . ' + ' . $filled($col('thumb2'));
        $detail = '(CASE WHEN CHAR_LENGTH(IFNULL(' . $col('content') . ", '')) > 20 THEN 1 ELSE 0 END)";
        $attr = '(CASE WHEN IFNULL(' . $col('attr1_value') . ", '') <> '' OR IFNULL(" . $col('attr2_value') . ", '') <> '' OR IFNULL(" . $col('attr3_value') . ", '') <> '' THEN 1 ELSE 0 END)";
        $price = '(CASE WHEN ' . $col('price') . ' IS NOT NULL AND ' . $col('price') . ' > 0 THEN 1 ELSE 0 END)';
        $complete = '(' . $pics . ' + ' . $filled($col('summary')) . ' + ' . $detail . ' + ' . $attr . ' + ' . $price . ' + ' . $filled($col('brand')) . ')';
        $recent = '(CASE WHEN ' . $col('refreshed_at') . ' IS NULL THEN 0 ELSE GREATEST(0, 14 - DATEDIFF(CURDATE(), DATE(' . $col('refreshed_at') . '))) END)';
        $inq = "(SELECT COUNT(*) FROM inquiries iq WHERE iq.target_type IN ('supply','sell') AND iq.target_id = {$id})";
        $fav = "(SELECT COUNT(*) FROM favorites fv WHERE fv.favoritable_type = 'supply' AND fv.favoritable_id = {$id})";

        return $inq . ' * 10 + ' . $fav . ' * 6 + ' . $complete . ' * 4 + ' . $recent . ' + COALESCE(' . $col('weight') . ', 0)';
    }

    function supply_rank_place_code(int $page): string
    {
        return $page <= 1 ? 'rank_sell' : 'rank_sell_' . $page;
    }

    function supply_paid_slot_supply(int $page): ?\app\model\Supply
    {
        $now = date('Y-m-d H:i:s');
        $ads = \app\model\Ad::where('is_active', 1)
            ->where('position', supply_rank_place_code($page))
            ->order('sort', 'asc')
            ->select();
        foreach ($ads as $ad) {
            if ($ad->starts_at && (string) $ad->starts_at > $now) {
                continue;
            }
            if ($ad->ends_at && (string) $ad->ends_at < $now) {
                continue;
            }
            $id = 0;
            if (preg_match('#/sell/(\d+)#', (string) ($ad->link ?? ''), $m)) {
                $id = (int) $m[1];
            } elseif (preg_match('/supply\s*:\s*(\d+)/i', (string) ($ad->content ?? ''), $m)) {
                $id = (int) $m[1];
            }
            if ($id <= 0) {
                continue;
            }
            $supply = \app\model\Supply::publishedQuery()->with(['category', 'user'])->find($id);
            if ($supply) {
                return $supply;
            }
        }

        return null;
    }

    function supply_rank_slot_placeholder(int $page): array
    {
        return [
            'id' => 0,
            'is_slot' => true,
            'is_promoted' => false,
            'slot_page' => $page,
            'slot_to' => '/ads?place=' . supply_rank_place_code($page),
            'title' => '推广位招租',
            'thumb' => '',
        ];
    }

    /** 默认排序的供应列表：第 1–3 页第 1 条是付费推广位，其余走自然排序。 */
    function supply_ranked_sell_page($query, int $page, int $per): array
    {
        $slots = [];
        $ids = [];
        for ($i = 1; $i <= 3; $i++) {
            $supply = supply_paid_slot_supply($i);
            if (!$supply) {
                continue;
            }
            $slots[$i] = $supply;
            $ids[] = (int) $supply->id;
        }
        if ($ids) {
            $query->whereNotIn('id', array_values(array_unique($ids)));
        }
        $organicTotal = (int) (clone $query)->count();
        $cap3 = 3 * ($per - 1);
        if ($organicTotal <= 0) {
            $lastPage = 1;
        } elseif ($organicTotal <= $cap3) {
            $lastPage = (int) ceil($organicTotal / ($per - 1));
        } else {
            $lastPage = 3 + (int) ceil(($organicTotal - $cap3) / $per);
        }
        $page = min(max(1, $page), $lastPage);
        $showSlot = $page <= 3;
        if ($page <= 3) {
            $offset = ($page - 1) * ($per - 1);
            $limit = $per - 1;
        } else {
            $offset = $cap3 + ($page - 4) * $per;
            $limit = $per;
        }
        supply_natural_order($query);
        $rows = $limit > 0 ? $query->limit($offset, $limit)->select() : [];
        $data = [];
        if ($showSlot) {
            if (!empty($slots[$page])) {
                $row = \app\common\ApiResource::supply($slots[$page]);
                $row['is_promoted'] = true;
                $row['is_slot'] = false;
                $data[] = $row;
            } else {
                $data[] = supply_rank_slot_placeholder($page);
            }
        }
        foreach ($rows as $row) {
            $data[] = \app\common\ApiResource::supply($row);
        }

        return [
            'data' => $data,
            'meta' => [
                'current_page' => $page,
                'last_page' => $lastPage,
                'per_page' => $per,
                'total' => $organicTotal,
            ],
            'links' => [
                'first' => null,
                'last' => null,
                'prev' => $page > 1 ? $page - 1 : null,
                'next' => $page < $lastPage ? $page + 1 : null,
            ],
        ];
    }
}

if (!function_exists('mark_related_messages_read')) {
    function mark_related_messages_read(int $uid, array $needles): void
    {
        $needles = array_values(array_filter(array_map(static fn ($v) => trim((string) $v), $needles)));
        if ($uid < 1 || !$needles) {
            return;
        }
        $rows = \app\model\Message::where('user_id', $uid)->where('is_read', 0)->select();
        foreach ($rows as $row) {
            $blob = (string) $row->title . "\n" . (string) $row->content;
            foreach ($needles as $needle) {
                if (str_contains($blob, $needle)) {
                    $row->is_read = 1;
                    $row->save();
                    break;
                }
            }
        }
    }
}

if (!function_exists('company_filter_options')) {
    function company_filter_options(): array
    {
        return [
            'vip_levels' => [
                ['value' => 1, 'label' => 'VIP'],
                ['value' => 3, 'label' => '3+'],
                ['value' => 5, 'label' => '5+'],
                ['value' => 8, 'label' => '8+'],
                ['value' => 10, 'label' => '10'],
            ],
            'company_types' => ['企业单位', '事业单位或社会团体', '个体经营', '其他'],
            'business_modes' => ['制造商', '贸易商', '服务商', '其他机构'],
            'scales' => ['1-49人', '50-99人', '100-499人', '500-999人', '1000-3000人', '3000人以上'],
            'areas' => ['广东', '浙江', '江苏', '山东', '上海', '北京', '福建', '江西', '安徽', '湖北', '四川', '河北'],
        ];
    }
}

if (!function_exists('unwrap_destoon_lazy_html')) {
    /**
     * Destoon 迁移正文常用 lazy.gif + original/data-src 占位；
     * 新站没有懒加载脚本时，图片要点击/滚动才出现。输出前直接换成真实地址。
     */
    function unwrap_destoon_lazy_html(?string $html): string
    {
        $html = (string) $html;
        if ($html === '') {
            return $html;
        }
        if (
            stripos($html, 'lazy') === false
            && stripos($html, 'original=') === false
            && stripos($html, 'data-src') === false
            && stripos($html, 'data-original') === false
        ) {
            return $html;
        }

        $out = preg_replace_callback('/<img\b[^>]*>/i', static function (array $m) {
            $tag = $m[0];
            $real = '';
            foreach (['original', 'data-original', 'data-src', 'data-lazy', 'data-url'] as $attr) {
                if (preg_match('/\b'.preg_quote($attr, '/').'\s*=\s*(["\'])(.*?)\1/i', $tag, $mm)) {
                    $cand = trim(html_entity_decode($mm[2], ENT_QUOTES));
                    if ($cand !== '' && !preg_match('/lazy\.gif|blank\.gif|spacer|loading\.gif/i', $cand)) {
                        $real = $cand;
                        break;
                    }
                }
            }
            if ($real === '') {
                return $tag;
            }
            $safe = htmlspecialchars($real, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            if (preg_match('/\bsrc\s*=\s*(["\']).*?\1/i', $tag)) {
                $tag = preg_replace('/\bsrc\s*=\s*(["\']).*?\1/i', 'src="'.$safe.'"', $tag, 1) ?? $tag;
            } else {
                $tag = preg_replace('/<img\b/i', '<img src="'.$safe.'"', $tag, 1) ?? $tag;
            }

            return $tag;
        }, $html);

        return is_string($out) ? $out : $html;
    }
}
