<?php
namespace app\controller\api\v1;
use app\BaseController;
use app\common\ModuleGate;
use app\model\Company; use app\model\InvestProject; use app\model\Product; use app\model\QuoteProduct; use app\model\QuoteProductPrice; use app\model\User; use app\model\VipGrade;
use think\facade\Cache;
use think\Response;

class AdminResource extends BaseController
{
    /** 运营侧栏仍隐藏：暂未开放频道 + 其它暂不主推资源 */
    private const HIDDEN_MENU = [
        'article-topics',
        // 下列与 ModuleGate 关闭项对齐（再列一次防配置遗漏）
        'downloads', 'photos', 'videos', 'group-deals', 'exhibits', 'brands', 'know', 'club',
    ];

    /** 侧栏分组顺序（白话归类）；未列出的分组追加在后 */
    private const MENU_GROUP_ORDER = [
        '会员与资金',
        '买卖与订单',
        '信息审核',
        '广告推广',
        '网站配置',
        '更多功能',
    ];

    /** 分组内菜单顺序；未列出的项保持配置文件出现顺序追加 */
    private const MENU_ITEM_ORDER = [
        '会员与资金' => ['users', 'member-verifications', 'vip-pay-orders', 'fund-pay-orders', 'withdraws', 'ask-tickets'],
        '买卖与订单' => [
            'orders', 'inquiries', 'reviews',
            'gifts', 'gift-orders', 'invest-projects', 'invest-leads',
        ],
        '信息审核' => [
            'supplies', 'purchases', 'products', 'jobs', 'resumes',
        ],
        '广告推广' => ['banners', 'ads', 'ad-bookings', 'spreads'],
        '网站配置' => ['announcements', 'hotwords', 'friend-links', 'pages', 'categories', 'recycle'],
        '更多功能' => ['specials', 'special-items'],
    ];

    public function menu(): Response
    {
        $menu = [];
        $fixedFile = root_path().'config/admin_menus.php';
        if (is_file($fixedFile)) {
            $fixed = include $fixedFile;
            if (is_array($fixed)) {
                foreach ($fixed as $block) {
                    if (!empty($block['group']) && !empty($block['items'])) {
                        $menu[] = ['group' => $block['group'], 'items' => array_values($block['items'])];
                    }
                }
            }
        }
        $groups = [];
        $moduleHidden = ModuleGate::hiddenAdminResources();
        foreach ($this->allCfg() as $key => $cfg) {
            if (($cfg['menu'] ?? true) === false || in_array($key, self::HIDDEN_MENU, true) || in_array($key, $moduleHidden, true)) continue;
            $group = $cfg['group'] ?? '其他';
            if (in_array($group, ['资讯', '企业库', '行情报价', '资讯管理', '企业库管理'], true)) continue;
            $groups[$group][] = ['key'=>$key,'label'=>$cfg['label'],'to'=>'/admin/r/'.$key];
        }
        if (empty($groups['网站配置'])) {
            $groups['网站配置'] = [];
        }
        $groups['网站配置'][] = ['key' => 'recycle', 'label' => '回收站', 'to' => '/admin/recycle'];
        foreach (self::MENU_GROUP_ORDER as $name) {
            if (empty($groups[$name])) continue;
            $menu[] = ['group' => $name, 'items' => $this->expandAdCollections($name, $this->sortMenuItems($name, $groups[$name]))];
            unset($groups[$name]);
        }
        foreach ($groups as $name => $items) {
            $menu[] = ['group' => $name, 'items' => $this->expandAdCollections($name, $this->sortMenuItems($name, $items))];
        }
        $this->attachMenuBadges($menu);

        return json_data($menu);
    }

    /** 未完成事项：待审、待开通、待回复。为 0 的项不显示。 */
    private function attachMenuBadges(array &$menu): void
    {
        $counts = $this->pendingMenuCounts();
        foreach ($menu as &$block) {
            $sum = 0;
            foreach ($block['items'] as &$it) {
                $n = (int) ($counts[$it['to'] ?? ''] ?? 0);
                $it['badge'] = $n;
                $sum += $n;
            }
            unset($it);
            $block['badge'] = $sum;
        }
        unset($block);
    }

    /** @return array<string,int> */
    private function pendingMenuCounts(): array
    {
        $rules = [
            '/admin/r/articles?status=pending' => ['articles', ['pending']],
            '/admin/r/companies' => ['companies', ['pending']],
            '/admin/r/company-honors' => ['company-honors', ['pending']],
            '/admin/r/company-news' => ['company-news', ['pending']],
            '/admin/r/member-verifications' => ['member-verifications', ['pending']],
            '/admin/r/vip-pay-orders' => ['vip-pay-orders', ['pending_review']],
            '/admin/r/fund-pay-orders' => ['fund-pay-orders', ['pending_review']],
            '/admin/r/withdraws' => ['withdraws', ['pending']],
            '/admin/r/ask-tickets' => ['ask-tickets', ['new']],
            '/admin/r/inquiries' => ['inquiries', ['new', 'reply_pending']],
            '/admin/r/supplies' => ['supplies', ['pending']],
            '/admin/r/purchases' => ['purchases', ['pending']],
            '/admin/r/products' => ['products', ['pending']],
            '/admin/r/jobs' => ['jobs', ['pending']],
            '/admin/r/resumes' => ['resumes', ['pending']],
            '/admin/r/know' => ['know', ['pending']],
            '/admin/r/club' => ['club', ['pending']],
            '/admin/r/photos' => ['photos', ['pending']],
            '/admin/r/videos' => ['videos', ['pending']],
            '/admin/r/group-deals' => ['group-deals', ['pending']],
            '/admin/r/exhibits' => ['exhibits', ['pending']],
            '/admin/r/brands' => ['brands', ['pending']],
            '/admin/r/gift-orders' => ['gift-orders', ['pending']],
            '/admin/r/invest-leads' => ['invest-leads', ['new']],
            '/admin/r/ad-bookings' => ['ad-bookings', ['pending']],
            '/admin/r/spreads' => ['spreads', ['pending']],
        ];
        $cfg = $this->allCfg();
        $out = [];
        $moduleHidden = ModuleGate::hiddenAdminResources();
        foreach ($rules as $to => [$key, $values]) {
            if (in_array($key, $moduleHidden, true) || in_array($key, self::HIDDEN_MENU, true)) {
                continue;
            }
            $model = $cfg[$key]['model'] ?? null;
            $field = $cfg[$key]['status']['field'] ?? 'status';
            if (!$model || !class_exists($model)) {
                continue;
            }
            try {
                $out[$to] = (int) $model::whereIn($field, $values)->count();
            } catch (\Throwable $e) {
                $out[$to] = 0;
            }
        }

        return $out;
    }

    /** @param list<array{key:string,label:string,to:string}> $items */
    private function sortMenuItems(string $group, array $items): array
    {
        $prefer = self::MENU_ITEM_ORDER[$group] ?? [];
        if ($prefer === []) {
            return array_values($items);
        }
        $map = [];
        foreach ($items as $it) {
            $map[$it['key']] = $it;
        }
        $sorted = [];
        foreach ($prefer as $key) {
            if (isset($map[$key])) {
                $sorted[] = $map[$key];
                unset($map[$key]);
            }
        }
        foreach ($map as $it) {
            $sorted[] = $it;
        }

        return $sorted;
    }

    /**
     * 广告位按已有位置拆成侧栏合集（首页轮播、商城轮播等），不再堆在一张总表里。
     *
     * @param list<array{key:string,label:string,to:string}> $items
     * @return list<array{key:string,label:string,to:string}>
     */
    private function expandAdCollections(string $group, array $items): array
    {
        if ($group !== '广告推广') {
            return $items;
        }
        $out = [];
        foreach ($items as $it) {
            if (($it['key'] ?? '') === 'ads' && ($it['to'] ?? '') === '/admin/r/ads') {
                $collections = $this->adCollectionMenus();
                if ($collections === []) {
                    $out[] = $it;
                } else {
                    foreach ($collections as $child) {
                        $out[] = $child;
                    }
                }
                continue;
            }
            $out[] = $it;
        }

        return $out;
    }

    /** 与前台广告中心同一套分组：单独广告位一项，同一区块的多个位子合成一项。 */
    private function adAreaTitle(string $name, string $code): string
    {
        if (preg_match('/^(.*)·第\d+[位张]$/u', $name, $m)) {
            return $m[1];
        }
        if (preg_match('/^(.*?)第\d+页/u', $name, $m) && str_starts_with($code, 'rank_') && $m[1] !== '') {
            return $m[1].'列表';
        }

        return '';
    }

    /** @return list<array{code:string,name:string,active:int}> */
    private function loadAdPlaces(): array
    {
        $rows = [];
        try {
            foreach (\app\model\AdPlace::order('sort', 'asc')->order('id', 'asc')->select() as $place) {
                $code = trim((string) $place->code);
                $name = trim((string) $place->name);
                if ($code === '' || $name === '') {
                    continue;
                }
                $rows[] = ['code' => $code, 'name' => $name, 'active' => (int) ($place->is_active ?? 0)];
            }
        } catch (\Throwable $e) {
        }

        return $rows;
    }

    /** @return list<string> */
    private function adGroupCodes(string $code): array
    {
        $places = $this->loadAdPlaces();
        $self = null;
        foreach ($places as $place) {
            if ($place['code'] === $code) {
                $self = $place;
                break;
            }
        }
        if (!$self) {
            return [$code];
        }
        $title = $this->adAreaTitle($self['name'], $self['code']);
        if ($title === '') {
            return [$code];
        }
        $codes = [];
        foreach ($places as $place) {
            if ($this->adAreaTitle($place['name'], $place['code']) === $title) {
                $codes[] = $place['code'];
            }
        }

        return $codes !== [] ? $codes : [$code];
    }

    /** @return list<array{key:string,label:string,to:string}> */
    private function adCollectionMenus(): array
    {
        $places = $this->loadAdPlaces();
        if ($places === []) {
            return [];
        }
        $have = [];
        try {
            foreach (\app\model\Ad::field('position')->group('position')->select() as $row) {
                $code = trim((string) $row->position);
                if ($code !== '') {
                    $have[$code] = true;
                }
            }
        } catch (\Throwable $e) {
        }
        $buckets = [];
        foreach ($places as $place) {
            $title = $this->adAreaTitle($place['name'], $place['code']);
            if ($title === '') {
                continue;
            }
            $buckets[$title][] = $place;
        }
        $items = [];
        $covered = [];
        $emitted = [];
        foreach ($places as $place) {
            if ($place['active'] !== 1 && empty($have[$place['code']])) {
                continue;
            }
            $title = $this->adAreaTitle($place['name'], $place['code']);
            $mates = $title !== '' ? ($buckets[$title] ?? []) : [];
            if ($title === '' || count($mates) < 2) {
                $items[] = [
                    'key' => 'ads',
                    'label' => $place['name'],
                    'to' => '/admin/r/ads?position='.$place['code'],
                ];
                $covered[$place['code']] = true;
                continue;
            }
            if (isset($emitted[$title])) {
                $covered[$place['code']] = true;
                continue;
            }
            $emitted[$title] = true;
            $items[] = [
                'key' => 'ads',
                'label' => $title,
                'to' => '/admin/r/ads?group='.$mates[0]['code'],
            ];
            foreach ($mates as $mate) {
                $covered[$mate['code']] = true;
            }
        }
        foreach (array_keys($have) as $code) {
            if (isset($covered[$code]) || $code === 'home') {
                continue;
            }
            $name = $code;
            foreach ($places as $place) {
                if ($place['code'] === $code) {
                    $name = $place['name'];
                    break;
                }
            }
            $items[] = [
                'key' => 'ads',
                'label' => $name,
                'to' => '/admin/r/ads?position='.$code,
            ];
        }

        return $items;
    }

