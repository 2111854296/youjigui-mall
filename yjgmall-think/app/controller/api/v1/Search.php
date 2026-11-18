<?php
namespace app\controller\api\v1;

use app\BaseController;
use app\common\ApiResource;
use app\common\SpreadRank;
use app\model\Article;
use app\model\Company;
use app\model\Product;
use app\model\Purchase;
use app\model\Supply;
use think\Response;

class Search extends BaseController
{
    public function index(): Response
    {
        $q = trim((string) $this->request->param('q', ''));
        $type = (string) $this->request->param('type', 'all');
        if (!in_array($type, ['all', 'sell', 'buy', 'mall', 'company', 'news'], true)) {
            $type = 'all';
        }
        if ($q === '') {
            return json([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 16, 'total' => 0],
                'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
                'meta_query' => ['type' => $type, 'q' => $q],
            ]);
        }
        $kw = '%'.$q.'%';
        $page = max(1, (int) $this->request->param('page', 1));
        $buyQuery = Purchase::publishedQuery()->with(['category', 'user'])->where(function ($w) use ($kw) {
            $w->whereLike('title', $kw)->whereOr('summary', 'like', $kw);
        });
        $companyQuery = Company::publishedQuery()->where(function ($w) use ($kw) {
            $w->whereLike('name', $kw)->whereOr('business', 'like', $kw)->whereOr('area', 'like', $kw);
        });
        $mallQuery = Product::publishedQuery()->with(['category', 'user'])->where(function ($w) use ($kw) {
            $w->whereLike('title', $kw)->whereOr('summary', 'like', $kw)->whereOr('content', 'like', $kw);
        });
        $sellQuery = Supply::publishedQuery()->with(['category', 'user'])->where(function ($w) use ($kw) {
            $w->whereLike('title', $kw)->whereOr('brand', 'like', $kw)->whereOr('summary', 'like', $kw)->whereOr('content', 'like', $kw);
        });
        $newsQuery = Article::publishedQuery()->where(function ($w) use ($kw) {
            $w->whereLike('title', $kw)->whereOr('summary', 'like', $kw);
        });
        SpreadRank::apply($buyQuery, 'buy', $q);
        SpreadRank::apply($companyQuery, 'company', $q);
        SpreadRank::apply($mallQuery, 'mall', $q);
        SpreadRank::apply($sellQuery, 'sell', $q);

        if ($type === 'all') {
            return $this->allResults($q, $page, $sellQuery, $buyQuery, $mallQuery, $companyQuery, $newsQuery);
        }

        $paginator = match ($type) {
            'buy' => $this->orderByRelevanceThenMember($buyQuery, 'purchases', 'title', $q, false)->paginate(['list_rows' => 16, 'page' => $page]),
            'company' => $this->orderByRelevance($companyQuery, 'name', $q)
                ->order('vip_level', 'desc')->order('is_featured', 'desc')->order('id', 'desc')
                ->paginate(['list_rows' => 16, 'page' => $page]),
            'news' => $this->orderByRelevance($newsQuery, 'title', $q)->order('id', 'desc')->paginate(['list_rows' => 16, 'page' => $page]),
            'mall' => $this->orderByRelevanceThenMember($mallQuery, 'products', 'title', $q, false)->paginate(['list_rows' => 16, 'page' => $page]),
            default => $this->orderByRelevanceThenMember($sellQuery, 'supplies', 'title', $q, true)->paginate(['list_rows' => 16, 'page' => $page]),
        };
        $map = match ($type) {
            'buy' => fn ($r) => ApiResource::purchase($r),
            'company' => fn ($r) => ApiResource::company($r),
            'news' => fn ($r) => ApiResource::article($r),
            'mall' => fn ($r) => ApiResource::product($r),
            default => fn ($r) => ApiResource::supply($r),
        };

