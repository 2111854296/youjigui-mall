<?php

namespace app\common;

use app\model\Company as CompanyModel;
use app\model\User;

/**
 * Laravel Resource-shaped arrays for Nuxt frontend contracts.
 */
class ApiResource
{
    public static function category($c): ?array
    {
        if (!$c) {
            return null;
        }

        return [
            'id' => (int) $c->id,
            'parent_id' => $c->parent_id,
            'name' => $c->name,
            'slug' => $c->slug,
            'module' => $c->module ?? null,
            'sort' => (int) ($c->sort ?? 0),
            'children' => isset($c->children) ? array_map(fn ($ch) => [
                'id' => (int) $ch->id,
                'name' => $ch->name,
                'slug' => $ch->slug,
            ], $c->children->all()) : [],
        ];
    }

    public static function supply($s, bool $detail = false): array
    {
        $user = $s->user ?? null;
        $data = [
            'id' => (int) $s->id,
            'category_id' => $s->category_id,
            'title' => $s->title,
            'thumb' => $s->thumb,
            'thumb1' => $s->thumb1 ?? null,
            'thumb2' => $s->thumb2 ?? null,
            'price' => $s->price !== null ? (string) $s->price : null,
            'unit' => $s->unit,
            'min_amount' => $s->min_amount !== null ? (string) $s->min_amount : null,
            'amount' => $s->amount ?? null,
            'days' => $s->days ?? null,
            'brand' => $s->brand,
            'area' => $s->area,
            'attr1_name' => $s->attr1_name ?? null,
            'attr1_value' => $s->attr1_value ?? null,
            'attr2_name' => $s->attr2_name ?? null,
            'attr2_value' => $s->attr2_value ?? null,
            'attr3_name' => $s->attr3_name ?? null,
            'attr3_value' => $s->attr3_value ?? null,
            'attrs' => array_values(array_filter([
                !empty($s->attr1_name) ? ['name' => $s->attr1_name, 'value' => $s->attr1_value] : null,
                !empty($s->attr2_name) ? ['name' => $s->attr2_name, 'value' => $s->attr2_value] : null,
                !empty($s->attr3_name) ? ['name' => $s->attr3_name, 'value' => $s->attr3_value] : null,
            ])),
            'summary' => $s->summary,
            'content' => $detail ? $s->content : ($s->content ?? null),
            'status' => $s->status,
            'is_urgent' => (bool) ($s->is_urgent ?? false),
            'is_hot' => (bool) ($s->is_hot ?? false),
            'is_elite' => (bool) ($s->is_elite ?? false),
            'level' => (int) ($s->level ?? 0),
            'weight' => (int) ($s->weight ?? 0),
            'views' => (int) ($s->views ?? 0),
            'refreshed_at' => $s->refreshed_at,
            'created_at' => $s->created_at,
            'category' => self::category($s->category ?? null),
            'user' => self::userBrief($user, $detail, $detail && (bool) (request()->user ?? null)),
        ];
        $data['user'] = self::attachMatchedShop($data['user'] ?? null, $user);
        $data['user'] = self::attachSupplyCompany($data['user'] ?? null, $s);

        return $data;
    }

    /**
     * 发布人公司名能对上唯一旺铺时，带上旺铺 id / slug，供前端进店。
     * 对不上就不猜，避免一个账号挂了很多企业时链到错误店铺。
     */
    private static function attachMatchedShop(?array $brief, $user): ?array
    {
        if (!$brief || !$user) {
            return $brief;
        }
        if (!empty($brief['company_slug'])) {
            return $brief;
        }
        $name = trim((string) ($brief['company_name'] ?? ''));
        if ($name === '') {
            return $brief;
        }
        $rows = CompanyModel::where('user_id', (int) $user->id)
            ->where('name', $name)
            ->whereIn('status', ['approved', 'offline'])
            ->field('id,slug,name')
            ->limit(2)
            ->select();
        if (count($rows) !== 1) {
            return $brief;
        }
        $co = $rows[0];
        $brief['company_id'] = (int) $co->id;
        $brief['company_slug'] = $co->slug;

        return $brief;
    }

