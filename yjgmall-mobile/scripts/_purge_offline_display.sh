#!/bin/bash
set -e
SITE=/www/wwwroot/g.aifeili.cn
cd "$SITE"

DBH=$(grep -E '^[[:space:]]*DB_HOST[[:space:]]*=' .env | head -1 | sed 's/^[^=]*=[[:space:]]*//;s/[[:space:]]*$//;s/^["'\'']//;s/["'\'']$//')
DBD=$(grep -E '^[[:space:]]*DB_NAME[[:space:]]*=' .env | head -1 | sed 's/^[^=]*=[[:space:]]*//;s/[[:space:]]*$//;s/^["'\'']//;s/["'\'']$//')
DBU=$(grep -E '^[[:space:]]*DB_USER[[:space:]]*=' .env | head -1 | sed 's/^[^=]*=[[:space:]]*//;s/[[:space:]]*$//;s/^["'\'']//;s/["'\'']$//')
DBP=$(grep -E '^[[:space:]]*DB_PASS[[:space:]]*=' .env | head -1 | sed 's/^[^=]*=[[:space:]]*//;s/[[:space:]]*$//;s/^["'\'']//;s/["'\'']$//')
DBH=${DBH:-127.0.0.1}
export MYSQL_PWD="$DBP"
echo "DB=$DBD host=$DBH user=$DBU"

STAMP=$(date +%Y%m%d_%H%M%S)
BAK=/root/yjgmall_purge_offline_backup_$STAMP.sql.gz
echo "=== BACKUP -> $BAK ==="
mysqldump -h"$DBH" -u"$DBU" "$DBD" \
  supplies purchases products companies \
  --single-transaction --quick \
  | gzip -c > "$BAK"
ls -lh "$BAK"
test -s "$BAK"

echo "=== BEFORE ==="
mysql -h"$DBH" -u"$DBU" "$DBD" -N -B -e "
SELECT 'companies_all', COUNT(*) FROM companies;
SELECT 'companies_offline', COUNT(*) FROM companies WHERE status='offline';
SELECT 'supplies_offline', COUNT(*) FROM supplies WHERE status='offline';
SELECT 'purchases_offline', COUNT(*) FROM purchases WHERE status='offline';
SELECT 'products_offline', COUNT(*) FROM products WHERE status='offline';
SELECT 'articles', COUNT(*) FROM articles;
SELECT 'quote_active', COUNT(*) FROM quote_products WHERE IFNULL(is_active,1)=1;
"

echo "=== PURGE OFFLINE ==="
mysql -h"$DBH" -u"$DBU" "$DBD" -e "
START TRANSACTION;
DELETE FROM supplies WHERE status='offline';
DELETE FROM purchases WHERE status='offline';
DELETE FROM products WHERE status='offline';
DELETE FROM companies WHERE status='offline';
COMMIT;
"

echo "=== AFTER ==="
mysql -h"$DBH" -u"$DBU" "$DBD" -N -B -e "
SELECT 'companies_all', COUNT(*) FROM companies;
SELECT 'companies_by_status', status, COUNT(*) FROM companies GROUP BY status;
SELECT 'supplies_all', COUNT(*) FROM supplies;
SELECT 'purchases_all', COUNT(*) FROM purchases;
SELECT 'products_all', COUNT(*) FROM products;
SELECT 'articles', COUNT(*) FROM articles;
SELECT 'quote_active_names', GROUP_CONCAT(name SEPARATOR ' | ') FROM quote_products WHERE IFNULL(is_active,1)=1;
"

echo "=== CLEAR CACHE ==="
rm -rf "$SITE/runtime/cache/"* "$SITE/runtime/temp/"* 2>/dev/null || true

echo "BACKUP_FILE=$BAK"
echo "PURGE_OFFLINE_OK"
