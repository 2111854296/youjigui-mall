<?php
namespace app\controller\api\v1;

use app\BaseController;
use app\common\ApiResource;
use app\common\VipEntitlement;
use app\model\Article;
use app\model\ArticleTopic;
use think\Response;

class News extends BaseController
{
    public function index(): Response
    {
        $req = $this->request;
        $query = Article::publishedQuery()->with(['category', 'quoteProduct']);
        if ($t = $req->param('type')) {
            $query->where('type', $t);
        }
        if ($c = (int) $req->param('category')) {
            $query->whereIn('category_id', category_ids_with_children($c));
        }
        if ($qp = (int) $req->param('quote_product_id', 0)) {
            $query->where('quote_product_id', $qp);
        }
        if ($q = trim((string) $req->param('q', ''))) {
            $kw = '%'.$q.'%';
            $query->where(function ($w) use ($kw) {
                $w->whereLike('title', $kw)->whereOr('summary', 'like', $kw);
            });
        }
        $this->applySection($query, (string) $req->param('section', ''));
        $sort = (string) $req->param('sort', 'new');
        match ($sort) {
            'hot' => Article::orderByDisplayViews($query, 'desc'),
            'top' => $query->where('is_top', 1)->order('published_at', 'desc'),
            default => $query->order('is_top', 'desc')->order('published_at', 'desc'),
        };
        $perPage = (int) $req->param('per_page', 20);
        if ($perPage < 1) {
            $perPage = 20;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }
        $paginator = $query->paginate(['list_rows' => $perPage]);
        $result = paginate_laravel($paginator, fn ($a) => ApiResource::article($a));

        $catRows = root_categories(['article', 'news']);
        $categories = array_map(fn ($c) => ApiResource::category($c), $catRows ? $catRows->all() : []);
        $topics = ArticleTopic::where('is_active', 1)->order('sort', 'asc')->field('id,name,slug,summary')->select()->toArray();

        $headlines = array_map(
            fn ($a) => ApiResource::article($a),
            Article::publishedQuery()->where('is_top', 1)->order('published_at', 'desc')->limit(6)->select()->all()
        );
        if (count($headlines) < 3) {
            $more = Article::publishedQuery()->order('published_at', 'desc')->limit(6)->select();
            $seen = array_column($headlines, 'id');
            foreach ($more as $a) {
                if (in_array((int) $a->id, $seen, true)) {
                    continue;
                }
                $headlines[] = ApiResource::article($a);
                if (count($headlines) >= 6) {
                    break;
                }
            }
        }
        $rank = array_map(
            fn ($a) => ApiResource::article($a),
            Article::orderByDisplayViews(Article::publishedQuery(), 'desc')->limit(12)->select()->all()
        );

        return json(array_merge($result, [
            'categories' => $categories,
            'topics' => $topics,
            'headlines' => $headlines,
            'rank' => $rank,
        ]));
    }

    public function rank(): Response
    {
        $rows = Article::orderByDisplayViews(Article::publishedQuery(), 'desc')->limit(30)->select();

        return json_data(array_map(fn ($a) => ApiResource::article($a), $rows->all()));
    }

    public function topics(): Response
    {
        return json_data(ArticleTopic::where('is_active', 1)->order('sort', 'asc')->select()->toArray());
    }

    public function topic($slug): Response
    {
        $topic = ArticleTopic::where(function ($q) use ($slug) {
            $q->where('slug', $slug)->whereOr('id', $slug);
        })->where('is_active', 1)->find();
        if (!$topic) {
            return json_error('Not found', 404);
        }
        // 关联在 article_topic 中间表，不是 articles.topic_id
        $articleIds = \think\facade\Db::name('article_topic')
            ->where('article_topic_id', $topic->id)
            ->column('article_id');
        $paginator = Article::publishedQuery()
            ->whereIn('id', $articleIds ?: [0])
            ->order('published_at', 'desc')
            ->paginate(['list_rows' => 20]);

        return json(array_merge(paginate_laravel($paginator, fn ($a) => ApiResource::article($a)), [
            'topic' => [
                'id' => (int) $topic->id,
                'name' => $topic->name,
                'slug' => $topic->slug,
                'summary' => $topic->summary,
            ],
        ]));
    }

    public function show($id): Response
    {
        $item = Article::publishedQuery()->with(['category'])->find($id);
        if (!$item) {
            return json_error('Not found', 404);
        }
        $item->views = (int) $item->views + 1;
        $item->save();

        $data = ApiResource::article($item);
        $isReport = VipEntitlement::isResearchReport((string) ($item->title ?? ''), (string) ($item->type ?? ''));
        $data['is_report'] = $isReport;
        if (!$isReport) {
            $data['report_locked'] = false;
            $data['report_access'] = 'open';

            return json_data($data);
        }

        $viewer = request()->user ?? optional_request_user();
        $price = VipEntitlement::reportPrice();
        $data['report_price'] = $price;
        if (VipEntitlement::canAccessReport($viewer, (int) $item->id)) {
            $data['report_locked'] = false;
            $data['report_access'] = $viewer && VipEntitlement::canReadIndustryReport($viewer) ? 'vip' : 'purchased';

            return json_data($data);
        }

        $data['content'] = unwrap_destoon_lazy_html(
            VipEntitlement::reportPreviewHtml((string) ($item->content ?? ''), $item->summary ?? null)
        );
        $data['report_locked'] = true;
        if (!$viewer) {
            $data['report_access'] = 'guest';
            $data['report_hint'] = '非会员仅可查看导读以上内容，其余需升级会员后观看。';
        } else {
            $data['report_access'] = 'need_buy';
            $data['report_hint'] = '普通会员和白银会员需购买单个报告后才能阅读全文。黄金、星钻会员可直接阅读。';
        }

        return json_data($data);
    }

    /** 资讯栏目只认分类；未分类不进任何栏目板块。 */
    private function applySection($query, string $section): void
    {
        $section = trim($section);
        $slugMap = [
            'review' => 'news-review',
            'capacity' => 'news-capacity',
            'trade' => 'news-trade',
            'company' => 'news-company',
            'talk' => 'news-talk',
            'event' => 'news-event',
            'product' => 'news-product',
            'tech' => 'news-tech',
            'expo' => 'news-expo',
            'report' => 'news-report',
        ];
        if ($section === '' || empty($slugMap[$section])) {
            return;
        }
        $catId = (int) \app\model\Category::where('module', 'article')->where('slug', $slugMap[$section])->value('id');
        if ($catId < 1) {
            // 分类未建好时不回退关键词，避免串台；结果为空
            $query->where('id', 0);

            return;
        }
        // 含子分类（如网评→日评/周评，产能→开工率）
        $query->whereIn('category_id', category_ids_with_children($catId));
    }
}
