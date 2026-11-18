<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyHonor;
use App\Models\CompanyLink;
use App\Models\CompanyNews;
use App\Models\CompanyPage;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShopController extends Controller
{
    private function companyOf(Request $request): Company
    {
        $request->user()->ensureEnterprise();
        $company = $request->user()->company;
        if (! $company) {
            $company = Company::create([
                'user_id' => $request->user()->id,
                'name' => $request->user()->company_name ?: ($request->user()->name.'的旺铺'),
                'slug' => 'shop-'.$request->user()->id.'-'.Str::random(4),
                'contact' => $request->user()->name,
                'phone' => $request->user()->mobile,
                'status' => 'pending',
                'template' => 'default',
            ]);
        }

        return $company;
    }

    public function show(Request $request)
    {
        $company = $this->companyOf($request);
        $company->load([
            'news' => fn ($q) => $q->latest()->limit(20),
            'honors' => fn ($q) => $q->latest()->limit(20),
            'pages' => fn ($q) => $q->orderBy('sort')->limit(20),
            'links' => fn ($q) => $q->orderBy('sort')->limit(20),
        ]);

        return response()->json(['data' => $this->payload($company)]);
    }

    public function update(Request $request)
    {
        $company = $this->companyOf($request);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'logo' => 'nullable|string|max:500',
            'intro' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'area' => 'nullable|string|max:50',
            'business' => 'nullable|string|max:200',
            'website' => 'nullable|string|max:200',
            'contact' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:30',
            'template' => 'nullable|in:default,blue,orange',
        ]);
        $company->update($data);
        $request->user()->update(['company_name' => $data['name']]);

        return $this->show($request);
    }

    public function storeNews(Request $request)
    {
        $company = $this->companyOf($request);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'content' => 'nullable|string',
        ]);
        $news = $company->news()->create([...$data, 'status' => 'published']);

        return response()->json(['data' => ['id' => $news->id]], 201);
    }

    public function destroyNews(Request $request, CompanyNews $news)
    {
        abort_unless($news->company_id === $this->companyOf($request)->id, 403);
        $news->delete();

        return response()->json(['message' => 'ok']);
    }

    public function storeHonor(Request $request)
    {
        $company = $this->companyOf($request);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'image' => 'nullable|string|max:500',
            'authority' => 'nullable|string|max:100',
            'issued_at' => 'nullable|date',
        ]);
        $honor = $company->honors()->create($data);

        return response()->json(['data' => ['id' => $honor->id]], 201);
    }

    public function destroyHonor(Request $request, CompanyHonor $honor)
    {
        abort_unless($honor->company_id === $this->companyOf($request)->id, 403);
        $honor->delete();

        return response()->json(['message' => 'ok']);
    }

    public function storePage(Request $request)
    {
        $company = $this->companyOf($request);
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'slug' => 'nullable|string|max:80',
            'content' => 'nullable|string',
        ]);
        $slug = trim((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $base = Str::slug($data['title']);
            $slug = ($base !== '' ? $base : 'page').'-'.Str::lower(Str::random(4));
        }
        $page = $company->pages()->create([
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'] ?? null,
            'status' => 'published',
        ]);

        return response()->json(['data' => ['id' => $page->id]], 201);
    }

    public function destroyPage(Request $request, CompanyPage $page)
    {
        abort_unless($page->company_id === $this->companyOf($request)->id, 403);
        $page->delete();

        return response()->json(['message' => 'ok']);
    }

    public function storeLink(Request $request)
    {
        $company = $this->companyOf($request);
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'url' => 'required|url|max:255',
        ]);
        $link = $company->links()->create($data);

        return response()->json(['data' => ['id' => $link->id]], 201);
    }

    public function destroyLink(Request $request, CompanyLink $link)
    {
        abort_unless($link->company_id === $this->companyOf($request)->id, 403);
        $link->delete();

        return response()->json(['message' => 'ok']);
    }

    private function payload(Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'slug' => $company->slug,
            'logo' => MediaUrl::make($company->logo),
            'intro' => $company->intro,
            'content' => $company->content,
            'area' => $company->area,
            'business' => $company->business,
            'website' => $company->website,
            'contact' => $company->contact,
            'phone' => $company->phone,
            'status' => $company->status,
            'template' => $company->template ?: 'default',
            'is_certified' => (bool) $company->is_certified,
            'news' => ($company->news ?? collect())->map(fn ($n) => [
                'id' => $n->id, 'title' => $n->title, 'content' => $n->content,
                'status' => $n->status, 'created_at' => $n->created_at?->toDateTimeString(),
            ]),
            'honors' => ($company->honors ?? collect())->map(fn ($h) => [
                'id' => $h->id, 'title' => $h->title, 'image' => MediaUrl::make($h->image), 'authority' => $h->authority,
                'issued_at' => optional($h->issued_at)?->toDateString(),
            ]),
            'pages' => ($company->pages ?? collect())->map(fn ($p) => [
                'id' => $p->id, 'title' => $p->title, 'slug' => $p->slug, 'content' => $p->content,
            ]),
            'links' => ($company->links ?? collect())->map(fn ($l) => [
                'id' => $l->id, 'name' => $l->name, 'url' => $l->url,
            ]),
            'templates' => [
                ['code' => 'default', 'name' => '经典橙'],
                ['code' => 'blue', 'name' => '商务蓝'],
                ['code' => 'orange', 'name' => '活力橙深'],
            ],
        ];
    }
}