    public function meta($resource): Response
    {
        $cfg = $this->cfg($resource);
        $filters = $this->resolveFilters($cfg, $resource);
        $sortable = $this->resolveSortable($cfg);

        if ($resource === 'articles') {
            $this->ensureArticleQuoteProductColumn();
            if (isset($filters['quote_product_id'])) {
                $opts = [];
                try {
                    foreach (QuoteProduct::order('sort', 'asc')->order('id', 'asc')->limit(100)->select() as $qp) {
                        $opts[(string) $qp->id] = (string) ($qp->name ?? ('#'.$qp->id));
                    }
                } catch (\Throwable $e) {
                    $opts = [];
                }
                $filters['quote_product_id']['options'] = $this->optionsAsObject($opts);
            }
        }

        $fields = $cfg['fields'] ?? [];
        if (is_array($fields)) {
            foreach ($fields as &$field) {
                if (!is_array($field)) {
                    continue;
                }
                if (isset($field['options']) && is_array($field['options'])) {
                    $field['options'] = $this->optionsAsObject($field['options']);
                }
            }
            unset($field);
        }

        return json_data([
            'key' => $resource,
            'label' => $cfg['label'],
            'columns' => $cfg['columns'],
            'status' => $cfg['status'] ?? null,
            'filters' => $filters,
            'sortable' => $sortable,
            'search' => array_values($cfg['search'] ?? []),
            'actions' => $cfg['actions'] ?? [],
            'action_labels' => $cfg['action_labels'] ?? new \stdClass(),
            'editable' => $cfg['editable'] ?? [],
            'edit' => (bool) ($cfg['edit'] ?? true),
            'create' => (bool) ($cfg['create'] ?? false),
            'destroy' => (bool) ($cfg['destroy'] ?? false),
            'fillable' => $cfg['fillable'] ?? [],
            'fields' => $fields,
            'default_status' => $cfg['default_status'] ?? null,
        ]);
    }

    /** 把 PHP 连续数字键 options 转成 JSON 对象，避免 json_encode 成数组导致前端 0/"0" 对不上。 */
    private function optionsAsObject(array $opts): \stdClass
    {
        $obj = new \stdClass();
        foreach ($opts as $k => $v) {
            $obj->{(string) $k} = $v;
        }
        return $obj;
    }

    private function refuseClosedModule(string $resource): ?Response
    {
        $mod = ModuleGate::ADMIN_RESOURCE_MAP[$resource] ?? null;
        return $mod ? ModuleGate::refuse($mod) : null;
    }

