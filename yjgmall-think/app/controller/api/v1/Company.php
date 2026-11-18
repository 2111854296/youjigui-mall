<?php
namespace app\controller\api\v1;

use app\BaseController;
use app\common\ApiResource;
use app\common\SpreadRank;
use app\model\Company as CompanyModel;
use app\model\CompanyHonor;
use app\model\CompanyLink;
use app\model\CompanyNews;
use app\model\CompanyPage;
use app\model\JobPost;
use app\model\Product;
use app\model\Supply;
use think\Response;

class Company extends BaseController
{
    public function index(): Response
    {
        $req = $this->request;
        $query = CompanyModel::publishedQuery()->with(['category', 'user']);
        $q = trim((string) $req->param('q', ''));
        if ($q !== '') {
            $kw = '%'.$q.'%';
            $query->where(function ($w) use ($kw) {
                $w->whereLike('name', $kw)->whereOr('business', 'like', $kw)->whereOr('area', 'like', $kw);
            });
        }
        if ($c = (int) $req->param('category')) {
            filter_query_by_category($query, $c, ['name', 'business', 'intro']);
        }
        if ($req->param('featured')) {
            $query->where(function ($w) {
                $w->where('is_featured', 1)->whereOr('is_certified', 1);
            });
        }
        if ($req->param('certified')) {
            $query->where('is_certified', 1);
        }
        if (($vip = (int) $req->param('vip_level')) > 0) {
            $query->where('vip_level', '>=', $vip);
        }
        if ($t = trim((string) $req->param('company_type', ''))) {
            $query->where('company_type', $t);
        }
        if ($m = trim((string) $req->param('business_mode', ''))) {
            $query->where('business_mode', $m);
        }
        if ($s = trim((string) $req->param('scale', ''))) {
            $query->where('scale', $s);
        }
        if ($area = trim((string) $req->param('area', ''))) {
            $query->whereLike('area', '%'.$area.'%');
        }
        if ($cap = trim((string) $req->param('capital', ''))) {
            $query->whereLike('capital', '%'.$cap.'%');
        }

        if ($q !== '') {
            SpreadRank::apply($query, 'company', $q);
        }
        $this->orderByMember($query);
        $paginator = $query->paginate(['list_rows' => 25]);
        $result = paginate_laravel($paginator, fn ($c) => ApiResource::company($c));

        $featured = [];
        $floors = [];
        $latest = [];
        $hasExtraFilter = $req->param('q')
            || $req->param('category')
            || $req->param('vip_level')
            || $req->param('company_type')
            || $req->param('business_mode')
            || $req->param('scale')
            || $req->param('area')
            || $req->param('capital')
            || $req->param('certified')
            || $req->param('featured');

        // 企业库用独立行业分类（硅烷/硅树脂合并等），与商城 sell 楼层区分
        $categories = root_categories(['company']);
        if (!$categories || $categories->isEmpty()) {
            $categories = root_categories(['sell']);
        }
        $latestRows = CompanyModel::publishedQuery()->with(['user'])->order('id', 'desc')->limit(12)->select();
        $latest = array_map(fn ($c) => ApiResource::company($c), $latestRows->all());

        if ((int) $req->param('page', 1) <= 1 && !$hasExtraFilter) {
            $featuredRows = $this->orderByMember(CompanyModel::publishedQuery()->with(['user'])->where(function ($w) {
                $w->where('is_featured', 1)->whereOr('is_certified', 1);
            }))->limit(12)->select();
            $featured = array_map(fn ($c) => ApiResource::company($c), $featuredRows->all());

            foreach ($categories as $i => $cat) {
                $ids = array_merge([$cat->id], $cat->children ? $cat->children->column('id') : []);
                $name = $cat->name;
                $items = $this->orderByMember(CompanyModel::publishedQuery()->with(['user'])->where(function ($w) use ($ids, $name) {
                    $w->whereIn('category_id', $ids)->whereOr('business', 'like', '%'.$name.'%');
                }))->limit(8)->select();
                $floors[] = [
                    'floor_no' => $i + 1,
                    'category' => ApiResource::category($cat),
                    'children' => floor_children($cat),
                    'companies' => array_map(fn ($c) => ApiResource::company($c), $items->all()),
                ];
            }
        }

        $catId = (int) $req->param('category');

        return json(array_merge($result, [
            'featured' => $featured,
            'latest' => $latest,
            'floors' => $floors,
            'categories' => array_map(fn ($c) => ApiResource::category($c), is_object($categories) ? $categories->all() : []),
            'filters' => company_filter_options(),
            'active_root_id' => $catId ? category_root_id($catId) : null,
        ]));
    }

