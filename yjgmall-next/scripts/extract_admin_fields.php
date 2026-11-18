<?php

$map = [
    'UserResource' => 'users',
    'SupplyResource' => 'supplies',
    'PurchaseResource' => 'purchases',
    'ProductResource' => 'products',
    'CompanyResource' => 'companies',
    'ArticleResource' => 'articles',
    'ArticleTopicResource' => 'article-topics',
    'BannerResource' => 'banners',
    'AdResource' => 'ads',
    'AdBookingResource' => 'ad-bookings',
    'SpreadResource' => 'spreads',
    'AnnouncementResource' => 'announcements',
    'HotwordResource' => 'hotwords',
    'FriendLinkResource' => 'friend-links',
    'PageResource' => 'pages',
    'CategoryResource' => 'categories',
    'OrderResource' => 'orders',
    'InquiryResource' => 'inquiries',
    'ProductReviewResource' => 'reviews',
    'WithdrawRequestResource' => 'withdraws',
    'VipApplicationResource' => 'vip-applications',
    'AskTicketResource' => 'ask-tickets',
    'JobPostResource' => 'jobs',
    'KnowQuestionResource' => 'know',
    'ClubPostResource' => 'club',
    'BrandResource' => 'brands',
    'DownloadResource' => 'downloads',
    'ExhibitResource' => 'exhibits',
    'GiftResource' => 'gifts',
    'GroupDealResource' => 'group-deals',
    'PhotoResource' => 'photos',
    'VideoResource' => 'videos',
    'SpecialResource' => 'specials',
    'QuoteProductResource' => 'quote-products',
    'InvestProjectResource' => 'invest-projects',
    'InvestLeadResource' => 'invest-leads',
];

$out = [];
$base = dirname(__DIR__).'/app/Filament/Resources';

foreach ($map as $class => $key) {
    $path = $base.'/'.$class.'.php';
    if (! is_file($path)) {
        fwrite(STDERR, "MISS $class\n");
        continue;
    }
    $src = file_get_contents($path);
    if (! preg_match('/->schema\(\[(.*)\]\);\s*\}\s*public static function table/s', $src, $m)
        && ! preg_match('/->schema\(\[(.*)\]\);\s*\}\s*$/s', $src, $m)) {
        // fallback: between schema([ and ]);
        if (! preg_match('/->schema\(\[(.*)\]\);/s', $src, $m)) {
            fwrite(STDERR, "NO SCHEMA $class\n");
            continue;
        }
    }
    $schema = $m[1];
    preg_match_all(
        '/Forms\\\\Components\\\\(\w+)::make\([\'"](\w+)[\'"]\)(.*?)(?=Forms\\\\Components\\\\|\z)/s',
        $schema,
        $mm,
        PREG_SET_ORDER
    );
    $fields = [];
    foreach ($mm as $b) {
        $comp = $b[1];
        $name = $b[2];
        $rest = $b[3];
        $label = $name;
        if (preg_match('/->label\([\'"]([^\'"]+)[\'"]\)/', $rest, $lm)) {
            $label = $lm[1];
        }
        $type = match ($comp) {
            'TextInput' => str_contains($rest, '->password(')
                ? 'password'
                : (str_contains($rest, '->numeric(') || str_contains($rest, '->integer(')
                    ? 'number'
                    : (str_contains($rest, '->email(') ? 'email' : 'text')),
            'Textarea' => 'textarea',
            'RichEditor' => 'richtext',
            'FileUpload' => 'image',
            'Toggle' => 'toggle',
            'Select' => 'select',
            'DatePicker' => 'date',
            'DateTimePicker' => 'datetime',
            default => 'text',
        };
        $opts = null;
        if (preg_match('/->options\(\[(.*?)\]\)/s', $rest, $om)) {
            $opts = [];
            if (preg_match_all('/[\'"]?([^\'\"\s]+)[\'"]?\s*=>\s*[\'"]([^\'"]+)[\'"]/', $om[1], $om2, PREG_SET_ORDER)) {
                foreach ($om2 as $o) {
                    $opts[$o[1]] = $o[2];
                }
            }
        }
        $rel = null;
        $relLabel = null;
        if (preg_match('/->relationship\([\'"](\w+)[\'"]\s*,\s*[\'"](\w+)[\'"]/', $rest, $rm)) {
            $type = 'relation';
            $rel = $rm[1];
            $relLabel = $rm[2];
        }
        $disabled = str_contains($rest, '->disabled(');
        $required = str_contains($rest, '->required(');
        $f = ['name' => $name, 'label' => $label, 'type' => $type];
        if ($opts) {
            $f['options'] = $opts;
        }
        if ($rel) {
            $f['relation'] = $rel;
            $f['relation_label'] = $relLabel;
        }
        if ($disabled) {
            $f['readonly'] = true;
        }
        if ($required) {
            $f['required'] = true;
        }
        if ($type === 'password') {
            $f['create_only'] = false; // show on edit but optional
        }
        // category relation → category widget
        if ($rel === 'category' || ($name === 'category_id')) {
            $f['type'] = 'category';
            unset($f['relation'], $f['relation_label']);
        }
        if ($rel === 'parent' || $name === 'parent_id') {
            $f['type'] = 'number';
            $f['hint'] = '父分类 ID，空为顶级';
            unset($f['relation'], $f['relation_label']);
        }
        $fields[] = $f;
    }
    $out[$key] = $fields;
    echo "$key: ".count($fields)." fields\n";
}

$php = "<?php\n\n/** Auto-extracted from Filament Resources — admin form schemas */\nreturn ".var_export($out, true).";\n";
$dest = dirname(__DIR__).'/config/admin_resource_fields.php';
file_put_contents($dest, $php);
echo "Wrote $dest (".count($out)." resources)\n";