    /** 供应已记了所属企业时，详情和首页都用这一家，不再退回账号下最新旺铺。 */
    private static function attachSupplyCompany(?array $brief, $supply): ?array
    {
        if (!$brief) {
            return $brief;
        }
        $id = (int) ($supply->company_id ?? 0);
        if ($id <= 0) {
            return $brief;
        }
        $co = CompanyModel::where('id', $id)
            ->whereIn('status', ['approved', 'offline'])
            ->field('id,slug,name,logo')
            ->find();
        if (!$co) {
            return $brief;
        }
        $name = trim((string) $co->name);
        if ($name === '' || $name === '管理员') {
            return $brief;
        }
        $brief['company_id'] = (int) $co->id;
        $brief['company_slug'] = $co->slug ?: (string) $co->id;
        $brief['company_name'] = $name;
        $logo = trim((string) ($co->logo ?? ''));
        if ($logo !== '') {
            $brief['company_logo'] = $logo;
        }

        return $brief;
    }

    /** @return array<string, mixed>|null */
    public static function userBrief($user, bool $withShop = false, bool $revealContact = false): ?array
    {
        if (!$user) {
            return null;
        }
        $mobile = trim((string) ($user->mobile ?? ''));
        $shop = null;
        if ($withShop) {
            $shop = CompanyModel::where('user_id', (int) $user->id)
                ->whereIn('status', ['approved', 'offline'])
                ->order('id', 'desc')
                ->field('id,slug,name,logo,phone,area')
                ->find();
            if ($mobile === '' && $shop) {
                $mobile = trim((string) ($shop->phone ?? ''));
            }
        }
        $data = [
            'id' => (int) $user->id,
            'name' => $user->name,
            'company_name' => trim((string) ($user->company_name ?? '')) !== ''
                ? $user->company_name
                : ($shop->name ?? null),
            'mobile' => $mobile === '' ? null : ($revealContact ? $mobile : VipEntitlement::maskMobile($mobile)),
            'mobile_masked' => !$revealContact && $mobile !== '',
            'qq' => $revealContact ? ($user->qq ?? null) : null,
            'is_vip' => method_exists($user, 'isVip') ? $user->isVip() : false,
            'vip_grade_code' => $user->vip_grade_code ?? null,
            ...\app\common\VipEntitlement::memberMark($user),
            'is_certified' => (bool) ($user->is_certified ?? false),
        ];
        if ($withShop) {
            $data['company_id'] = $shop ? (int) $shop->id : null;
            $data['company_slug'] = $shop->slug ?? null;
            $logo = trim((string) ($shop->logo ?? ''));
            $data['company_logo'] = $logo !== '' ? $logo : null;
        }

        return $data;
    }

    public static function purchase($p, bool $revealContact = false): array
    {
        $user = $p->user ?? null;
        $viewer = request()->user ?? null;
        $reveal = $revealContact;
        if (!$reveal && $viewer && $user && (int) $viewer->id === (int) $user->id) {
            $reveal = true;
        }
        if (!$reveal && $viewer && $user && VipEntitlement::hasUnlocked($viewer, 'purchase', (int) $p->id)) {
            $reveal = true;
        }

        return [
            'id' => (int) $p->id,
            'category_id' => $p->category_id,
            'title' => $p->title,
            'thumb' => $p->thumb,
            'budget' => $p->budget !== null ? (string) $p->budget : null,
            'quantity' => $p->quantity,
            'unit' => $p->unit,
            'area' => $p->area,
            'days' => $p->days ?? null,
            'summary' => $p->summary,
            'content' => $p->content,
            'status' => $p->status,
            'is_urgent' => (bool) ($p->is_urgent ?? false),
            'is_hot' => (bool) ($p->is_hot ?? false),
            'level' => (int) ($p->level ?? 0),
            'views' => (int) ($p->views ?? 0),
            'refreshed_at' => $p->refreshed_at,
            'quotes_count' => (int) ($p->quotes_count ?? 0),
            'contact_unlocked' => $reveal,
            'category' => self::category($p->category ?? null),
            'user' => self::userBrief($user, true, $reveal),
        ];
    }

