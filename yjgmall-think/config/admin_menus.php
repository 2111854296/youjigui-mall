<?php
/**
 * Destoon-style fixed module menus (injected before auto resource groups).
 */
return [
    [
        'group' => '资讯',
        'items' => [
            ['key' => 'news-add', 'label' => '添加资讯', 'to' => '/admin/articles/edit'],
            ['key' => 'articles', 'label' => '资讯列表', 'to' => '/admin/r/articles'],
            ['key' => 'news-audit', 'label' => '待审资讯', 'to' => '/admin/r/articles?status=pending'],
            ['key' => 'news-cats', 'label' => '资讯分类', 'to' => '/admin/r/categories?module=article'],
            ['key' => 'news-refresh', 'label' => '更新数据', 'to' => '/admin/modules/news/refresh'],
            ['key' => 'news-settings', 'label' => '资讯设置', 'to' => '/admin/modules/news/settings'],
        ],
    ],
    [
        'group' => '行情报价',
        'items' => [
            ['key' => 'quote-products', 'label' => '行情品种', 'to' => '/admin/r/quote-products'],
        ],
    ],
    [
        'group' => '企业库',
        'items' => [
            ['key' => 'companies', 'label' => '公司列表', 'to' => '/admin/r/companies'],
            ['key' => 'company-vip', 'label' => '企业VIP指数', 'to' => '/admin/r/companies?vip=1'],
            ['key' => 'company-cats', 'label' => '行业分类', 'to' => '/admin/r/categories?module=company'],
            ['key' => 'company-honors', 'label' => '荣誉资质', 'to' => '/admin/r/company-honors'],
            ['key' => 'company-news', 'label' => '公司新闻', 'to' => '/admin/r/company-news'],
            ['key' => 'company-pages', 'label' => '公司单页', 'to' => '/admin/r/company-pages'],
            ['key' => 'company-links', 'label' => '公司友情链接', 'to' => '/admin/r/company-links'],
            ['key' => 'company-templates', 'label' => '公司模板', 'to' => '/admin/modules/company/templates'],
            ['key' => 'company-refresh', 'label' => '更新数据', 'to' => '/admin/modules/company/refresh'],
            ['key' => 'company-settings', 'label' => '企业库设置', 'to' => '/admin/modules/company/settings'],
        ],
    ],
];
