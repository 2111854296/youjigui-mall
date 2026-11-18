<?php
/** php scripts/upgrade_admin_security.php */
require __DIR__ . '/../vendor/autoload.php';
$app = new think\App();
$app->initialize();

app\common\AdminRecycle::ensureTable();
echo "admin_recycle ready\n";
echo "ADMIN_SECURITY_OK\n";
