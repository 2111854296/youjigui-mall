<?php
/** php scripts/upgrade_shop_showcase.php */
require __DIR__ . '/../vendor/autoload.php';
$app = new think\App();
$app->initialize();
use think\facade\Db;

$cols = Db::query("SHOW COLUMNS FROM products LIKE 'is_showcase'");
if (!$cols) {
    Db::execute('ALTER TABLE products ADD is_showcase TINYINT NOT NULL DEFAULT 0');
    echo "added is_showcase\n";
} else {
    echo "is_showcase exists\n";
}
$listed = Db::query("SHOW COLUMNS FROM products LIKE 'is_listed'");
if (!$listed) {
    Db::execute("ALTER TABLE products ADD is_listed TINYINT NOT NULL DEFAULT 1 AFTER status");
    echo "added is_listed\n";
} else {
    echo "is_listed exists\n";
}

// 演示旺铺东莞德鑫：补回同店可展示商品（此前仅 1 个上架，侧栏本店商品为空）
$demoUid = 130204;
$demoCid = 1;
$co = Db::query('SELECT id FROM companies WHERE id=? AND user_id=? LIMIT 1', [$demoCid, $demoUid]);
if ($co) {
    $n = Db::execute(
        "UPDATE products SET status='approved', is_listed=1, company_id=? WHERE user_id=? AND id IN (1,2,3) AND status IN ('offline','pending')",
        [$demoCid, $demoUid]
    );
    echo "demo_relist={$n}\n";
    $b = Db::execute(
        "UPDATE products SET company_id=? WHERE user_id=? AND (company_id IS NULL OR company_id=0)",
        [$demoCid, $demoUid]
    );
    echo "demo_bind_company={$b}\n";
} else {
    echo "demo_shop_skip\n";
}

echo "SHOP_SHOWCASE_OK\n";
