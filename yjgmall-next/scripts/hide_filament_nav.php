<?php

$hide = [
    'JobPostResource',
    'KnowQuestionResource',
    'ClubPostResource',
    'BrandResource',
    'DownloadResource',
    'ExhibitResource',
    'GiftResource',
    'GroupDealResource',
    'PhotoResource',
    'VideoResource',
    'SpecialResource',
    'InvestProjectResource',
    'InvestLeadResource',
    'ArticleTopicResource',
];

$dir = dirname(__DIR__).'/app/Filament/Resources';
$method = <<<'PHP'

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
PHP;

foreach ($hide as $class) {
    $path = $dir.'/'.$class.'.php';
    if (! is_file($path)) {
        echo "MISS $class\n";
        continue;
    }
    $src = file_get_contents($path);
    if (str_contains($src, 'shouldRegisterNavigation')) {
        echo "SKIP $class\n";
        continue;
    }
    // insert before first "public static function form" or "public static function table" or "public static function getPages"
    if (! preg_match('/\n    public static function (form|table|getPages)/', $src, $m, PREG_OFFSET_CAPTURE)) {
        echo "NO INSERT POINT $class\n";
        continue;
    }
    $pos = $m[0][1];
    $src = substr($src, 0, $pos).$method."\n".substr($src, $pos);
    file_put_contents($path, $src);
    echo "OK $class\n";
}
