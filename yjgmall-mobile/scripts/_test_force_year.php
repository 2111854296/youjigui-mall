#!/usr/bin/env php
<?php
require '/www/wwwroot/g.aifeili.cn/vendor/autoload.php';
// quick reflection test via including class
$path='/www/wwwroot/g.aifeili.cn/app/common/QuotePriceImport.php';
$code=file_get_contents($path);
if (strpos($code,'forceYear')===false) { fwrite(STDERR,"no forceYear\n"); exit(1); }
// invoke private via reflection by eval small harness
class T extends \app\common\QuotePriceImport {
  public static function t($raw,$title,$y){
    $m=new ReflectionMethod(self::class,'dateInSheet');
    $m->setAccessible(true);
    return $m->invoke(null,$raw,$title,$y);
  }
  public static function n($raw,$y){
    $m=new ReflectionMethod(self::class,'normalizeDate');
    $m->setAccessible(true);
    $d=$m->invoke(null,$raw,$y);
    $f=new ReflectionMethod(self::class,'forceYear');
    $f->setAccessible(true);
    return $f->invoke(null,$d,$y);
  }
}
echo "dateInSheet 2026-01-05 with y=2025 => ".T::t('2026-01-05','标题',2025)."\n";
echo "dateInSheet excel-ish 1/5/26 y=2025 => ".T::t('1/5/26','标题',2025)."\n";
echo "normalize+force 2026/10/28 y=2025 => ".T::n('2026/10/28',2025)."\n";
echo "ok\n";