        return json(array_merge(paginate_laravel($paginator, $map), [
            'meta_query' => ['type' => $type, 'q' => $q],
        ]));
    }

    /** 综合：各频道各取一批，合并后先按相关度再按会员等级排序再分页。 */
    private function allResults(
        string $q,
        int $page,
        $sellQuery,
        $buyQuery,
        $mallQuery,
        $companyQuery,
        $newsQuery
    ): Response {
        $perChannel = 24;
        $rows = [];
        foreach ($this->orderByRelevanceThenMember($sellQuery, 'supplies', 'title', $q, true)->limit($perChannel)->select() as $r) {
            $item = ApiResource::supply($r);
            $item['_channel'] = 'sell';
            $item['_channel_label'] = '供应';
            $item['_relevance'] = $this->textRelevance($q, (string) ($item['title'] ?? ''));
            $rows[] = $item;
        }
        foreach ($this->orderByRelevanceThenMember($buyQuery, 'purchases', 'title', $q, false)->limit($perChannel)->select() as $r) {
            $item = ApiResource::purchase($r);
            $item['_channel'] = 'buy';
            $item['_channel_label'] = '采购';
            $item['_relevance'] = $this->textRelevance($q, (string) ($item['title'] ?? ''));
            $rows[] = $item;
        }
        foreach ($this->orderByRelevanceThenMember($mallQuery, 'products', 'title', $q, false)->limit($perChannel)->select() as $r) {
            $item = ApiResource::product($r);
            $item['_channel'] = 'mall';
            $item['_channel_label'] = '商城';
            $item['_relevance'] = $this->textRelevance($q, (string) ($item['title'] ?? ''));
            $rows[] = $item;
        }
        foreach ($this->orderByRelevance($companyQuery, 'name', $q)->order('vip_level', 'desc')->order('is_featured', 'desc')->order('id', 'desc')->limit($perChannel)->select() as $r) {
            $item = ApiResource::company($r);
            $item['_channel'] = 'company';
            $item['_channel_label'] = '企业';
            $item['_relevance'] = $this->textRelevance($q, (string) ($item['name'] ?? ''));
            $rows[] = $item;
        }
        foreach ($this->orderByRelevance($newsQuery, 'title', $q)->order('id', 'desc')->limit($perChannel)->select() as $r) {
            $item = ApiResource::article($r);
            $item['_channel'] = 'news';
            $item['_channel_label'] = '资讯';
            $item['_relevance'] = $this->textRelevance($q, (string) ($item['title'] ?? ''));
            $rows[] = $item;
        }

        usort($rows, static function ($a, $b) {
            $ra = (int) ($a['_relevance'] ?? 99);
            $rb = (int) ($b['_relevance'] ?? 99);
            if ($ra !== $rb) {
                return $ra <=> $rb;
            }
            $la = (int) ($a['user']['member_level'] ?? $a['vip_level'] ?? 0);
            $lb = (int) ($b['user']['member_level'] ?? $b['vip_level'] ?? 0);
            if ($la !== $lb) {
                return $lb <=> $la;
            }
            $wa = (int) ($a['weight'] ?? 0);
            $wb = (int) ($b['weight'] ?? 0);
            if ($wa !== $wb) {
                return $wb <=> $wa;
            }

            return ((int) ($b['id'] ?? 0)) <=> ((int) ($a['id'] ?? 0));
        });

        $per = 16;
        $total = count($rows);
        $last = max(1, (int) ceil($total / $per));
        $page = min($page, $last);
        $slice = array_values(array_slice($rows, ($page - 1) * $per, $per));
        foreach ($slice as &$row) {
            unset($row['_relevance']);
        }
        unset($row);

        return json([
            'data' => $slice,
            'meta' => [
                'current_page' => $page,
                'last_page' => $last,
                'per_page' => $per,
                'total' => $total,
            ],
            'links' => [
                'first' => null,
                'last' => null,
                'prev' => $page > 1 ? $page - 1 : null,
                'next' => $page < $last ? $page + 1 : null,
            ],
            'meta_query' => ['type' => 'all', 'q' => $q],
        ]);
    }

    /** 标题相关度：越小越靠前。0 精确 / 1 前缀 / 2 包含 / 3 仅其它字段命中 */
    private function textRelevance(string $q, string $title): int
    {
        $q = trim($q);
        $title = trim($title);
        if ($q === '' || $title === '') {
            return 3;
        }
        if (strcasecmp($title, $q) === 0 || $title === $q) {
            return 0;
        }
        if (mb_stripos($title, $q) === 0) {
            return 1;
        }
        if (mb_stripos($title, $q) !== false) {
            // 标题里关键词相对更「主体」时略优先：DMC 本体优于「DMC贮罐」这类长尾
            $ratio = mb_strlen($q) / max(1, mb_strlen($title));
            return $ratio >= 0.35 ? 2 : 3;
        }

        return 4;
    }

    private function orderByRelevance($query, string $titleCol, string $q)
    {
        $safe = addslashes($q);
        $col = preg_replace('/[^a-zA-Z0-9_]/', '', $titleCol) ?: 'title';
        $query->orderRaw(
            "CASE
                WHEN `{$col}` = '{$safe}' THEN 0
                WHEN `{$col}` LIKE '{$safe}%' THEN 1
                WHEN `{$col}` LIKE '%{$safe}%' THEN 2
                ELSE 3
             END ASC"
        );

        return $query;
    }

    /** 相关度优先，再会员等级；供应保留原有自然排序权重。 */
    private function orderByRelevanceThenMember($query, string $table, string $titleCol, string $q, bool $withWeight)
    {
        $this->orderByRelevance($query, $titleCol, $q);
        if ($withWeight && $table === 'supplies') {
            supply_natural_order($query);

            return $query;
        }
        $query->orderRaw("(SELECT COALESCE(u.level,0) FROM users u WHERE u.id = {$table}.user_id LIMIT 1) DESC");

        return $table === 'products'
            ? $query->order('id', 'desc')
            : $query->order('refreshed_at', 'desc');
    }
}
