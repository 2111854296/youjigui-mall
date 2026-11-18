<?php

namespace app\controller\api\v1;

use app\BaseController;
use app\model\Article;
use app\model\Company;
use app\model\CompanyHonor;
use app\model\CompanyLink;
use app\model\CompanyNews;
use app\model\CompanyPage;
use app\model\SiteSetting;
use think\Response;

class AdminModule extends BaseController
{
    private const MODULES = ['news', 'company'];

    private const SETTINGS = [
        'news' => [
            'news_page_size' => '20',
            'news_need_check' => '1',
            'news_list_order' => 'id_desc',
        ],
        'company' => [
            'company_need_check' => '1',
            'company_vip_min' => '1',
            'company_page_size' => '20',
            'company_default_template' => 'atelier',
        ],
    ];

    private const TEMPLATES = [
        'atelier' => '展厅·清雅',
        'forge' => '工场·沉稳',
        'ledger' => '名录·商务',
    ];

    public function refresh(string $module): Response
    {
        $module = $this->assertModule($module);
        $stats = $module === 'news' ? $this->refreshNews() : $this->refreshCompany();
        return json(['data' => $stats, 'message' => '更新完成']);
    }

    public function settingsShow(string $module): Response
    {
        $module = $this->assertModule($module);
        return json_data($this->loadSettings($module));
    }

    public function settingsUpdate(string $module): Response
    {
        $module = $this->assertModule($module);
        $defaults = self::SETTINGS[$module];
        $payload = $this->request->post();
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }
            $val = trim((string) $payload[$key]);
            $row = SiteSetting::where('key', $key)->find();
            if ($row) {
                $row->value = $val;
                $row->save();
            } else {
                SiteSetting::create(['key' => $key, 'value' => $val]);
            }
        }
        return json(['data' => $this->loadSettings($module), 'message' => '已保存']);
    }

    public function templates(): Response
    {
        $rows = [];
        foreach (self::TEMPLATES as $code => $name) {
            $rows[] = [
                'code' => $code,
                'name' => $name,
                'count' => (int) Company::where('template', $code)->count(),
            ];
        }
        $other = (int) Company::where(function ($q) {
            $codes = array_keys(self::TEMPLATES);
            $q->whereNull('template')->whereOr('template', '')->whereOr(function ($w) use ($codes) {
                $w->whereNotIn('template', $codes);
            });
        })->count();
        return json_data(['templates' => $rows, 'other' => $other]);
    }

    public function assignTemplate(): Response
    {
        $code = (string) $this->request->post('template', '');
        if (!isset(self::TEMPLATES[$code])) {
            return json_error('未知模板', 422);
        }
        $ids = $this->request->post('ids', []);
        if (!is_array($ids) || !$ids) {
            return json_error('请选择企业', 422);
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));
        $n = Company::whereIn('id', $ids)->update(['template' => $code]);
        return json(['data' => ['updated' => (int) $n], 'message' => '已更新模板']);
    }

    private function assertModule(string $module): string
    {
        $module = strtolower(trim($module));
        if (!in_array($module, self::MODULES, true)) {
            throw new \think\exception\HttpException(404, '模块不存在');
        }
        return $module;
    }

    private function loadSettings(string $module): array
    {
        $out = self::SETTINGS[$module];
        try {
            $keys = array_keys($out);
            $rows = SiteSetting::whereIn('key', $keys)->select();
            foreach ($rows as $row) {
                $k = (string) $row->getAttr('key');
                if (isset($out[$k])) {
                    $out[$k] = (string) ($row->value ?? '');
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $out;
    }

    private function refreshNews(): array
    {
        $fixedPub = 0;
        $fixedStatus = 0;
        $allowed = ['published', 'pending', 'rejected', 'offline', 'draft'];
        $rows = Article::where(function ($q) {
            $q->whereNull('published_at')->whereOr('published_at', '');
        })->where('status', 'published')->limit(500)->select();
        foreach ($rows as $row) {
            $row->save(['published_at' => date('Y-m-d H:i:s')]);
            $fixedPub++;
        }
        $bad = Article::whereNotIn('status', $allowed)->limit(200)->select();
        foreach ($bad as $row) {
            $row->save(['status' => 'pending']);
            $fixedStatus++;
        }
        return [
            'published_at_fixed' => $fixedPub,
            'status_fixed' => $fixedStatus,
            'total' => (int) Article::count(),
            'pending' => (int) Article::where('status', 'pending')->count(),
            'published' => (int) Article::where('status', 'published')->count(),
        ];
    }

    private function refreshCompany(): array
    {
        $slugFixed = 0;
        $vipFixed = 0;
        $emptySlug = Company::where(function ($q) {
            $q->whereNull('slug')->whereOr('slug', '');
        })->limit(500)->select();
        foreach ($emptySlug as $row) {
            $row->save(['slug' => 'c'.$row->id]);
            $slugFixed++;
        }
        $vipRows = Company::where(function ($q) {
            $q->whereNull('vip_level');
        })->limit(500)->select();
        foreach ($vipRows as $row) {
            $lv = 0;
            if ((int) ($row->is_certified ?? 0)) {
                $lv = 5;
            } elseif ((int) ($row->is_featured ?? 0)) {
                $lv = 3;
            }
            $row->save(['vip_level' => $lv]);
            $vipFixed++;
        }
        $companyIds = Company::column('id') ?: [0];
        $orphanNews = CompanyNews::whereNotIn('company_id', $companyIds)->delete();
        $orphanHonors = CompanyHonor::whereNotIn('company_id', $companyIds)->delete();
        $orphanPages = CompanyPage::whereNotIn('company_id', $companyIds)->delete();
        $orphanLinks = CompanyLink::whereNotIn('company_id', $companyIds)->delete();
        return [
            'slug_fixed' => $slugFixed,
            'vip_fixed' => $vipFixed,
            'orphan_news' => (int) $orphanNews,
            'orphan_honors' => (int) $orphanHonors,
            'orphan_pages' => (int) $orphanPages,
            'orphan_links' => (int) $orphanLinks,
            'total' => (int) Company::count(),
            'vip' => (int) Company::where('vip_level', '>', 0)->count(),
            'pending' => (int) Company::where('status', 'pending')->count(),
        ];
    }
}
