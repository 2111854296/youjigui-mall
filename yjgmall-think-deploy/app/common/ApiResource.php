<?php

namespace app\common;

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
            'is_elite' => (bool) ($s->is_elite ?? false),
            'level' => (int) ($s->level ?? 0),
            'views' => (int) ($s->views ?? 0),
            'refreshed_at' => $s->refreshed_at,
            'created_at' => $s->created_at,
            'category' => self::category($s->category ?? null),
            'user' => $user ? [
                'id' => (int) $user->id,
                'name' => $user->name,
                'company_name' => $user->company_name,
                'mobile' => $user->mobile,
                'qq' => $user->qq ?? null,
                'is_vip' => method_exists($user, 'isVip') ? $user->isVip() : false,
                'is_certified' => (bool) ($user->is_certified ?? false),
            ] : null,
        ];

        return $data;
    }

    public static function purchase($p): array
    {
        $user = $p->user ?? null;

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
            'level' => (int) ($p->level ?? 0),
            'views' => (int) ($p->views ?? 0),
            'refreshed_at' => $p->refreshed_at,
            'quotes_count' => (int) ($p->quotes_count ?? 0),
            'category' => self::category($p->category ?? null),
            'user' => $user ? [
                'id' => (int) $user->id,
                'name' => $user->name,
                'company_name' => $user->company_name,
                'mobile' => $user->mobile,
            ] : null,
        ];
    }

    public static function product($p): array
    {
        $user = $p->user ?? null;

        return [
            'id' => (int) $p->id,
            'title' => $p->title,
            'thumb' => $p->thumb,
            'price' => (string) $p->price,
            'stock' => (int) ($p->stock ?? 0),
            'unit' => $p->unit,
            'summary' => $p->summary,
            'content' => $p->content,
            'status' => $p->status,
            'sales' => (int) ($p->sales ?? 0),
            'views' => (int) ($p->views ?? 0),
            'category' => self::category($p->category ?? null),
            'user' => $user ? [
                'id' => (int) $user->id,
                'name' => $user->name,
                'company_name' => $user->company_name,
            ] : null,
        ];
    }

    public static function company($c, bool $detail = false): array
    {
        $data = [
            'id' => (int) $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'logo' => $c->logo,
            'contact' => $c->contact,
            'phone' => $c->phone,
            'website' => $c->website,
            'address' => $c->address,
            'area' => $c->area,
            'business' => $c->business,
            'intro' => $c->intro,
            'content' => $c->content,
            'status' => $c->status,
            'is_featured' => (bool) ($c->is_featured ?? false),
            'is_certified' => (bool) ($c->is_certified ?? false),
            'template' => $c->template ?: 'default',
            'views' => (int) ($c->views ?? 0),
            'category' => self::category($c->category ?? null),
        ];
        if ($detail) {
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
        }

        return $data;
    }

    public static function article($a): array
    {
        return [
            'id' => (int) $a->id,
            'title' => $a->title,
            'thumb' => $a->thumb,
            'author' => $a->author,
            'summary' => $a->summary,
            'content' => $a->content,
            'type' => $a->type,
            'status' => $a->status,
            'is_top' => (bool) ($a->is_top ?? false),
            'views' => (int) ($a->views ?? 0),
            'published_at' => $a->published_at,
            'category' => isset($a->category) && $a->category ? [
                'id' => (int) $a->category->id,
                'name' => $a->category->name,
                'slug' => $a->category->slug,
            ] : null,
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
}
