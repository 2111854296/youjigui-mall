<?php
/**
 * 资讯栏目子集：网评（日/周/月/年）、产能（产能/开工率）
 * 与「价格行情资讯 → 行情品种」同理，后台用所属分类挂文章。
 */
require dirname(__DIR__) . '/vendor/autoload.php';
$app = new think\App();
$app->initialize();

function ensureChild(int $parentId, string $slug, string $name, int $sort): int
{
    $row = think\facade\Db::query(
        'SELECT id FROM categories WHERE slug=? LIMIT 1',
        [$slug]
    );
    if ($row) {
        think\facade\Db::execute(
            'UPDATE categories SET name=?, parent_id=?, module=?, sort=?, is_active=1, updated_at=NOW() WHERE id=?',
            [$name, $parentId, 'article', $sort, (int) $row[0]['id']]
        );
        echo "UPD {$slug} #{$row[0]['id']}\n";

        return (int) $row[0]['id'];
    }
    think\facade\Db::execute(
        'INSERT INTO categories (name, slug, module, parent_id, icon, sort, is_active, created_at, updated_at)
         VALUES (?,?,?,?,NULL,?,1,NOW(),NOW())',
        [$name, $slug, 'article', $parentId, $sort]
    );
    $idRow = think\facade\Db::query('SELECT id FROM categories WHERE slug=? LIMIT 1', [$slug]);
    $id = (int) ($idRow[0]['id'] ?? 0);
    echo "ADD {$slug} #{$id}\n";

    return $id;
}

$parent = think\facade\Db::query(
    "SELECT id, slug FROM categories WHERE module='article' AND slug IN ('news-review','news-capacity','news-price')"
);
$map = [];
foreach ($parent as $r) {
    $map[$r['slug']] = (int) $r['id'];
}

if (empty($map['news-review'])) {
    echo "MISSING news-review\n";
    exit(1);
}
if (empty($map['news-capacity'])) {
    echo "MISSING news-capacity\n";
    exit(1);
}

$review = [
    ['news-review-daily', '日评', 1],
    ['news-review-weekly', '周评', 2],
    ['news-review-monthly', '月评', 3],
    ['news-review-yearly', '年度盘点', 4],
];
foreach ($review as [$slug, $name, $sort]) {
    ensureChild($map['news-review'], $slug, $name, $sort);
}

$cap = [
    ['news-capacity-output', '产能', 1],
    ['news-capacity-rate', '开工率', 2],
];
foreach ($cap as [$slug, $name, $sort]) {
    ensureChild($map['news-capacity'], $slug, $name, $sort);
}

echo "DONE\n";
$rows = think\facade\Db::query(
    "SELECT id,parent_id,slug,name FROM categories WHERE module='article' AND (slug LIKE 'news-review%' OR slug LIKE 'news-capacity%') ORDER BY parent_id,sort,id"
);
foreach ($rows as $r) {
    echo "{$r['id']}\t{$r['parent_id']}\t{$r['slug']}\t{$r['name']}\n";
}
