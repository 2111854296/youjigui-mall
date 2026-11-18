<?php
/**
 * Ensure articles Destoon-parity columns exist.
 * php scripts/upgrade_articles_columns.php
 */
require __DIR__ . '/../vendor/autoload.php';

$app = new think\App();
$app->initialize();

use think\facade\Db;

function hasColumn(string $table, string $col): bool
{
    $rows = Db::query(
        'SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
        [$table, $col]
    );
    return (int) ($rows[0]['c'] ?? 0) > 0;
}

$cols = [
    'source' => "ALTER TABLE `articles` ADD COLUMN `source` VARCHAR(120) NULL DEFAULT NULL COMMENT '资讯来源'",
    'source_url' => "ALTER TABLE `articles` ADD COLUMN `source_url` VARCHAR(255) NULL DEFAULT NULL COMMENT '来源链接'",
    'level' => "ALTER TABLE `articles` ADD COLUMN `level` INT NOT NULL DEFAULT 0 COMMENT '信息级别'",
    'area' => "ALTER TABLE `articles` ADD COLUMN `area` VARCHAR(120) NULL DEFAULT NULL COMMENT '地区'",
    'sort' => "ALTER TABLE `articles` ADD COLUMN `sort` INT NOT NULL DEFAULT 0 COMMENT '排序'",
    'view_boost' => "ALTER TABLE `articles` ADD COLUMN `view_boost` INT NOT NULL DEFAULT 0 COMMENT '后台增加浏览量（前台显示=真实views+此值）'",
];

foreach ($cols as $name => $sql) {
    if (hasColumn('articles', $name)) {
        echo "skip $name\n";
        continue;
    }
    echo "add $name ... ";
    Db::execute($sql);
    echo "ok\n";
}

$n = Db::name('articles')->where('status', 'draft')->update(['status' => 'pending']);
echo "draft_to_pending=$n\n";
echo "DONE\n";