    public function index($resource): Response
    {
        if ($e = $this->refuseClosedModule($resource)) return $e;
        $cfg = $this->cfg($resource);
        $model = $cfg['model'];
        $sortable = $this->resolveSortable($cfg);
        $sort = trim((string) $this->request->param('sort', ''));
        $order = strtolower(trim((string) $this->request->param('order', 'desc')));
        if (!in_array($order, ['asc', 'desc'], true)) {
            $order = 'desc';
        }
        if ($sort === '' || !in_array($sort, $sortable, true)) {
            $sort = in_array('id', $sortable, true) ? 'id' : ($sortable[0] ?? 'id');
        }
        $q = $model::order($sort, $order);
        if (!empty($cfg['with'])) {
            $with = array_map(fn ($r) => explode(':', $r)[0], (array) $cfg['with']);
            $q->with($with);
        }
        if ($resource === 'company-honors') {
            $this->syncExpiredHonors();
        }
        $statusField = $cfg['status']['field'] ?? null;
        $isBool = (bool) ($cfg['status']['bool'] ?? false);
        if ($this->request->param('status') !== null && $this->request->param('status') !== '' && $statusField) {
            $val = $this->request->param('status');
            if ($isBool) {
                $q->where($statusField, filter_var($val, FILTER_VALIDATE_BOOLEAN) || $val === '1' || $val === 1 ? 1 : 0);
            } else {
                $q->where($statusField, $val);
            }
        } elseif ($this->request->param('pending_only') && !empty($cfg['default_status']) && $statusField) {
            $q->where($statusField, $cfg['default_status']);
        }
        if ($resource === 'companies' && ($this->request->param('vip') === '1' || $this->request->param('vip') === 1)) {
            $q->where('vip_level', '>', 0);
        }
        if ($resource === 'companies') {
            $this->applyCompanyFilters($q);
        }
        if ($resource === 'categories' && ($mod = trim((string) $this->request->param('module', ''))) !== '') {
            $q->where('module', $mod);
        }
        if (in_array($resource, ['ads', 'banners'], true)) {
            $group = trim((string) $this->request->param('group', ''));
            $position = trim((string) $this->request->param('position', ''));
            if ($resource === 'ads' && $group !== '') {
                $q->whereIn('position', $this->adGroupCodes($group));
            } elseif ($position !== '') {
                if ($resource === 'banners' && in_array($position, ['home', 'home_banner'], true)) {
                    $q->whereIn('position', ['home', 'home_banner']);
                } else {
                    $q->where('position', $position);
                }
            }
        }
        $this->applyColumnFilters($q, $this->resolveFilters($cfg, $resource));
        if ($kw = $this->request->param('q')) {
            $like = '%'.$kw.'%';
            $cols = $cfg['search'] ?? [];
            if (!$cols) {
                $cols = ['title', 'name', 'username'];
            }
            if ($cols) {
                $q->where(function ($w) use ($cols, $like) {
                    foreach ($cols as $i => $col) {
                        $i === 0 ? $w->whereLike($col, $like) : $w->whereOr($col, 'like', $like);
                    }
                });
            }
        }
        $paginator = $q->paginate(['list_rows' => 20]);
        $data = array_map(fn ($row) => $this->serialize($row, $cfg), $paginator->items());
        if ($resource === 'quote-products') {
            $data = $this->decorateQuoteRows($data);
        }

        return json([
            'data' => array_values($data),
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'last_page' => (int) $paginator->lastPage(),
                'total' => (int) $paginator->total(),
                'sort' => $sort,
                'order' => $order,
            ],
        ]);
    }

    public function batch($resource): Response
    {
        if ($resource === 'articles') {
            return $this->batchArticles();
        }
        $cfg = $this->cfg($resource);
        if ($resource !== 'companies') {
            return json_error('该资源不支持批量操作', 403);
        }
        $ids = $this->request->post('ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if ($ids === []) {
            return json_error('请选择记录', 422);
        }
        $action = (string) $this->request->post('action', '');
        $model = $cfg['model'];
        $n = 0;
        if ($action === 'feature') {
            $n = $model::whereIn('id', $ids)->update(['is_featured' => 1]);
        } elseif ($action === 'unfeature') {
            $n = $model::whereIn('id', $ids)->update(['is_featured' => 0]);
        } elseif ($action === 'certify') {
            $n = $model::whereIn('id', $ids)->update(['is_certified' => 1]);
        } elseif ($action === 'offline') {
            $n = $model::whereIn('id', $ids)->update(['status' => 'offline']);
        } elseif ($action === 'vip') {
            $level = max(0, min(10, (int) $this->request->post('vip_level', 1)));
            $n = $model::whereIn('id', $ids)->update([
                'vip_level' => $level,
                'vip_theory' => $level,
                'vip_correct' => 0,
            ]);
        } else {
            return json_error('未知批量操作', 422);
        }
        return json(['message' => '已处理 '.$n.' 条', 'data' => ['count' => $n]]);
    }

    /** 和资讯列表同一套筛选：状态、分类、关键词。 */
    private function articleFilterQuery()
    {
        $cfg = $this->cfg('articles');
        $q = \app\model\Article::where('id', '>', 0);
        $statusField = $cfg['status']['field'] ?? null;
        $status = $this->request->param('status');
        if ($status !== null && $status !== '' && $statusField) {
            $q->where($statusField, $status);
        }
        $this->applyColumnFilters($q, $this->resolveFilters($cfg, 'articles'));
        $kw = trim((string) $this->request->param('q', ''));
        if ($kw !== '') {
            $like = '%'.$kw.'%';
            $cols = $cfg['search'] ?? ['title'];
            $q->where(function ($w) use ($cols, $like) {
                foreach ($cols as $i => $col) {
                    $i === 0 ? $w->whereLike($col, $like) : $w->whereOr($col, 'like', $like);
                }
            });
        }

        return $q;
    }

    /** 资讯列表：一起上架、下架、删除，或改到别的资讯分类。scope=filter 时按当前筛选处理全部页。 */
    private function batchArticles(): Response
    {
        $action = (string) $this->request->post('action', '');
        $scoped = (string) $this->request->post('scope', '') === 'filter';
        if ($scoped) {
            $total = (int) $this->articleFilterQuery()->count();
            if ($total < 1) {
                return json_error('当前筛选下没有资讯', 422);
            }

            return $this->batchArticleQuery($action, $total);
        }

        $ids = $this->request->post('ids', []);
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return json_error('请先勾选资讯', 422);
        }
        $action = (string) $this->request->post('action', '');
        $rows = \app\model\Article::whereIn('id', $ids)->select();
        if ($rows->isEmpty()) {
            return json_error('没有找到这些资讯', 404);
        }

        if ($action === 'publish') {
            $uncat = 0;
            foreach ($rows as $row) {
                if ((int) ($row->category_id ?? 0) <= 0) {
                    $uncat++;
                }
            }
            if ($uncat > 0) {
                return json_error('有 '.$uncat.' 篇未分类，请先选择分类再上架', 422);
            }
            $n = 0;
            foreach ($rows as $row) {
                $data = ['status' => 'published'];
                if (trim((string) ($row->published_at ?? '')) === '') {
                    $data['published_at'] = date('Y-m-d H:i:s');
                }
                $row->save($data);
                $n++;
            }
            $this->bustHomeCache('articles');

            return json(['message' => '已上架 '.$n.' 篇', 'data' => ['count' => $n]]);
        }

        if ($action === 'offline') {
            $n = \app\model\Article::whereIn('id', $ids)->update(['status' => 'offline']);
            $this->bustHomeCache('articles');

            return json(['message' => '已下架 '.$n.' 篇', 'data' => ['count' => $n]]);
        }

        if ($action === 'move') {
            $catId = (int) $this->request->post('category_id', 0);
            $cat = \app\model\Category::where('id', $catId)->whereIn('module', ['article', 'news'])->find();
            if (!$cat) {
                return json_error('请选择要放入的资讯分类', 422);
            }
            $n = \app\model\Article::whereIn('id', $ids)->update(['category_id' => $catId]);
            $this->bustHomeCache('articles');
            $name = (string) ($cat->name ?? '');

            return json(['message' => '已把 '.$n.' 篇移动到「'.$name.'」', 'data' => ['count' => $n, 'category' => $name]]);
        }

        if ($action === 'set_quote') {
            $this->ensureArticleQuoteProductColumn();
            $qpId = (int) $this->request->post('quote_product_id', 0);
            if ($qpId < 1) {
                return json_error('请选择行情品种', 422);
            }
            $qp = QuoteProduct::find($qpId);
            if (!$qp) {
                return json_error('行情品种不存在', 422);
            }
            $n = \app\model\Article::whereIn('id', $ids)->update(['quote_product_id' => $qpId]);
            $this->bustHomeCache('articles');
            $name = (string) ($qp->name ?? '');

            return json(['message' => '已把 '.$n.' 篇绑定行情品种「'.$name.'」', 'data' => ['count' => $n, 'quote_product' => $name]]);
        }

        if ($action === 'delete') {
            try {
                \app\common\AdminSecondPassword::assertCanDelete($this->request->user ?? null);
            } catch (\RuntimeException $e) {
                return json_error($e->getMessage(), (int) ($e->getCode() ?: 403));
            }
            $adminId = (int) (($this->request->user->id ?? 0));
            $n = 0;
            foreach ($rows as $row) {
                \app\common\AdminRecycle::push('articles', $row->toArray(), $adminId);
                $row->delete();
                $n++;
            }
            $this->bustHomeCache('articles');

            return json(['message' => '已把 '.$n.' 篇移入回收站', 'data' => ['count' => $n]]);
        }

        return json_error('未知批量操作', 422);
    }

    /** 按当前筛选处理全部资讯，不把每一页的编号传到浏览器。 */
    private function batchArticleQuery(string $action, int $total): Response
    {
        if ($action === 'publish') {
            $uncat = (int) $this->articleFilterQuery()->where(function ($q) {
                $q->whereNull('category_id')->whereOr('category_id', 0);
            })->count();
            if ($uncat > 0) {
                return json_error('当前筛选里有 '.$uncat.' 篇未分类，请先批量改分类再上架', 422);
            }
            $now = date('Y-m-d H:i:s');
            $this->articleFilterQuery()->whereNull('published_at')->update(['published_at' => $now]);
            $this->articleFilterQuery()->update(['status' => 'published']);
            $this->bustHomeCache('articles');

            return json(['message' => '已上架 '.$total.' 篇', 'data' => ['count' => $total]]);
        }

        if ($action === 'offline') {
            $this->articleFilterQuery()->update(['status' => 'offline']);
            $this->bustHomeCache('articles');

            return json(['message' => '已下架 '.$total.' 篇', 'data' => ['count' => $total]]);
        }

        if ($action === 'move') {
            $catId = (int) $this->request->post('category_id', 0);
            $cat = \app\model\Category::where('id', $catId)->whereIn('module', ['article', 'news'])->find();
            if (!$cat) {
                return json_error('请选择要放入的资讯分类', 422);
            }
            $this->articleFilterQuery()->update(['category_id' => $catId]);
            $this->bustHomeCache('articles');
            $name = (string) ($cat->name ?? '');

            return json(['message' => '已把 '.$total.' 篇移动到「'.$name.'」', 'data' => ['count' => $total, 'category' => $name]]);
        }

        if ($action === 'set_quote') {
            $this->ensureArticleQuoteProductColumn();
            $qpId = (int) $this->request->post('quote_product_id', 0);
            if ($qpId < 1) {
                return json_error('请选择行情品种', 422);
            }
            $qp = QuoteProduct::find($qpId);
            if (!$qp) {
                return json_error('行情品种不存在', 422);
            }
            $this->articleFilterQuery()->update(['quote_product_id' => $qpId]);
            $this->bustHomeCache('articles');
            $name = (string) ($qp->name ?? '');

            return json(['message' => '已把 '.$total.' 篇绑定行情品种「'.$name.'」', 'data' => ['count' => $total, 'quote_product' => $name]]);
        }

        if ($action === 'delete') {
            try {
                \app\common\AdminSecondPassword::assertCanDelete($this->request->user ?? null);
            } catch (\RuntimeException $e) {
                return json_error($e->getMessage(), (int) ($e->getCode() ?: 403));
            }
            $adminId = (int) (($this->request->user->id ?? 0));
            $n = 0;
            $guard = 0;
            while ($guard++ < 500) {
                $rows = $this->articleFilterQuery()->limit(100)->select();
                if ($rows->isEmpty()) {
                    break;
                }
                foreach ($rows as $row) {
                    \app\common\AdminRecycle::push('articles', $row->toArray(), $adminId);
                    $row->delete();
                    $n++;
                }
            }
            $this->bustHomeCache('articles');

            return json(['message' => '已把 '.$n.' 篇移入回收站', 'data' => ['count' => $n]]);
        }

        return json_error('未知批量操作', 422);
    }

    public function show($resource, $id): Response
    {
        if ($e = $this->refuseClosedModule($resource)) return $e;
        $cfg = $this->cfg($resource);
        $model = $cfg['model'];
        $q = $model::where('id', $id);
        if (!empty($cfg['with'])) {
            $with = array_map(fn($r) => explode(':', $r)[0], (array)$cfg['with']);
            $q->with($with);
        }
        $item = $q->find();
        if (!$item) return json_error('Not found', 404);
        return json_data($this->serialize($item, $cfg));
    }

    public function store($resource): Response
    {
        if ($e = $this->refuseClosedModule($resource)) return $e;
        $cfg = $this->cfg($resource);
        if (empty($cfg['create'])) return json_error('该资源不允许新建', 403);
        $fillable = $cfg['fillable'] ?? $cfg['editable'] ?? [];
        $data = $this->normalizePayload($this->request->only($fillable), $resource, 'create');
        if (!empty($cfg['default_status']) && empty($data[$cfg['status']['field'] ?? 'status'])) {
            $data[$cfg['status']['field'] ?? 'status'] = $cfg['default_status'];
        }
        if ($resource === 'companies') {
            $data = $this->applyCompanyVipIndex($data, null);
        }
        if ($resource === 'company-honors' && empty($data['status'])) {
            $data['status'] = 'pending';
        }
        if ($resource === 'articles') {
            $status = (string) ($data['status'] ?? ($cfg['default_status'] ?? ''));
            if ($status === 'published' && (int) ($data['category_id'] ?? 0) <= 0) {
                return json_error('请先选择资讯分类再上架', 422);
            }
        }
        $model = $cfg['model'];
        $item = $model::create($data);
        if ($resource === 'companies') {
            $this->syncCompanyUserVerify($item, $this->request->param());
        }
        if ($resource === 'quote-products') {
            $this->snapshotQuotePrice($item->refresh());
            \app\common\QuoteAdSlots::sync($item->refresh());
            $this->bustQuoteCache();
        }
        $this->bustHomeCache($resource);
        return json(['data' => $this->serialize($item->refresh(), $cfg), 'message' => '已创建'], 201);
    }

    public function update($resource, $id): Response
    {
        if ($e = $this->refuseClosedModule($resource)) return $e;
        $cfg = $this->cfg($resource);
        $model = $cfg['model'];
        $item = $model::find($id);
        if (!$item) return json_error('Not found', 404);
        $hasAction = (bool) $this->request->param('action') && !empty($cfg['actions']);
        if (($cfg['edit'] ?? true) === false && !$hasAction) {
            return json_error('该模块不支持编辑', 403);
        }
        $editable = $cfg['editable'] ?? [];
        $data = $this->normalizePayload($this->request->only($editable), $resource, 'update');
        if ($hasAction) {
            $action = (string)$this->request->param('action');
            if (!array_key_exists($action, $cfg['actions'])) return json_error('未知操作', 422);
            $field = $cfg['status']['field'] ?? 'status';
            $value = $cfg['actions'][$action];
            $data[$field] = !empty($cfg['status']['bool']) ? (int)(bool)$value : $value;
            // 列表快捷拒绝可带 admin_remark
            if ($action === 'reject' && $this->request->post('admin_remark') !== null) {
                $data['admin_remark'] = trim((string) $this->request->post('admin_remark'));
            }
        }
        if ($resource === 'articles') {
            $nextStatus = (string) ($data['status'] ?? ($item->status ?? ''));
            $catId = array_key_exists('category_id', $data) ? (int) $data['category_id'] : (int) ($item->category_id ?? 0);
            if ($nextStatus === 'published' && $catId <= 0) {
                return json_error('请先选择资讯分类再上架', 422);
            }
        }
        if ($resource === 'users' && ($data['status'] ?? null) === 'active' && empty($data['group_code'])) {
            $data['group_code'] = (($item->member_type ?? '') === 'company') ? 'enterprise' : 'personal';
        }
        if ($resource === 'companies') {
            $data = $this->applyCompanyVipIndex($data, $item);
            $this->syncCompanyUserVerify($item, $this->request->param());
        }
        if (in_array(($cfg['hook'] ?? null), ['vip_pay_approve', 'fund_pay_approve'], true)) {
            $nextStatus = (string) ($data['status'] ?? '');
            if ($nextStatus === 'approved') {
                $cur = (string) ($item->status ?? '');
                $voucher = trim((string) ($item->voucher_url ?? ''));
                if ($cur !== 'pending_review' || $voucher === '') {
                    return json_error('仅可对「待审核」且已上传付款凭证的订单执行通过', 422);
                }
            }
            if ($nextStatus === 'rejected') {
                $remark = trim((string) ($data['admin_remark'] ?? ''));
                if ($remark === '') {
                    return json_error('请填写驳回原因（用户可见）', 422);
                }
                $data['admin_remark'] = $remark;
            }
        }
        $prevWithdrawStatus = ($resource === 'withdraws') ? (string) ($item->status ?? '') : '';
        $prevInquiryStatus = ($resource === 'inquiries') ? (string) ($item->status ?? '') : '';
        if (($cfg['hook'] ?? null) === 'inquiry_forward') {
            $nextInquiryStatus = (string) ($data['status'] ?? $item->status ?? '');
            $toUserId = array_key_exists('to_user_id', $data)
                ? (int) $data['to_user_id']
                : (int) ($item->to_user_id ?? 0);
            if ($nextInquiryStatus === 'processing' && $prevInquiryStatus === 'new' && $toUserId <= 0) {
                return json_error('该留言未绑定企业，无法转发。', 422);
            }
            if ($nextInquiryStatus === 'replied' && $prevInquiryStatus === 'reply_pending') {
                if (trim((string) ($item->reply ?? '')) === '') {
                    return json_error('企业尚未提交回复内容，无法通过', 422);
                }
            }
        }
        if ($resource === 'products') {
            $nextStatus = (string) ($data['status'] ?? '');
            if ($nextStatus === 'offline') {
                $data['is_listed'] = 0;
                $data['is_showcase'] = 0;
                $data['is_sample'] = 0;
            } elseif (in_array($nextStatus, ['approved', 'published'], true)) {
                // 上架：审核通过/发布时同步可售标记
                $data['is_listed'] = 1;
            }
        }
        $prevQuote = null;
        if ($resource === 'quote-products') {
            $prevQuote = [
                'date' => substr((string) ($item->quoted_on ?? ''), 0, 10),
                'price_avg' => $item->price_avg,
                'price_min' => $item->price_min,
                'price_max' => $item->price_max,
                'market' => $item->market,
            ];
        }
        $item->save($data);
        if (($cfg['hook'] ?? null) === 'vip_approve' && ($data['status'] ?? null) === 'approved') {
            $this->approveVip($item);
        }
        if (($cfg['hook'] ?? null) === 'vip_pay_approve') {
            $status = (string) ($data['status'] ?? '');
            $adminId = (int) ($this->request->user->id ?? 0);
            if ($status === 'approved') {
                $this->approveVipPay($item, $adminId);
            } elseif ($status === 'rejected') {
                $this->rejectVipPay($item, $adminId);
            }
        }
        if (($cfg['hook'] ?? null) === 'fund_pay_approve') {
            $status = (string) ($data['status'] ?? '');
            $adminId = (int) ($this->request->user->id ?? 0);
            if ($status === 'approved') {
                $approved = \app\common\FundPay::approve($item->refresh(), $adminId);
                if (empty($approved['ok'])) {
                    return json_error($approved['message'] ?? '处理失败', 422);
                }
            } elseif ($status === 'rejected') {
                \app\common\FundPay::reject($item->refresh(), $adminId);
            }
        }
        if ($resource === 'withdraws' && ($data['status'] ?? null) === 'rejected' && $prevWithdrawStatus === 'pending') {
            $this->refundWithdraw($item->refresh());
        }
        if ($resource === 'quote-products') {
            if ($prevQuote) {
                $this->keepQuoteDay((int) $item->id, $prevQuote);
            }
            $this->snapshotQuotePrice($item->refresh());
            \app\common\QuoteAdSlots::sync($item->refresh());
            $this->bustQuoteCache();
        }
        if ($resource === 'spreads' && ($data['status'] ?? null) === 'active') {
            $this->activateSpread($item->refresh());
        }
        if ($resource === 'member-verifications' && in_array(($data['status'] ?? null), ['approved', 'rejected'], true)) {
            $this->applyMemberVerification($item->refresh());
        }
        if (($cfg['hook'] ?? null) === 'inquiry_forward') {
            $fresh = $item->refresh();
            $this->forwardInquiryToEnterprise($fresh, $prevInquiryStatus);
            $this->deliverInquiryReplyToBuyer($fresh, $prevInquiryStatus);
        }
        $this->bustHomeCache($resource);
        return json(['data' => $this->serialize($item->refresh(), $cfg), 'message' => '已更新']);
    }

    private function activateSpread($item): void
    {
        $changed = false;
        if (empty($item->starts_at)) {
            $item->starts_at = date('Y-m-d H:i:s');
            $changed = true;
        }
        if (empty($item->ends_at)) {
            $days = max(1, (int) ($item->days ?: 30));
            $item->ends_at = date('Y-m-d H:i:s', strtotime((string) $item->starts_at) + $days * 86400);
            $changed = true;
        }
        if ($changed) {
            $item->save();
        }
    }

    private function applyMemberVerification($item): void
    {
        $item->reviewed_at = date('Y-m-d H:i:s');
        $item->save();
        if ((string) $item->status !== 'approved') {
            return;
        }
        $user = User::find((int) $item->user_id);
        if (!$user) {
            return;
        }
        $type = (string) $item->type;
        if ($type === 'mobile') {
            $user->mobile = $item->mobile;
            $user->mobile_verified = 1;
        } elseif ($type === 'truename') {
            $user->truename = $item->truename;
            $user->truename_verified = 1;
        } elseif ($type === 'bank') {
            $user->bank_name = $item->bank_name;
            $user->bank_account = $item->bank_account;
            if (trim((string) $item->truename) !== '') {
                $user->truename = $item->truename;
            }
            $user->bank_verified = 1;
        }
        $user->save();
    }

    /** 改报价日期时，把改之前那一天留在历史里，避免只剩最新一条。 */
    private function keepQuoteDay(int $productId, array $prev): void
    {
        $date = substr((string) ($prev['date'] ?? ''), 0, 10);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $prev['price_avg'] === null || $prev['price_avg'] === '') {
            return;
        }
        $exists = \app\model\QuoteProductPrice::where('product_id', $productId)->where('quoted_on', $date)->find();
        if ($exists) {
            return;
        }
        \app\model\QuoteProductPrice::create([
            'product_id' => $productId,
            'quoted_on' => $date,
            'price_avg' => $prev['price_avg'],
            'price_min' => $prev['price_min'],
            'price_max' => $prev['price_max'],
            'market' => $prev['market'] ?? null,
        ]);
    }

    private function snapshotQuotePrice($item): void
    {
        $date = substr((string) ($item->quoted_on ?: date('Y-m-d')), 0, 10) ?: date('Y-m-d');
        $row = QuoteProductPrice::where('product_id', (int) $item->id)->where('quoted_on', $date)->find();
        $payload = [
            'product_id' => (int) $item->id,
            'quoted_on' => $date,
            'price_avg' => $item->price_avg,
            'price_min' => $item->price_min,
            'price_max' => $item->price_max,
            'market' => $item->market,
        ];
        if ($row) {
            $row->save($payload);
        } else {
            QuoteProductPrice::create($payload);
        }
    }

    public function destroy($resource, $id): Response
    {
        if ($e = $this->refuseClosedModule($resource)) return $e;
        $cfg = $this->cfg($resource);
        $allow = !empty($cfg['create']) || !empty($cfg['destroy'])
            || in_array($resource, ['pages','hotwords','friend-links','announcements','banners','ads','categories','company-news','company-honors','company-pages','company-links'], true);
        if (!$allow) return json_error('Forbidden', 403);
        $admin = $this->request->user ?? null;
        if ($resource === 'categories') {
            if (!$admin || empty($admin->id)) {
                return json_error('请先登录', 401);
            }
            if (!\app\common\AdminSecondPassword::isSet()) {
                return json_error('请先在「站点设置」中设置二级密码后再删除', 423);
            }
        } else {
            try {
                \app\common\AdminSecondPassword::assertCanDelete($admin);
            } catch (\RuntimeException $e) {
                return json_error($e->getMessage(), (int) ($e->getCode() ?: 403));
            }
        }
        $model = $cfg['model'];
        $item = $model::find($id);
        if (!$item) return json_error('Not found', 404);
        if ($resource === 'categories') {
            $password = trim((string) $this->request->param('password', ''));
            if ($password === '' || !\app\common\AdminSecondPassword::check($password)) {
                return json_error('删除分类每次都要输入正确的二级密码', 428);
            }
            $this->ensureCategoryIconColumn();
            $this->recycleCategoryTree($item, (int) ($admin->id ?? 0));
            $this->bustHomeCache($resource);
            return json_message('已移入回收站');
        }
        $payload = $item->toArray();
        if ($resource === 'quote-products') {
            $qid = (int) $item->id;
            \app\model\QuoteProductPrice::where('product_id', $qid)->delete();
            \app\model\QuoteRegionPrice::where('product_id', $qid)->delete();
            \app\common\QuoteAdSlots::deactivate($qid);
            $this->bustQuoteCache();
        }
        \app\common\AdminRecycle::push($resource, $payload, (int) ($admin->id ?? 0));
        $item->delete();
        $this->bustHomeCache($resource);
        return json_message('已移入回收站');
    }

    public function importQuotePrices(): Response
    {
        $file = $this->request->file('file');
        if (!$file) {
            return json_error('请选择要上传的表格', 422);
        }
        $origName = method_exists($file, 'getOriginalName') ? (string) $file->getOriginalName() : (string) $file->getFilename();
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $size = method_exists($file, 'getSize') ? (int) $file->getSize() : 0;
        if ($size <= 0 || $size > 5 * 1024 * 1024) {
            return json_error('表格不能超过 5MB', 422);
        }
        try {
            $productId = (int) $this->request->post('product_id', 0);
            $defaultYear = (int) $this->request->post('year', 0);
            if ($defaultYear < 1990 || $defaultYear > 2100) {
                return json_error('请指定要导入的年份（year），避免误写入或误清其它年份', 422);
            }
            $result = \app\common\QuotePriceImport::fromFile($file->getPathname(), $ext, $productId, $defaultYear);
        } catch (\InvalidArgumentException $e) {
            return json_error($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return json_error('导入失败，请确认是 xlsx 或 csv', 422);
        }
        $this->bustQuoteCache();

        return json(['data' => $result, 'message' => '导入完成']);
    }

    public function quotePrices($id): Response
    {
        $product = \app\model\QuoteProduct::find($id);
        if (!$product) {
            return json_error('Not found', 404);
        }
        $rows = \app\model\QuoteProductPrice::where('product_id', (int) $id)
            ->order('quoted_on', 'desc')
            ->limit(2000)
            ->select();
        $list = [];
        foreach ($rows as $row) {
            $list[] = [
                'id' => (int) $row->id,
                'quoted_on' => substr((string) $row->quoted_on, 0, 10),
                'price_avg' => $row->price_avg,
                'price_min' => $row->price_min,
                'price_max' => $row->price_max,
            ];
        }

        return json(['data' => [
            'product' => ['id' => (int) $product->id, 'name' => $product->name],
            'prices' => $list,
        ]]);
    }

    public function quoteOverview(): Response
    {
        $products = \app\model\QuoteProduct::field('id,is_active')->select();
        $active = 0;
        $ids = [];
        foreach ($products as $p) {
            $ids[] = (int) $p->id;
            if ((int) $p->is_active === 1) {
                $active++;
            }
        }
        $moves = $this->quoteLatestMoves($ids);
        $up = 0;
        $down = 0;
        foreach ($moves as $pair) {
            if (count($pair) < 2) {
                continue;
            }
            $diff = $pair[0] - $pair[1];
            if ($diff > 0.004) {
                $up++;
            } elseif ($diff < -0.004) {
                $down++;
            }
        }

        return json(['data' => [
            'products' => count($ids),
            'active' => $active,
            'inactive' => count($ids) - $active,
            'records' => (int) \app\model\QuoteProductPrice::count(),
            'region_records' => $this->quoteRegionCount(),
            'up' => $up,
            'down' => $down,
        ]]);
    }

    public function quoteBoard($id): Response
    {
        $product = \app\model\QuoteProduct::find($id);
        if (!$product) {
            return json_error('Not found', 404);
        }
        $pid = (int) $product->id;
        $byDate = [];
        $regionNames = [];
        foreach (\app\model\QuoteProductPrice::where('product_id', $pid)->order('quoted_on', 'asc')->select() as $row) {
            $date = substr((string) $row->quoted_on, 0, 10);
            $byDate[$date]['id'] = (int) $row->id;
            $byDate[$date]['avg'] = $row->price_avg !== null ? round((float) $row->price_avg, 2) : null;
            $byDate[$date]['min'] = $row->price_min !== null ? round((float) $row->price_min, 2) : null;
            $byDate[$date]['max'] = $row->price_max !== null ? round((float) $row->price_max, 2) : null;
        }
        foreach (\app\model\QuoteRegionPrice::where('product_id', $pid)->order('quoted_on', 'asc')->select() as $row) {
            $date = substr((string) $row->quoted_on, 0, 10);
            $name = trim((string) $row->region);
            if ($name === '') {
                continue;
            }
            $regionNames[$name] = true;
            $byDate[$date]['regions'][$name] = round((float) $row->price, 2);
        }
        ksort($byDate);
        $years = [];
        foreach (array_keys($byDate) as $date) {
            $years[(int) substr($date, 0, 4)] = true;
        }
        $yearList = array_map('intval', array_keys($years));
        sort($yearList);
        $latestDate = $byDate ? (string) array_key_last($byDate) : date('Y-m-d');
        $nowY = (int) date('Y');
        $year = (int) $this->request->get('year', 0);
        // 允许切到尚无数据的年份（导入要用）；仅缺参/非法时才回落到有数据的最近年
        // 上限放宽：页面可手动添加未来年份后再导入
        if ($year < 2016 || $year > $nowY + 30) {
            $year = $yearList ? (int) end($yearList) : (int) substr($latestDate, 0, 4);
            if ($year < 2016) {
                $year = $nowY;
            }
        }
        // 默认下拉：2016～当前年（不含未来年）；另含有数据的年份、当前选中年
        $selectYears = $yearList;
        $yearFrom = 2016;
        $yearTo = $nowY;
        for ($y = $yearFrom; $y <= $yearTo; $y++) {
            if (!in_array($y, $selectYears, true)) {
                $selectYears[] = $y;
            }
        }
        if ($year >= 2016 && $year <= $nowY + 30 && !in_array($year, $selectYears, true)) {
            $selectYears[] = $year;
        }
        rsort($selectYears);
        $span = (string) $this->request->get('span', 'month');
        if (!in_array($span, ['day', 'month', 'year'], true)) {
            $span = 'month';
        }
        $month = (int) $this->request->get('month', 0);
        if ($month < 1 || $month > 12) {
            $month = $byDate ? (int) substr($latestDate, 5, 2) : (int) date('n');
        }
        $dates = array_keys($byDate);
        if ($span === 'year') {
            $prefix = sprintf('%04d-', $year);
            $window = array_values(array_filter($dates, static fn ($d) => str_starts_with($d, $prefix)));
        } elseif ($span === 'day') {
            $prefix = sprintf('%04d-%02d-', $year, $month);
            $inMonth = array_values(array_filter($dates, static fn ($d) => str_starts_with($d, $prefix)));
            $window = $inMonth ? [end($inMonth)] : [];
        } else {
            $prefix = sprintf('%04d-%02d-', $year, $month);
            $window = array_values(array_filter($dates, static fn ($d) => str_starts_with($d, $prefix)));
        }
        $series = [];
        $table = [];
        foreach ($window as $date) {
            $cell = $byDate[$date] ?? [];
            $regs = $cell['regions'] ?? [];
            $avg = $cell['avg'] ?? null;
            if (($avg === null || (float) $avg <= 0) && $regs) {
                $avg = round(array_sum($regs) / count($regs), 2);
            }
            $min = $cell['min'] ?? ($regs ? min($regs) : null);
            $max = $cell['max'] ?? ($regs ? max($regs) : null);
            if ($avg !== null && (float) $avg > 0) {
                $series[] = ['date' => $date, 'price' => round((float) $avg, 2)];
            }
            $table[] = [
                'date' => $date,
                'regions' => $regs ?: new \stdClass(),
                'min' => $min,
                'max' => $max,
                'avg' => $avg !== null ? round((float) $avg, 2) : null,
            ];
        }
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $prefix = sprintf('%04d-%02d-', $year, $m);
            $days = 0;
            foreach ($dates as $date) {
                if (str_starts_with($date, $prefix)) {
                    $days++;
                }
            }
            $months[] = ['month' => $m, 'days' => $days];
        }
        $prices = array_column($series, 'price');
        $latest = $prices ? (float) $prices[count($prices) - 1] : 0.0;
        $first = $prices ? (float) $prices[0] : 0.0;
        $diff = round($latest - $first, 2);
        $mins = array_values(array_filter(array_column($table, 'min'), static fn ($v) => $v !== null && (float) $v > 0));
        $maxs = array_values(array_filter(array_column($table, 'max'), static fn ($v) => $v !== null && (float) $v > 0));

        return json(['data' => [
            'product' => [
                'id' => $pid,
                'name' => (string) $product->name,
                'market' => (string) ($product->market ?? ''),
                'unit' => trim((string) ($product->unit ?? '')) ?: '元/吨',
                'quoted_on' => substr((string) ($product->quoted_on ?? ''), 0, 10),
                'price_avg' => $product->price_avg !== null ? round((float) $product->price_avg, 2) : null,
            ],
            'years' => $selectYears,
            'year' => $year,
            'month' => $month,
            'span' => $span,
            'months' => $months,
            'regions' => array_keys($regionNames),
            'summary' => [
                'latest' => $prices ? $latest : null,
                'latest_on' => $series ? $series[count($series) - 1]['date'] : '',
                'avg' => $prices ? round(array_sum($prices) / count($prices), 2) : null,
                'min' => $mins ? round((float) min($mins), 2) : null,
                'max' => $maxs ? round((float) max($maxs), 2) : null,
                'change' => $prices ? $diff : null,
                'change_pct' => ($prices && $first != 0.0) ? round($diff / $first * 100, 2) : null,
                'days' => count($series),
            ],
            'series' => $series,
            'rows' => $table,
        ]]);
    }

    private function quoteRegionCount(): int
    {
        try {
            return (int) \app\model\QuoteRegionPrice::count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** @param list<int> $ids @return array<int, list<float>> */
    private function quoteLatestMoves(array $ids): array
    {
        $latest = [];
        if (!$ids) {
            return $latest;
        }
        $rows = \app\model\QuoteProductPrice::whereIn('product_id', $ids)
            ->order('quoted_on', 'desc')
            ->field('product_id,quoted_on,price_avg')
            ->select();
        foreach ($rows as $row) {
            $pid = (int) $row->product_id;
            if (!isset($latest[$pid])) {
                $latest[$pid] = [];
            }
            if (count($latest[$pid]) >= 2) {
                continue;
            }
            if ($row->price_avg === null || (float) $row->price_avg <= 0) {
                continue;
            }
            $latest[$pid][] = (float) $row->price_avg;
        }

        return $latest;
    }

    /** @param list<array<string,mixed>> $data */
    private function decorateQuoteRows(array $data): array
    {
        $ids = [];
        foreach ($data as $row) {
            $ids[] = (int) ($row['id'] ?? 0);
        }
        $moves = $this->quoteLatestMoves(array_values(array_filter($ids)));
        foreach ($data as &$row) {
            $pair = $moves[(int) ($row['id'] ?? 0)] ?? [];
            $cur = $pair[0] ?? (float) ($row['price_avg'] ?? 0);
            $prev = $pair[1] ?? $cur;
            $diff = round($cur - $prev, 2);
            $pct = $prev != 0.0 ? round($diff / $prev * 100, 2) : 0.0;
            $row['price_text'] = $cur > 0 ? '¥'.$this->quoteMoney($cur).' / 吨' : '—';
            if (count($pair) < 2 || abs($diff) < 0.005) {
                $row['day_change'] = '—';
                $row['trend_label'] = '持平';
            } elseif ($diff > 0) {
                $row['day_change'] = '+'.$this->quoteMoney($diff).' (+'.$pct.'%)';
                $row['trend_label'] = '上涨';
            } else {
                $row['day_change'] = $this->quoteMoney($diff).' ('.$pct.'%)';
                $row['trend_label'] = '下跌';
            }
        }
        unset($row);

        return $data;
    }

    private function quoteMoney(float $n): string
    {
        $neg = $n < 0;
        $n = abs($n);
        $text = abs($n - round($n)) < 0.001
            ? number_format($n, 0, '.', ',')
            : number_format($n, 2, '.', ',');

        return ($neg ? '-' : '').$text;
    }

    public function saveQuotePrice($id): Response
    {
        $product = \app\model\QuoteProduct::find($id);
        if (!$product) {
            return json_error('Not found', 404);
        }
        $date = $this->quoteDate((string) $this->request->post('quoted_on', ''));
        if ($date === '') {
            return json_error('日期请写成 2026-09-29', 422);
        }
        $avg = $this->quoteNum($this->request->post('price_avg'));
        if ($avg === null) {
            return json_error('均价要填数字', 422);
        }
        $payload = [
            'product_id' => (int) $id,
            'quoted_on' => $date,
            'price_avg' => $avg,
            'price_min' => $this->quoteNum($this->request->post('price_min')),
            'price_max' => $this->quoteNum($this->request->post('price_max')),
            'market' => $product->market,
        ];
        $rowId = (int) $this->request->post('id', 0);
        if ($rowId > 0) {
            $row = \app\model\QuoteProductPrice::where('product_id', (int) $id)->where('id', $rowId)->find();
            if (!$row) {
                return json_error('这条记录不存在', 404);
            }
            $clash = \app\model\QuoteProductPrice::where('product_id', (int) $id)
                ->where('quoted_on', $date)
                ->where('id', '<>', $rowId)
                ->find();
            if ($clash) {
                return json_error('这一天已经有价格了', 422);
            }
            $row->save($payload);
        } else {
            $row = \app\model\QuoteProductPrice::where('product_id', (int) $id)->where('quoted_on', $date)->find();
            if ($row) {
                $row->save($payload);
            } else {
                \app\model\QuoteProductPrice::create($payload);
            }
        }
        $this->syncQuoteProductFromHistory((int) $id);
        $this->bustQuoteCache();

        return json_message('已保存');
    }

    public function destroyQuotePrice($id, $priceId): Response
    {
        $admin = $this->request->user ?? null;
        try {
            \app\common\AdminSecondPassword::assertCanDelete($admin);
        } catch (\RuntimeException $e) {
            return json_error($e->getMessage(), (int) ($e->getCode() ?: 403));
        }
        $row = \app\model\QuoteProductPrice::where('product_id', (int) $id)->where('id', (int) $priceId)->find();
        if (!$row) {
            return json_error('这条记录不存在', 404);
        }
        \app\common\AdminRecycle::push('quote-product-prices', $row->toArray(), (int) ($admin->id ?? 0));
        $row->delete();
        $this->syncQuoteProductFromHistory((int) $id);
        $this->bustQuoteCache();

        return json_message('已移入回收站');
    }

    /** 清除本品种指定年份的日价与地区价（必须带 year），需二级密码。 */
    public function clearQuotePrices($id): Response
    {
        $admin = $this->request->user ?? null;
        try {
            \app\common\AdminSecondPassword::assertCanDelete($admin);
        } catch (\RuntimeException $e) {
            return json_error($e->getMessage(), (int) ($e->getCode() ?: 403));
        }
        $product = \app\model\QuoteProduct::find($id);
        if (!$product) {
            return json_error('Not found', 404);
        }
        $year = (int) ($this->request->param('year') ?: $this->request->post('year') ?: 0);
        if ($year < 1990 || $year > 2100) {
            return json_error('请指定要清除的年份（year）', 422);
        }
        $pid = (int) $id;
        $from = sprintf('%04d-01-01', $year);
        $to = sprintf('%04d-12-31', $year);
        $days = (int) \app\model\QuoteProductPrice::where('product_id', $pid)
            ->whereBetween('quoted_on', [$from, $to])
            ->count();
        $regions = (int) \app\model\QuoteRegionPrice::where('product_id', $pid)
            ->whereBetween('quoted_on', [$from, $to])
            ->count();
        \app\model\QuoteProductPrice::where('product_id', $pid)
            ->whereBetween('quoted_on', [$from, $to])
            ->delete();
        \app\model\QuoteRegionPrice::where('product_id', $pid)
            ->whereBetween('quoted_on', [$from, $to])
            ->delete();
        $this->syncQuoteProductFromHistory($pid);
        $this->bustQuoteCache();

        return json([
            'message' => '已清除 '.$year.' 年报价数据',
            'data' => [
                'year' => $year,
                'days' => $days,
                'region_rows' => $regions,
            ],
        ]);
    }

    private function syncQuoteProductFromHistory(int $productId): void
    {
        $product = \app\model\QuoteProduct::find($productId);
        if (!$product) {
            return;
        }
        $latest = \app\model\QuoteProductPrice::where('product_id', $productId)->order('quoted_on', 'desc')->find();
        if (!$latest) {
            $product->save([
                'price_avg' => null,
                'price_min' => null,
                'price_max' => null,
                'quoted_on' => null,
            ]);

            return;
        }
        $product->save([
            'price_avg' => $latest->price_avg,
            'price_min' => $latest->price_min,
            'price_max' => $latest->price_max,
            'quoted_on' => substr((string) $latest->quoted_on, 0, 10),
        ]);
    }

    private function quoteDate(string $raw): string
    {
        $raw = trim(str_replace(['.', '/'], '-', $raw));
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
        }

        return '';
    }

    private function quoteNum($raw): ?float
    {
        $text = trim(str_replace([',', '，', '元', ' '], '', (string) $raw));
        if ($text === '' || !is_numeric($text)) {
            return null;
        }

        return round((float) $text, 2);
    }

    private function bustQuoteCache(): void
    {
        $this->deleteHomeCacheKeys();
    }

    /** 供求/广告改动后清首页缓存，避免轮播与标记延迟 */
    private function bustHomeCache(string $resource): void
    {
        if (!in_array($resource, ['supplies', 'purchases', 'banners', 'ads', 'articles', 'companies', 'products', 'categories'], true)) {
            return;
        }
        $this->deleteHomeCacheKeys();
    }

    private function deleteHomeCacheKeys(): void
    {
        // Home::index 当前键为 api:home:v33；旧键一并清，避免漏清导致「已上架但前台看不到」
        $keys = [
            'api:home:v14',
            'api:home:v15',
            'api:home:v27',
            'api:home:v28',
            'api:home:v29',
            'api:home:v33',
        ];
        try {
            foreach ($keys as $key) {
                Cache::delete($key);
            }
        } catch (\Throwable $e) {
            // ignore
        }
    }

    public function options($relation): Response
    {
        if ($relation === 'category') {
            return json_data($this->categoryOptions());
        }
        $q = trim((string)$this->request->param('q', ''));
        $limit = min(200, max(5, (int)$this->request->param('limit', 30)));
        if ($relation === 'quoteProduct') {
            $this->ensureArticleQuoteProductColumn();
            $query = QuoteProduct::order('sort', 'asc')->order('id', 'asc')->limit($limit);
            if ($q !== '') {
                // 支持短码：DMC / D4 / 生胶 等
                $aliases = [
                    'DMC' => '二甲基环硅氧烷',
                    'dmc' => '二甲基环硅氧烷',
                    'D4' => '八甲基环四硅氧烷',
                    'd4' => '八甲基环四硅氧烷',
                    '生胶' => '甲基乙烯基硅橡胶',
                    '107胶' => '107室温硫化硅橡胶',
                    '混炼胶' => '硅橡胶混炼胶',
                    '气相白炭黑' => '气相法二氧化硅',
                    '沉淀白炭黑' => '沉淀二氧化硅',
                    '裂解料硅油' => '裂解料二甲基硅油',
                    '外资硅油' => '外资品牌二甲基硅油',
                ];
                $kw = $aliases[$q] ?? $q;
                $query->where(function ($w) use ($q, $kw) {
                    $w->whereLike('name', '%'.$q.'%');
                    if ($kw !== $q) {
                        $w->whereOr('name', 'like', '%'.$kw.'%');
                    }
                });
            } else {
                try {
                    $query->where('is_active', 1);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            $rows = [];
            foreach ($query->select() as $row) {
                $rows[] = [
                    'id' => (int) $row->id,
                    'label' => trim((string) ($row->name ?? '')).' #'.$row->id,
                ];
            }

            return json_data($rows);
        }
        $map = [
            'user' => [User::class, ['name','username','company_name','mobile','email']],
            'toUser' => [User::class, ['name','username','company_name','mobile','email']],
            'company' => [Company::class, ['name','slug','contact','phone']],
            'product' => [Product::class, ['title']],
            'project' => [InvestProject::class, ['title']],
            'gift' => [\app\model\Gift::class, ['title']],
            'special' => [\app\model\Special::class, ['name', 'slug']],
            'grade' => [VipGrade::class, ['name']],
        ];
        if (!isset($map[$relation])) return json_error('关联不存在', 404);
        [$model, $search] = $map[$relation];
        $query = $model::order('id', 'desc')->limit($limit);
        // 询盘「接收企业用户」：只列已审核通过的企业账号；按 ID 精确查时放行（回显已绑定）
        if ($relation === 'toUser') {
            $exactId = ($q !== '' && ctype_digit($q)) ? (int) $q : 0;
            if ($exactId > 0) {
                $query->where('id', $exactId);
            } else {
                $approvedIds = Company::where('status', 'approved')
                    ->where('user_id', '>', 0)
                    ->column('user_id');
                $approvedIds = array_values(array_unique(array_filter(array_map('intval', $approvedIds ?: []))));
                if ($approvedIds === []) {
                    return json_data([]);
                }
                $query->whereIn('id', $approvedIds);
                if ($q !== '') {
                    $kw = '%'.$q.'%';
                    $query->where(function ($w) use ($search, $kw) {
                        foreach ($search as $i => $col) {
                            $i === 0 ? $w->whereLike($col, $kw) : $w->whereOr($col, 'like', $kw);
                        }
                    });
                }
            }
        } elseif ($q !== '') {
            $kw = '%'.$q.'%';
            $query->where(function ($w) use ($search, $kw) {
                foreach ($search as $i => $col) {
                    $i === 0 ? $w->whereLike($col, $kw) : $w->whereOr($col, 'like', $kw);
                }
            });
        }
        $rows = [];
        foreach ($query->select() as $row) {
            $label = match ($relation) {
                'user','toUser' => trim(($row->company_name ?: $row->name ?: $row->username).' #'.$row->id),
                'company' => trim(($row->name ?: $row->slug).' #'.$row->id),
                'product','project','gift' => ($row->title ?? $row->name).' #'.$row->id,
                'special' => trim(($row->name ?: $row->slug).' #'.$row->id),
                'grade' => ($row->name ?? '').' #'.$row->id,
                default => '#'.$row->id,
            };
            $rows[] = ['id' => (int)$row->id, 'label' => $label];
        }
        return json_data($rows);
    }

    private function ensureArticleQuoteProductColumn(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        try {
            $cols = \think\facade\Db::query('SHOW COLUMNS FROM `articles` LIKE \'quote_product_id\'');
            if ($cols) {
                return;
            }
            \think\facade\Db::execute(
                'ALTER TABLE `articles` ADD COLUMN `quote_product_id` INT UNSIGNED NULL DEFAULT NULL AFTER `type`, ADD INDEX `idx_articles_quote_product` (`quote_product_id`)'
            );
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /** 删除父级时，子分类先进入回收站再删，恢复时按条还原。 */
    private function recycleCategoryTree($item, int $adminId): void
    {
        $children = \app\model\Category::where('parent_id', (int) $item->id)->select();
        foreach ($children as $child) {
            $this->recycleCategoryTree($child, $adminId);
        }
        \app\common\AdminRecycle::push('categories', $item->toArray(), $adminId);
        $item->delete();
    }

    private function ensureCategoryIconColumn(): void
    {
        try {
            $cols = \think\facade\Db::query("SHOW COLUMNS FROM categories LIKE 'icon'");
            if ($cols) {
                return;
            }
            \think\facade\Db::execute("ALTER TABLE categories ADD COLUMN icon varchar(32) NULL DEFAULT NULL");
        } catch (\Throwable $e) {
        }
    }

    /** 分类父级下拉：按模块列出已有分类，方便点选，不用手填 ID。 */
    private function categoryOptions(): array
    {
        $module = trim((string) $this->request->param('module', ''));
        $query = \app\model\Category::order('sort', 'asc')->order('id', 'asc');
        if ($module !== '') {
            $query->where('module', $module);
        }
        $limit = min(500, max(20, (int) $this->request->param('limit', 400)));
        $all = [];
        foreach ($query->limit($limit)->select() as $row) {
            $all[] = $row;
        }
        $byId = [];
        foreach ($all as $row) {
            $byId[(int) $row->id] = $row;
        }
        $rows = [];
        foreach ($all as $row) {
            $name = (string) ($row->name ?? '');
            $pid = $row->parent_id ? (int) $row->parent_id : 0;
            if ($pid && isset($byId[$pid])) {
                $parentName = trim((string) ($byId[$pid]->name ?? ''));
                if ($parentName !== '') {
                    $name = $parentName.' › '.$name;
                }
            }
            $rows[] = [
                'id' => (int) $row->id,
                'label' => $name,
                'parent_id' => $pid ?: null,
                'module' => (string) ($row->module ?? ''),
            ];
        }

        return $rows;
    }

    /** 询盘从「待审」转为「已转发」时，通知企业用户（发件人填询盘方，便于信件一键回复） */
    private function forwardInquiryToEnterprise($item, string $prevStatus): void
    {
        $status = (string) ($item->status ?? '');
        $to = (int) ($item->to_user_id ?? 0);
        if ($status !== 'processing' || $to <= 0) {
            return;
        }
        if (in_array($prevStatus, ['processing', 'reply_pending', 'replied'], true)) {
            return;
        }
        if ($prevStatus !== 'new' && $prevStatus !== '') {
            // 结案后再改回已转发也补发一次通知
            if (!in_array($prevStatus, ['done', 'closed'], true)) {
                return;
            }
        }
        $contact = trim((string) ($item->contact_name ?? ''));
        $phone = trim((string) ($item->contact_phone ?? ''));
        $body = (string) ($item->content ?? '');
        if ($contact !== '' || $phone !== '') {
            $body = '联系人：'.$contact.' / '.$phone."\n".$body;
        }
        $from = (int) ($item->user_id ?? 0);
        \app\model\Message::create([
            'user_id' => $to,
            'from_user_id' => $from > 0 ? $from : null,
            'title' => '收到询盘：'.(string) ($item->title ?? ''),
            'content' => $body,
            'is_read' => 0,
        ]);
    }

    /** 企业回复审核通过后，通知询盘方 */
    private function deliverInquiryReplyToBuyer($item, string $prevStatus): void
    {
        $status = (string) ($item->status ?? '');
        if ($status !== 'replied' || $prevStatus === 'replied') {
            return;
        }
        if ($prevStatus !== 'reply_pending' && $prevStatus !== 'processing') {
            return;
        }
        $buyer = (int) ($item->user_id ?? 0);
        $ent = (int) ($item->to_user_id ?? 0);
        $reply = trim((string) ($item->reply ?? ''));
        if ($buyer <= 0 || $reply === '') {
            return;
        }
        if (empty($item->replied_at)) {
            $item->replied_at = date('Y-m-d H:i:s');
            $item->save();
        }
        \app\model\Message::create([
            'user_id' => $buyer,
            'from_user_id' => $ent > 0 ? $ent : null,
            'title' => '询盘回复：'.(string) ($item->title ?? ''),
            'content' => $reply,
            'is_read' => 0,
        ]);
    }

    private function approveVip($app): void
    {
        $userId = $app->user_id ?? null;
        if (!$userId) {
            return;
        }
        $user = User::find($userId);
        if (!$user) {
            return;
        }
        $gradeId = (int) ($app->vip_grade_id ?? $app->grade_id ?? 0);
        $grade = $gradeId ? VipGrade::find($gradeId) : null;
        $level = $grade ? (int) $grade->level : 2;
        $code = $grade ? (string) $grade->code : 'gold';
        if ($code === 'free' || (float) ($grade->price_year ?? 0) <= 0) {
            return;
        }
        $wasCertified = (bool) ($user->is_certified ?? false);
        $user->save([
            'level' => max((int) $user->level, $level),
            'vip_grade_code' => $code,
            'vip_expire_at' => date('Y-m-d H:i:s', strtotime('+1 year')),
            'group_code' => 'vip',
            'status' => 'active',
            'is_certified' => 1,
            'member_type' => $user->member_type === 'personal' ? 'company' : ($user->member_type ?: 'company'),
        ]);
        if (!$wasCertified) {
            \app\common\VipEntitlement::grantCertBonus(User::find($userId) ?: $user);
        }
        \app\common\VipEntitlement::rewardInviteUpgrade(User::find($userId) ?: $user);
    }

    private function approveVipPay($order, int $adminId = 0): void
    {
        $this->approveVip($order);
        try {
            $order->status = 'approved';
            $order->reviewed_at = date('Y-m-d H:i:s');
            if ($adminId > 0) {
                $order->reviewed_by = $adminId;
            }
            $order->save();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    private function rejectVipPay($order, int $adminId = 0): void
    {
        try {
            $order->status = 'rejected';
            $order->reviewed_at = date('Y-m-d H:i:s');
            if ($adminId > 0) {
                $order->reviewed_by = $adminId;
            }
            $order->save();
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /** 提现驳回：退回冻结余额 */
    private function refundWithdraw($row): void
    {
        $amount = round((float) ($row->amount ?? 0), 2);
        if ($amount <= 0) {
            return;
        }
        $user = User::find((int) $row->user_id);
        if (!$user) {
            return;
        }
        $user->balance = round((float) $user->balance + $amount, 2);
        $user->save();
        \app\model\FinanceRecord::create([
            'user_id' => (int) $user->id,
            'type' => 'cash',
            'amount' => $amount,
            'balance_after' => $user->balance,
            'title' => '提现驳回退回',
            'remark' => '申请 #' . $row->id,
        ]);
    }

    private function allCfg(): array
    {
        $resources = include root_path().'config/admin_resources.php';
        $fieldsAll = include root_path().'config/admin_resource_fields.php';
        foreach ($resources as $key => &$cfg) {
            $fields = $fieldsAll[$key] ?? null;
            if (!is_array($fields) || $fields === []) {
                continue;
            }
            $cfg['fields'] = array_values($fields);
            if ($key === 'ads') {
                try {
                    foreach ($cfg['fields'] as &$field) {
                        if (($field['name'] ?? '') !== 'position') {
                            continue;
                        }
                        $opts = is_array($field['options'] ?? null) ? $field['options'] : [];
                        foreach (\app\model\AdPlace::where('is_active', 1)->order('sort', 'asc')->select() as $place) {
                            $opts[(string) $place->code] = (string) $place->name;
                        }
                        $field['options'] = $opts;
                    }
                    unset($field);
                } catch (\Throwable $e) {
                }
            }
            $editable = [];
            $fillable = [];
            foreach ($fields as $f) {
                if (!empty($f['readonly']) || !empty($f['virtual'])) {
                    continue;
                }
                $name = $f['name'] ?? '';
                if ($name === '') {
                    continue;
                }
                $editable[] = $name;
                if (empty($f['edit_only'])) {
                    $fillable[] = $name;
                }
            }
            $cfg['editable'] = array_values(array_unique(array_merge($cfg['editable'] ?? [], $editable)));
            $cfg['fillable'] = array_values(array_unique(array_merge($cfg['fillable'] ?? [], $fillable)));
        }
        unset($cfg);
        return $resources;
    }

    private function cfg(string $resource): array
    {
        $all = $this->allCfg();
        if (!isset($all[$resource])) {
            throw new \think\exception\HttpException(404, '资源不存在');
        }
        return $all[$resource];
    }

    private function serialize($row, array $cfg): array
    {
        $arr = $row->toArray();
        unset($arr['password'], $arr['pay_password'], $arr['remember_token']);
        foreach ($cfg['columns'] ?? [] as $col) {
            $key = $col['key'] ?? '';
            if (str_contains($key, '.')) {
                $parts = explode('.', $key);
                $val = $arr;
                foreach ($parts as $p) {
                    $val = is_array($val) ? ($val[$p] ?? null) : null;
                }
                $arr[$key] = $val;
            }
        }
        if (($cfg['model'] ?? null) === Company::class || ($row instanceof Company)) {
            $cap = trim((string) ($arr['capital'] ?? ''));
            $unit = trim((string) ($arr['capital_unit'] ?? ''));
            $arr['capital_display'] = $cap === '' ? null : ($unit !== '' ? $cap.$unit : $cap);
            $imgs = \app\common\ApiResource::decodeEnvImages($arr['env_images'] ?? null);
            for ($i = 1; $i <= 8; $i++) {
                $arr['env_image'.$i] = $imgs[$i - 1] ?? '';
            }
            $arr['env_images'] = $imgs;
            if (!empty($arr['user']) && is_array($arr['user'])) {
                $arr['user_mobile_verified'] = !empty($arr['user']['mobile_verified']);
                $arr['user_bank_verified'] = !empty($arr['user']['bank_verified']);
                $arr['user_truename_verified'] = !empty($arr['user']['truename_verified']);
                $arr['user_vip_expire_at'] = $arr['user']['vip_expire_at'] ?? null;
                $arr['user_group_code'] = $arr['user']['group_code'] ?? ($arr['user']['member_type'] ?? null);
                $arr['user_level'] = $arr['user']['level'] ?? null;
            }
            if (empty($arr['expires_display'])) {
                // noop
            }
        }
        if (isset($arr['expires_at'])) {
            $arr['expires_display'] = $arr['expires_at'] ?: '永久';
        }
        if ($row instanceof \app\model\Article) {
            $status = (string) ($arr['status'] ?? '');
            $arr['audit_label'] = match ($status) {
                'published', 'offline' => '通过',
                'pending' => '待审',
                'rejected' => '拒绝',
                'draft' => '草稿',
                default => $status !== '' ? $status : '—',
            };
            $arr['shelf_label'] = match ($status) {
                'published' => '上架',
                'offline' => '下架',
                default => '未上架',
            };
            $qpName = '';
            if (!empty($arr['quoteProduct']) && is_array($arr['quoteProduct'])) {
                $qpName = (string) ($arr['quoteProduct']['name'] ?? '');
            } elseif (!empty($arr['quote_product_id'])) {
                try {
                    $qp = QuoteProduct::find((int) $arr['quote_product_id']);
                    $qpName = $qp ? (string) ($qp->name ?? '') : '';
                } catch (\Throwable $e) {
                    $qpName = '';
                }
            }
            $arr['quote_product_name'] = $qpName !== '' ? $qpName : '—';
        }
        if ($row instanceof \app\model\Inquiry) {
            $toId = (int) ($arr['to_user_id'] ?? 0);
            $toLabel = '—';
            if ($toId > 0) {
                try {
                    $to = User::find($toId);
                    if ($to) {
                        $toLabel = trim((string) ($to->company_name ?: $to->name ?: $to->username ?: '')).' #'.$toId;
                    } else {
                        $toLabel = '#'.$toId;
                    }
                    $co = Company::where('user_id', $toId)->order('id', 'desc')->find();
                    if ($co && trim((string) ($co->name ?? '')) !== '') {
                        $toLabel = trim((string) $co->name).' #'.$toId;
                    }
                } catch (\Throwable $e) {
                    $toLabel = '#'.$toId;
                }
            }
            $arr['to_user_label'] = $toLabel;
        }
        return $arr;
    }

    /** 广告列表表头筛选：ID、标题、位置、类型、启用。 */
    /** 列表筛选项：配置 filters + 列上常见枚举字段自动补齐（不含 status，status 走独立参数）。 */
    private function resolveFilters(array $cfg, string $resource = ''): array
    {
        $out = [];
        foreach ($cfg['filters'] ?? [] as $key => $f) {
            if (!is_array($f)) {
                continue;
            }
            $field = (string) ($f['field'] ?? $key);
            if ($field === '' || str_contains($field, '.')) {
                continue;
            }
            $out[$field] = [
                'field' => $field,
                'label' => (string) ($f['label'] ?? $field),
                'type' => (string) ($f['type'] ?? (!empty($f['options']) ? 'select' : 'text')),
                'options' => is_array($f['options'] ?? null) ? $this->optionsAsObject($f['options']) : new \stdClass(),
                'bool' => !empty($f['bool']),
            ];
        }
        $enumPresets = $this->enumFilterPresets();
        foreach ($cfg['columns'] ?? [] as $col) {
            $key = (string) ($col['key'] ?? '');
            if ($key === '' || str_contains($key, '.') || isset($out[$key])) {
                continue;
            }
            if ($key === ($cfg['status']['field'] ?? 'status')) {
                continue;
            }
            if (isset($enumPresets[$key])) {
                $out[$key] = [
                    'field' => $key,
                    'label' => (string) ($col['label'] ?? $key),
                    'type' => 'select',
                    'options' => $this->optionsAsObject($enumPresets[$key]),
                    'bool' => str_starts_with($key, 'is_'),
                ];
            } elseif (in_array($key, ['title', 'name', 'username', 'id'], true)) {
                $out[$key] = [
                    'field' => $key,
                    'label' => (string) ($col['label'] ?? $key),
                    'type' => 'text',
                    'options' => new \stdClass(),
                    'bool' => false,
                ];
            }
        }
        // 字段表里带 options 的也并入
        foreach ($cfg['fields'] ?? [] as $f) {
            $name = (string) ($f['name'] ?? '');
            if ($name === '' || str_contains($name, '.') || isset($out[$name])) {
                continue;
            }
            if ($name === ($cfg['status']['field'] ?? 'status')) {
                continue;
            }
            if (!empty($f['options']) && is_array($f['options'])) {
                $out[$name] = [
                    'field' => $name,
                    'label' => (string) ($f['label'] ?? $name),
                    'type' => 'select',
                    'options' => $this->optionsAsObject($f['options']),
                    'bool' => ($f['type'] ?? '') === 'switch' || str_starts_with($name, 'is_'),
                ];
            }
        }
        if ($resource === 'ads' && isset($out['position']) && empty((array) ($out['position']['options'] ?? []))) {
            // position options 可能在 fields 里动态补过
        }
        if ($resource === 'articles') {
            $out = ['category_id' => [
                'field' => 'category_id',
                'label' => '分类',
                'type' => 'select',
                'options' => $this->articleCategoryOptions(),
                'bool' => false,
            ]] + $out;
        }

        return $out;
    }

    /** 资讯列表筛选用：资讯分类，子级带上父级名字。 */
    private function articleCategoryOptions(): array
    {
        $rows = \app\model\Category::whereIn('module', ['article', 'news'])
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select();
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row->id] = $row;
        }
        $opts = [];
        foreach ($rows as $row) {
            $name = (string) $row->name;
            $parentId = (int) ($row->parent_id ?? 0);
            if ($parentId && isset($byId[$parentId])) {
                $name = (string) $byId[$parentId]->name.' / '.$name;
            }
            $opts[(string) $row->id] = $name;
        }

        return $opts;
    }

    private function enumFilterPresets(): array
    {
        return [
            'member_type' => ['personal' => '个人会员', 'company' => '企业会员'],
            'group_code' => [
                'admin' => '管理员',
                'banned' => '禁止访问',
                'guest' => '游客',
                'pending' => '待审核会员',
                'personal' => '个人会员',
                'enterprise' => '企业会员',
                'company' => '企业会员',
                'vip' => 'VIP渠道会员',
            ],
            'type' => [
                'news' => '资讯',
                'quote' => '行情',
                'image' => '图片',
                'text' => '文字',
                'html' => 'HTML',
                'supply' => '供应',
                'purchase' => '采购',
                'product' => '商品',
                'company' => '企业',
            ],
            'module' => [
                'sell' => '供应',
                'buy' => '采购',
                'mall' => '商城',
                'company' => '企业库',
                'news' => '资讯',
                'article' => '资讯',
            ],
            'is_active' => ['1' => '启用', '0' => '停用'],
            'is_featured' => ['1' => '是', '0' => '否'],
            'is_certified' => ['1' => '已认证', '0' => '未认证'],
            'is_top' => ['1' => '是', '0' => '否'],
            'is_hot' => ['1' => '是', '0' => '否'],
            'is_admin' => ['1' => '是', '0' => '否'],
            'is_elite' => ['1' => '是', '0' => '否'],
            'is_urgent' => ['1' => '是', '0' => '否'],
            'is_best' => ['1' => '是', '0' => '否'],
            'is_listed' => ['1' => '上架', '0' => '未上架'],
            'is_sample' => ['1' => '是', '0' => '否'],
            'is_showcase' => ['1' => '是', '0' => '否'],
            'level' => ['0' => '普通会员', '1' => '白银会员', '2' => '黄金会员', '3' => '星钻会员'],
        ];
    }

    /** @return list<string> */
    private function resolveSortable(array $cfg): array
    {
        if (!empty($cfg['sortable']) && is_array($cfg['sortable'])) {
            return array_values(array_unique(array_map('strval', $cfg['sortable'])));
        }
        $auto = [
            'id', 'created_at', 'updated_at', 'published_at', 'refreshed_at',
            'views', 'view_boost', 'price', 'stock', 'sort', 'weight',
            'vip_level', 'level', 'sales', 'expires_at', 'quoted_on',
        ];
        $keys = [];
        foreach ($cfg['columns'] ?? [] as $col) {
            $k = (string) ($col['key'] ?? '');
            if ($k !== '' && !str_contains($k, '.') && in_array($k, $auto, true)) {
                $keys[] = $k;
            }
        }
        if (!in_array('id', $keys, true)) {
            array_unshift($keys, 'id');
        }

        return array_values(array_unique($keys));
    }

    /** 通用列筛选：cf_{field}=值；select 精确匹配，text 模糊。 */
    private function applyColumnFilters($q, array $filters): void
    {
        foreach ($filters as $field => $meta) {
            if (!is_array($meta)) {
                continue;
            }
            $param = 'cf_'.$field;
            $raw = $this->request->param($param, null);
            if ($raw === null || $raw === '') {
                continue;
            }
            $type = (string) ($meta['type'] ?? 'select');
            $bool = !empty($meta['bool']);
            if ($type === 'text') {
                $v = trim((string) $raw);
                if ($v === '') {
                    continue;
                }
                if ($field === 'id') {
                    $q->whereLike('id', '%'.$v.'%');
                } else {
                    $q->whereLike($field, '%'.$v.'%');
                }
                continue;
            }
            if ($bool) {
                $q->where($field, ($raw === '1' || $raw === 1 || $raw === true || $raw === 'true') ? 1 : 0);
            } else {
                $q->where($field, $raw);
            }
        }
    }

    private function applyCompanyFilters($q): void
    {
        $req = $this->request;
        if (($v = $req->param('is_featured')) !== null && $v !== '') {
            $q->where('is_featured', filter_var($v, FILTER_VALIDATE_BOOLEAN) || $v === '1' || $v === 1 ? 1 : 0);
        }
        if (($v = $req->param('is_certified')) !== null && $v !== '') {
            $q->where('is_certified', filter_var($v, FILTER_VALIDATE_BOOLEAN) || $v === '1' || $v === 1 ? 1 : 0);
        }
        if (($v = trim((string) $req->param('area', ''))) !== '') {
            $q->whereLike('area', '%'.$v.'%');
        }
        if (($v = $req->param('vip_level')) !== null && $v !== '') {
            $q->where('vip_level', (int) $v);
        }
        if (($v = $req->param('category_id')) !== null && $v !== '') {
            $q->where('category_id', (int) $v);
        }
        if (($v = trim((string) $req->param('business_mode', ''))) !== '') {
            $q->whereLike('business_mode', '%'.$v.'%');
        }
        if (($v = trim((string) $req->param('company_type', ''))) !== '') {
            $q->where('company_type', $v);
        }
        if (($v = $req->param('capital_min')) !== null && $v !== '') {
            $q->whereRaw("CAST(SUBSTRING_INDEX(REPLACE(IFNULL(capital,''),'万',''), ' ', 1) AS DECIMAL(18,2)) >= ?", [(float) $v]);
        }
        if (($v = $req->param('capital_max')) !== null && $v !== '') {
            $q->whereRaw("CAST(SUBSTRING_INDEX(REPLACE(IFNULL(capital,''),'万',''), ' ', 1) AS DECIMAL(18,2)) <= ?", [(float) $v]);
        }
        if (($v = trim((string) $req->param('member', ''))) !== '') {
            if (ctype_digit($v)) {
                $q->where('user_id', (int) $v);
            } else {
                $ids = User::whereLike('username', '%'.$v.'%')
                    ->whereOr('name', 'like', '%'.$v.'%')
                    ->column('id');
                $q->whereIn('user_id', $ids ?: [0]);
            }
        }
    }

    private function applyCompanyVipIndex(array $data, $item): array
    {
        $hasTheory = array_key_exists('vip_theory', $data);
        $hasCorrect = array_key_exists('vip_correct', $data);
        if ($hasTheory || $hasCorrect) {
            $theory = (int) ($hasTheory ? $data['vip_theory'] : ($item->vip_theory ?? 0));
            $correct = (int) ($hasCorrect ? $data['vip_correct'] : ($item->vip_correct ?? 0));
            $data['vip_theory'] = $theory;
            $data['vip_correct'] = $correct;
            $data['vip_level'] = max(0, min(10, $theory + $correct));
        } elseif (array_key_exists('vip_level', $data) && $data['vip_level'] !== null && $data['vip_level'] !== '') {
            $data['vip_level'] = max(0, min(10, (int) $data['vip_level']));
        }
        if (isset($data['business_mode']) && is_array($data['business_mode'])) {
            $modes = array_values(array_unique(array_filter(array_map('strval', $data['business_mode']))));
            $data['business_mode'] = implode(',', array_slice($modes, 0, 2));
        }
        return $data;
    }

    private function syncCompanyUserVerify($item, array $params): void
    {
        $userId = (int) ($item->user_id ?? 0);
        if ($userId <= 0) {
            return;
        }
        $flags = [];
        foreach (['mobile_verified', 'bank_verified', 'truename_verified'] as $f) {
            $key = 'user_'.$f;
            if (!array_key_exists($key, $params)) {
                continue;
            }
            $v = $params[$key];
            $flags[$f] = filter_var($v, FILTER_VALIDATE_BOOLEAN) || $v === '1' || $v === 1 ? 1 : 0;
        }
        if ($flags === []) {
            return;
        }
        $user = User::find($userId);
        if ($user) {
            $user->save($flags);
        }
    }

    private function syncExpiredHonors(): void
    {
        try {
            \app\model\CompanyHonor::where('status', 'approved')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<>', '')
                ->where('expires_at', '<', date('Y-m-d'))
                ->update(['status' => 'expired']);
        } catch (\Throwable $e) {
            // ignore if columns missing before migration
        }
    }

    private function normalizePayload(array $data, string $resource, string $mode): array
    {
        foreach ($data as $k => $v) {
            if ($v === '') $data[$k] = null;
            if (in_array($k, ['is_admin','is_certified','is_active','is_featured','is_top','is_urgent','is_elite','is_hot','mobile_verified','bank_verified','truename_verified'], true)) {
                $data[$k] = filter_var($v, FILTER_VALIDATE_BOOLEAN) || $v === '1' || $v === 1 ? 1 : 0;
            }
            if ($k === 'password') {
                if (!$v) unset($data[$k]);
                else $data[$k] = password_hash((string)$v, PASSWORD_BCRYPT);
            }
            if ($k === 'business_mode' && is_array($v)) {
                $modes = array_values(array_unique(array_filter(array_map('strval', $v))));
                $data[$k] = implode(',', array_slice($modes, 0, 2));
            }
            if ($k === 'quote_product_id') {
                if ($v === null || $v === '' || $v === 0 || $v === '0') {
                    $data[$k] = null;
                } else {
                    $data[$k] = (int) $v;
                }
            }
        }
        if ($resource === 'companies') {
            $slots = [];
            $hasSlot = false;
            for ($i = 1; $i <= 8; $i++) {
                $key = 'env_image'.$i;
                if (array_key_exists($key, $data)) {
                    $hasSlot = true;
                    $slots[] = $data[$key];
                }
                unset($data[$key]);
            }
            if ($hasSlot) {
                $data['env_images'] = \app\common\ApiResource::encodeEnvImages($slots);
            } elseif (array_key_exists('env_images', $data)) {
                $data['env_images'] = \app\common\ApiResource::encodeEnvImages($data['env_images']);
            }
        }
        if ($mode === 'create' && $resource === 'users' && empty($data['password'])) {
            throw new \think\exception\HttpException(422, '请设置密码');
        }
        // 排序留空时按 0，避免 NOT NULL 列写入 null 导致保存 500
        if (array_key_exists('sort', $data) && ($data['sort'] === null || $data['sort'] === '')) {
            $data['sort'] = 0;
        }
        if ($resource === 'categories') {
            $this->ensureCategoryIconColumn();
            if (array_key_exists('parent_id', $data) && ($data['parent_id'] === '' || $data['parent_id'] === 0 || $data['parent_id'] === '0')) {
                $data['parent_id'] = null;
            }
            $allowed = ['molecule', 'droplet', 'layers', 'particles', 'spark', 'chain', 'tube', 'gear', 'hex', 'box'];
            $icon = trim((string) ($data['icon'] ?? ''));
            $data['icon'] = in_array($icon, $allowed, true) ? $icon : null;
            $isParent = empty($data['parent_id']);
            if ($mode === 'create' && $isParent && $data['icon'] === null) {
                throw new \think\exception\HttpException(422, '请先选择侧栏图标。父级分类要显示在首页左侧，名称前面必须有一个图标');
            }
            if (!$isParent) {
                $data['icon'] = null;
            }
        }
        return $data;
    }
}