    public static function product($p): array
    {
        $user = $p->user ?? null;
        $co = self::productCompany($p, $user);
        $companyName = trim((string) ($co->name ?? ''));
        if ($companyName === '') {
            $companyName = trim((string) ($user->company_name ?? ''));
        }

        return [
            'id' => (int) $p->id,
            'title' => $p->title,
            'thumb' => $p->thumb,
            'thumb1' => $p->thumb1 ?? null,
            'thumb2' => $p->thumb2 ?? null,
            'price' => (string) $p->price,
            'price_tiers' => \app\common\PriceTiers::decode($p->price_tiers ?? null),
            'detail_images' => self::detailImages($p->detail_images ?? null),
            'stock' => (int) ($p->stock ?? 0),
            'unit' => $p->unit,
            'summary' => $p->summary,
            'content' => $p->content,
            'status' => $p->status,
            'is_listed' => (int) ($p->is_listed ?? 1),
            'is_showcase' => (int) ($p->is_showcase ?? 0),
            'is_sample' => (int) ($p->is_sample ?? 0),
            'created_at' => (string) ($p->created_at ?? ''),
            'sales' => (int) ($p->sales ?? 0),
            'views' => (int) ($p->views ?? 0),
            'category' => self::category($p->category ?? null),
            'company_id' => $co ? (int) $co->id : null,
            'company_slug' => $co->slug ?? null,
            'company_name' => $companyName !== '' ? $companyName : null,
            'company_logo' => trim((string) ($co->logo ?? '')) !== '' ? (string) $co->logo : null,
            'company_area' => trim((string) ($co->area ?? '')) !== '' ? trim((string) $co->area) : null,
            'company_user_id' => $co && !empty($co->user_id) ? (int) $co->user_id : null,
            'user' => $user ? array_merge([
                'id' => (int) $user->id,
                'name' => $user->name,
                'company_name' => $companyName !== '' ? $companyName : null,
                'company_id' => $co ? (int) $co->id : null,
                'company_slug' => $co->slug ?? null,
            ], \app\common\VipEntitlement::memberMark($user)) : null,
        ];
    }