    public function show($company): Response
    {
        // 支持 slug / 公司id / 用户id（供应详情常把 user.id 当旺铺入口）
        $key = urldecode(trim((string) $company));
        $item = $this->findPublicCompany($key);
        $ownerPreview = false;
        if (!$item && $key !== '') {
            $owner = optional_request_user();
            if ($owner) {
                // 店主可预览自己的待审/下线旺铺；公众不可见 offline
                $own = CompanyModel::where('user_id', $owner->id)->where(function ($w) use ($key) {
                    $w->where('slug', $key);
                    if (ctype_digit($key)) {
                        $id = (int) $key;
                        $w->whereOr('id', $id);
                    }
                })->order('id', 'desc')->find();
                if ($own && (string) $own->status !== 'approved') {
                    $item = $own;
                    $ownerPreview = true;
                }
            }
        }
        if (!$item) {
            return json_error('企业不存在或未开通旺铺', 404);
        }
        if (!$ownerPreview) {
            $item->views = (int) $item->views + 1;
            $item->save();
        }
        $item->category;
        $item->supplies = Supply::publishedQuery()->where('user_id', $item->user_id)->limit(20)->select();
        $item->products = Product::publishedQuery()->where('user_id', $item->user_id)->limit(20)->select();
        $item->news = CompanyNews::where('company_id', $item->id)->where('status', 'published')->order('id', 'desc')->limit(20)->select();
        $item->honors = CompanyHonor::where('company_id', $item->id)->where('status', 'approved')->order('id', 'desc')->select();
        $item->pages = CompanyPage::where('company_id', $item->id)->where('status', 'published')->order('sort', 'asc')->select();
        $item->links = CompanyLink::where('company_id', $item->id)->select();
        $item->jobs = $item->user_id
            ? JobPost::where('user_id', $item->user_id)->where('status', 'approved')->order('id', 'desc')->limit(20)->select()
            : [];

        $data = ApiResource::company($item, true);
        if ($ownerPreview) {
            $data['owner_preview'] = true;
        }

        return json_data($data);
    }

    /** 访客只能看到已通过的旺铺；offline 不对公众开放（含联系方式）。 */
    private function findPublicCompany(string $key)
    {
        $item = null;
        if ($key !== '') {
            $item = CompanyModel::where('slug', $key)->where('status', 'approved')->find();
        }
        if (!$item && $key !== '' && ctype_digit($key)) {
            $id = (int) $key;
            $item = CompanyModel::where('id', $id)->where('status', 'approved')->find();
            if (!$item) {
                $item = CompanyModel::where('user_id', $id)->where('status', 'approved')->order('id', 'desc')->find();
            }
        }

        return $item;
    }

    /** 和供应列表一致：先按发布人会员等级，再按企业 VIP。 */
    private function orderByMember($query)
    {
        return $query
            ->orderRaw('(SELECT COALESCE(u.level,0) FROM users u WHERE u.id = companies.user_id LIMIT 1) DESC')
            ->order('vip_level', 'desc')
            ->order('is_featured', 'desc')
            ->order('id', 'desc');
    }
}
