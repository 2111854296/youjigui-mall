<?php

namespace app\common;

use think\facade\Db;

class AdminRecycle
{
    public static function ensureTable(): void
    {
        $rows = Db::query("SHOW TABLES LIKE 'admin_recycle'");
        if ($rows) {
            return;
        }
        Db::execute("CREATE TABLE `admin_recycle` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `resource` varchar(64) NOT NULL DEFAULT '',
          `record_id` int unsigned NOT NULL DEFAULT 0,
          `title` varchar(255) NOT NULL DEFAULT '',
          `payload` longtext NOT NULL,
          `deleted_by` int unsigned NOT NULL DEFAULT 0,
          `deleted_at` datetime NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_resource` (`resource`),
          KEY `idx_deleted_at` (`deleted_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function push(string $resource, array $row, int $deletedBy = 0): int
    {
        self::ensureTable();
        $id = (int) ($row['id'] ?? 0);
        $title = (string) ($row['title'] ?? $row['name'] ?? $row['username'] ?? ('#'.$id));
        unset($row['password'], $row['pay_password'], $row['remember_token']);
        Db::name('admin_recycle')->insert([
            'resource' => $resource,
            'record_id' => $id,
            'title' => mb_substr($title, 0, 250),
            'payload' => json_encode($row, JSON_UNESCAPED_UNICODE),
            'deleted_by' => $deletedBy,
            'deleted_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) Db::name('admin_recycle')->getLastInsID();
    }

    public static function list(int $page = 1, int $perPage = 20, string $q = ''): array
    {
        self::ensureTable();
        $query = Db::name('admin_recycle')->order('id', 'desc');
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($w) use ($like) {
                $w->whereLike('title', $like)->whereOr('resource', 'like', $like);
            });
        }
        $paginator = $query->paginate(['list_rows' => $perPage, 'page' => $page]);
        $items = array_map(static function ($row) {
            return is_array($row) ? $row : (array) $row;
        }, $paginator->items());

        return [
            'data' => array_values($items),
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'last_page' => (int) $paginator->lastPage(),
                'total' => (int) $paginator->total(),
            ],
        ];
    }

    public static function restore(int $recycleId, callable $restoreFn): void
    {
        self::ensureTable();
        $row = Db::name('admin_recycle')->where('id', $recycleId)->find();
        if (!$row) {
            throw new \RuntimeException('回收站记录不存在', 404);
        }
        $payload = json_decode((string) $row['payload'], true);
        if (!is_array($payload)) {
            throw new \RuntimeException('备份数据损坏，无法还原', 422);
        }
        $restoreFn((string) $row['resource'], $payload);
        Db::name('admin_recycle')->where('id', $recycleId)->delete();
    }

    public static function purge(int $recycleId): void
    {
        self::ensureTable();
        Db::name('admin_recycle')->where('id', $recycleId)->delete();
    }
}