    /** 商品评价上方的详情图，最多 5 张。 */
    private static function detailImages($raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        }
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $src) {
            $src = trim((string) $src);
            if ($src === '') {
                continue;
            }
            $out[] = $src;
            if (count($out) >= 5) {
                break;
            }
        }

        return $out;
    }

    /** 商品所属企业：优先 products.company_id，其次发布人公司名能对上的旺铺。 */
    private static function productCompany($p, $user)
    {
        $loaded = $p->company ?? null;
        if ($loaded && !empty($loaded->id)) {
            return $loaded;
        }
        if (!empty($p->company_id)) {
            $byId = CompanyModel::where('id', (int) $p->company_id)
                ->whereIn('status', ['approved', 'offline'])
                ->field('id,name,slug,user_id,status,area,logo')
                ->find();
            if ($byId) {
                return $byId;
            }
        }
        $name = trim((string) ($user->company_name ?? ''));
        if (!$user || $name === '') {
            return null;
        }

        return CompanyModel::where('user_id', (int) $user->id)
            ->where('name', $name)
            ->whereIn('status', ['approved', 'offline'])
            ->field('id,name,slug,user_id,status,area,logo')
            ->find();
    }

    public static function company($c, bool $detail = false): array
    {
        $data = [
            'id' => (int) $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'logo' => $c->logo,
            'banner' => $c->banner ?? null,
            'env_images' => self::decodeEnvImages($c->env_images ?? null),
            'contact' => $c->contact,
            'phone' => $c->phone,
            'website' => $c->website,
            'address' => $c->address,
            'area' => $c->area,
            'business' => $c->business,
            'tags' => $c->tags ?? null,
            'company_type' => $c->company_type ?? null,
            'business_mode' => $c->business_mode ?? null,
            'scale' => $c->scale ?? null,
            'capital' => $c->capital ?? null,
            'capital_unit' => $c->capital_unit ?? null,
            'capital_display' => (static function () use ($c) {
                $cap = trim((string) ($c->capital ?? ''));
                $unit = trim((string) ($c->capital_unit ?? ''));
                if ($cap === '') {
                    return null;
                }
                return $unit !== '' ? $cap.$unit : $cap;
            })(),
            'reg_year' => $c->reg_year !== null && $c->reg_year !== '' ? (int) $c->reg_year : null,
            'zip' => $c->zip ?? null,
            'fax' => $c->fax ?? null,
            'email' => $c->email ?? null,
            'sell_products' => $c->sell_products ?? null,
            'buy_products' => $c->buy_products ?? null,
            'vip_level' => (int) ($c->vip_level ?? 0),
            'intro' => $c->intro,
            'content' => $c->content,
            'status' => $c->status,
            'is_featured' => (bool) ($c->is_featured ?? false),
            'is_certified' => (bool) ($c->is_certified ?? false),
            'template' => $c->template ?: 'default',
            'views' => (int) ($c->views ?? 0),
            'category' => self::category($c->category ?? null),
        ];
        $bag = method_exists($c, 'getData') ? (array) $c->getData() : [];
        $owner = $bag['user'] ?? null;
        $data = array_merge($data, is_object($owner)
            ? \app\common\VipEntitlement::memberMark($owner)
            : ['member_level' => 0, 'member_badge' => '', 'member_grade' => '']);
        if ($detail) {
            $user = null;
            if (!empty($c->user_id)) {
                $user = $c->user ?? User::find($c->user_id);
            }
            $vipYears = 0;
            if ($user && !empty($user->vip_expire_at) && strtotime((string) $user->vip_expire_at) > time()) {
                $created = strtotime((string) ($user->created_at ?? 'now')) ?: time();
                $vipYears = max(1, (int) floor((time() - $created) / (365 * 86400)) + 1);
            } elseif ($user && method_exists($user, 'isVip') && $user->isVip()) {
                $vipYears = max(1, (int) ($user->level ?? 1));
            } elseif ((int) ($c->vip_level ?? 0) > 0) {
                $vipYears = max(1, (int) ceil(((int) $c->vip_level) / 2));
            }
            $data['credit'] = [
                'vip_level' => (int) ($c->vip_level ?? 0),
                'vip_years' => $vipYears,
                'is_vip' => $user ? (method_exists($user, 'isVip') ? $user->isVip() : false) : ((int) ($c->vip_level ?? 0) >= 5),
                'is_certified' => (bool) ($c->is_certified ?? false) || (bool) ($user->is_certified ?? false),
                'credit_score' => (int) ($user->credit ?? 0) + ((int) ($c->vip_level ?? 0) * 10) + ((bool) ($c->is_certified ?? false) ? 50 : 0),
                'deposit' => (string) ($user->deposit ?? '0.00'),
                'mobile_verified' => (bool) ($user->mobile_verified ?? false),
                'bank_verified' => (bool) ($user->bank_verified ?? false),
                'truename_verified' => (bool) ($user->truename_verified ?? false),
                'member_since' => $user->created_at ?? null,
                'supplies_count' => isset($c->supplies) ? (is_object($c->supplies) && method_exists($c->supplies, 'count') ? $c->supplies->count() : count((array) $c->supplies)) : null,
                'honors_count' => isset($c->honors) ? (is_object($c->honors) && method_exists($c->honors, 'count') ? $c->honors->count() : count((array) $c->honors)) : null,
            ];
            $data['supplies'] = isset($c->supplies) ? array_map(fn ($s) => self::supply($s), $c->supplies->all()) : [];
            $data['products'] = isset($c->products) ? array_map(fn ($p) => self::product($p), $c->products->all()) : [];
            $data['news'] = isset($c->news) ? array_map(fn ($n) => [
                'id' => (int) $n->id,
                'title' => $n->title,
                'content' => $n->content,
                'created_at' => $n->created_at,
            ], $c->news->all()) : [];
            $data['honors'] = isset($c->honors) ? array_map(fn ($h) => [
                'id' => (int) $h->id,
                'title' => $h->title,
                'image' => $h->image,
                'authority' => $h->authority,
                'issued_at' => $h->issued_at,
                'expires_at' => $h->expires_at ?? null,
                'status' => $h->status ?? 'approved',
            ], $c->honors->all()) : [];
            $data['pages'] = isset($c->pages) ? array_map(fn ($p) => [
                'id' => (int) $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'content' => $p->content,
            ], $c->pages->all()) : [];
            $data['links'] = isset($c->links) ? array_map(fn ($l) => [
                'id' => (int) $l->id,
                'name' => $l->name,
                'url' => $l->url,
            ], $c->links->all()) : [];
            $jobsSrc = $c->jobs ?? null;
            if ($jobsSrc === null) {
                $data['jobs'] = [];
            } else {
                $jobRows = is_object($jobsSrc) && method_exists($jobsSrc, 'all')
                    ? $jobsSrc->all()
                    : (array) $jobsSrc;
                $data['jobs'] = array_map(fn ($j) => [
                    'id' => (int) $j->id,
                    'title' => $j->title,
                    'salary' => $j->salary ?? null,
                    'area' => $j->area ?? null,
                    'content' => $j->content ?? null,
                    'created_at' => $j->created_at ?? null,
                ], $jobRows);
            }
        }

        return $data;
    }

    public static function article($a): array
    {
        $qp = null;
        if (isset($a->quoteProduct) && $a->quoteProduct) {
            $qp = $a->quoteProduct;
        } elseif (!empty($a->quote_product_id)) {
            try {
                $qp = \app\model\QuoteProduct::find((int) $a->quote_product_id);
            } catch (\Throwable $e) {
                $qp = null;
            }
        }

        return [
            'id' => (int) $a->id,
            'title' => $a->title,
            'thumb' => $a->thumb,
            'author' => $a->author,
            'summary' => $a->summary,
            'content' => unwrap_destoon_lazy_html((string) ($a->content ?? '')),
            'type' => $a->type,
            'status' => $a->status,
            'is_top' => (bool) ($a->is_top ?? false),
            'views' => method_exists($a, 'displayViews')
                ? $a->displayViews()
                : ((int) ($a->views ?? 0) + (int) ($a->view_boost ?? 0)),
            'published_at' => $a->published_at,
            'quote_product_id' => !empty($a->quote_product_id) ? (int) $a->quote_product_id : null,
            'quote_product' => $qp ? [
                'id' => (int) $qp->id,
                'name' => (string) ($qp->name ?? ''),
            ] : null,
            'category' => isset($a->category) && $a->category ? [
                'id' => (int) $a->category->id,
                'name' => $a->category->name,
                'slug' => $a->category->slug,
            ] : [
                'id' => 0,
                'name' => '未分类',
                'slug' => '',
            ],
            'topics' => isset($a->topics) ? array_map(fn ($t) => [
                'id' => (int) $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ], $a->topics->all()) : [],
        ];
    }

    public static function order($o): array
    {
        return [
            'id' => (int) $o->id,
            'order_no' => $o->order_no,
            'amount' => (string) $o->amount,
            'status' => $o->status,
            'payment_method' => $o->payment_method,
            'receiver_name' => $o->receiver_name,
            'receiver_phone' => $o->receiver_phone,
            'receiver_address' => $o->receiver_address,
            'remark' => $o->remark,
            'refund_reason' => $o->refund_reason ?? null,
            'paid_at' => $o->paid_at,
            'created_at' => $o->created_at,
            'user' => isset($o->user) && $o->user ? [
                'id' => (int) $o->user->id,
                'name' => $o->user->name,
                'username' => $o->user->username,
                'company_name' => $o->user->company_name,
            ] : null,
            'items' => isset($o->items) ? array_map(fn ($i) => [
                'id' => (int) $i->id,
                'title' => $i->title,
                'price' => (string) $i->price,
                'quantity' => (int) $i->quantity,
            ], $o->items->all()) : [],
        ];
    }

    public static function authUser(): ?User
    {
        return request()->user ?? null;
    }

    public static function requireEnterprise(?User $user = null): ?\think\Response
    {
        $user = $user ?: self::authUser();
        if (!$user || !$user->isEnterpriseMember()) {
            return json_error('该功能仅对企业会员开放，请先升级为企业会员', 403);
        }

        return null;
    }

    /** 招聘发布 / 人才库：已认证企业（含已通过旺铺），管理员例外 */
    public static function requireCertifiedEnterprise(?User $user = null): ?\think\Response
    {
        $user = $user ?: self::authUser();
        if (!$user) {
            return json_error('请先登录', 401);
        }
        if (method_exists($user, 'canBrowseTalents') && $user->canBrowseTalents()) {
            return null;
        }
        if (!$user->isEnterpriseMember()) {
            return json_error('招聘发布仅对已认证企业开放，请先升级为企业会员并完成认证', 403);
        }

        return json_error('企业尚未认证通过，认证通过后才能发布招聘', 403);
    }

    /** 厂房环境图：最多 8 张有效 URL */
    public static function decodeEnvImages($raw): array
    {
        if (is_array($raw)) {
            return self::normalizeEnvImages($raw);
        }
        $s = trim((string) ($raw ?? ''));
        if ($s === '') {
            return [];
        }
        $decoded = json_decode($s, true);
        if (!is_array($decoded)) {
            return [];
        }
        return self::normalizeEnvImages($decoded);
    }

    public static function normalizeEnvImages($raw): array
    {
        $out = [];
        if (!is_array($raw)) {
            return $out;
        }
        foreach ($raw as $item) {
            if (is_array($item)) {
                $item = $item['url'] ?? $item['image'] ?? $item['src'] ?? '';
            }
            $u = trim((string) $item);
            if ($u === '' || in_array($u, $out, true)) {
                continue;
            }
            $out[] = $u;
            if (count($out) >= 8) {
                break;
            }
        }
        return $out;
    }

    public static function encodeEnvImages($raw): ?string
    {
        $list = self::normalizeEnvImages(is_array($raw) ? $raw : self::decodeEnvImages($raw));
        return $list ? json_encode(array_values($list), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }
}
