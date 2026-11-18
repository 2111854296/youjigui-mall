#!/bin/bash
set -e
SITE=/www/wwwroot/g.aifeili.cn
cd "$SITE"

# .env 键名为 DB_NAME/DB_USER/DB_PASS，且可能带空格
DBH=$(grep -E '^[[:space:]]*DB_HOST[[:space:]]*=' .env | head -1 | sed 's/^[^=]*=[[:space:]]*//;s/[[:space:]]*$//;s/^["'\'']//;s/["'\'']$//')
DBD=$(grep -E '^[[:space:]]*DB_NAME[[:space:]]*=' .env | head -1 | sed 's/^[^=]*=[[:space:]]*//;s/[[:space:]]*$//;s/^["'\'']//;s/["'\'']$//')
DBU=$(grep -E '^[[:space:]]*DB_USER[[:space:]]*=' .env | head -1 | sed 's/^[^=]*=[[:space:]]*//;s/[[:space:]]*$//;s/^["'\'']//;s/["'\'']$//')
DBP=$(grep -E '^[[:space:]]*DB_PASS[[:space:]]*=' .env | head -1 | sed 's/^[^=]*=[[:space:]]*//;s/[[:space:]]*$//;s/^["'\'']//;s/["'\'']$//')
DBH=${DBH:-127.0.0.1}

if [ -z "$DBD" ] || [ -z "$DBU" ]; then
  echo "NO_DB parsed DBD='$DBD' DBU='$DBU'"
  exit 1
fi
echo "DB=$DBD host=$DBH user=$DBU"
export MYSQL_PWD="$DBP"

STAMP=$(date +%Y%m%d_%H%M%S)
BAK=/root/yjgmall_demo_clear_backup_$STAMP.sql.gz
echo "=== BACKUP -> $BAK ==="
mysqldump -h"$DBH" -u"$DBU" "$DBD" \
  supplies purchases products companies \
  quote_products quote_product_prices quote_region_prices \
  --single-transaction --quick \
  | gzip -c > "$BAK"
ls -lh "$BAK"
test -s "$BAK"

echo "=== BEFORE COUNTS ==="
mysql -h"$DBH" -u"$DBU" "$DBD" -N -B -e "
SELECT 'articles', COUNT(*) FROM articles;
SELECT 'supplies_approved', COUNT(*) FROM supplies WHERE status='approved';
SELECT 'purchases_approved', COUNT(*) FROM purchases WHERE status='approved';
SELECT 'products_listed', COUNT(*) FROM products WHERE status IN ('approved','published') AND IFNULL(is_listed,1)=1;
SELECT 'companies_approved', COUNT(*) FROM companies WHERE status='approved';
SELECT 'quote_active', COUNT(*) FROM quote_products WHERE IFNULL(is_active,1)=1;
SELECT 'quote_dmc', id, name FROM quote_products WHERE name LIKE '%DMC%' OR name LIKE '%二甲基环硅氧烷%' OR name LIKE '%dmc%';
"

echo "=== APPLY SOFT CLEAR ==="
HAS_QPP=$(mysql -h"$DBH" -u"$DBU" "$DBD" -N -B -e "SHOW TABLES LIKE 'quote_product_prices'")
HAS_QRP=$(mysql -h"$DBH" -u"$DBU" "$DBD" -N -B -e "SHOW TABLES LIKE 'quote_region_prices'")
HAS_LISTED=$(mysql -h"$DBH" -u"$DBU" "$DBD" -N -B -e "SHOW COLUMNS FROM products LIKE 'is_listed'")

SQL="START TRANSACTION;
UPDATE supplies SET status='offline' WHERE status <> 'offline';
UPDATE purchases SET status='offline' WHERE status <> 'offline';
UPDATE companies SET status='offline' WHERE status <> 'offline';
"
if [ -n "$HAS_LISTED" ]; then
  SQL+="UPDATE products SET status='offline', is_listed=0 WHERE status <> 'offline' OR IFNULL(is_listed,1)=1;"
else
  SQL+="UPDATE products SET status='offline' WHERE status <> 'offline';"
fi
SQL+="
UPDATE quote_products
SET is_active=0, price_min=NULL, price_max=NULL, price_avg=NULL
WHERE NOT (name LIKE '%DMC%' OR name LIKE '%dmc%' OR name LIKE '%二甲基环硅氧烷%');
"
if [ -n "$HAS_QPP" ]; then
  SQL+="
DELETE FROM quote_product_prices
WHERE product_id NOT IN (
  SELECT id FROM (
    SELECT id FROM quote_products
    WHERE name LIKE '%DMC%' OR name LIKE '%dmc%' OR name LIKE '%二甲基环硅氧烷%'
  ) t
);"
fi
if [ -n "$HAS_QRP" ]; then
  SQL+="
DELETE FROM quote_region_prices
WHERE product_id NOT IN (
  SELECT id FROM (
    SELECT id FROM quote_products
    WHERE name LIKE '%DMC%' OR name LIKE '%dmc%' OR name LIKE '%二甲基环硅氧烷%'
  ) t
);"
fi
SQL+="
UPDATE quote_products SET is_active=1
WHERE name LIKE '%DMC%' OR name LIKE '%dmc%' OR name LIKE '%二甲基环硅氧烷%';
COMMIT;"

mysql -h"$DBH" -u"$DBU" "$DBD" -e "$SQL"

echo "=== AFTER COUNTS ==="
mysql -h"$DBH" -u"$DBU" "$DBD" -N -B -e "
SELECT 'articles', COUNT(*) FROM articles;
SELECT 'supplies_approved', COUNT(*) FROM supplies WHERE status='approved';
SELECT 'purchases_approved', COUNT(*) FROM purchases WHERE status='approved';
SELECT 'products_listed', COUNT(*) FROM products WHERE status IN ('approved','published') AND IFNULL(is_listed,1)=1;
SELECT 'companies_approved', COUNT(*) FROM companies WHERE status='approved';
SELECT 'quote_active', COUNT(*) FROM quote_products WHERE IFNULL(is_active,1)=1;
SELECT 'quote_active_names', GROUP_CONCAT(name SEPARATOR ' | ') FROM quote_products WHERE IFNULL(is_active,1)=1;
SELECT 'quote_prices_left', COUNT(*) FROM quote_product_prices;
SELECT 'region_prices_left', COUNT(*) FROM quote_region_prices;
SELECT 'dmc_price_avg', id, name, price_avg FROM quote_products WHERE name LIKE '%DMC%' OR name LIKE '%dmc%' OR name LIKE '%二甲基环硅氧烷%' LIMIT 5;
"

echo "=== CLEAR CACHE ==="
rm -rf "$SITE/runtime/cache/"* "$SITE/runtime/temp/"* 2>/dev/null || true
find "$SITE/runtime" -type f \( -name '*home*' -o -name '*quote*' \) -delete 2>/dev/null || true

echo "=== API SPOTCHECK ==="
curl -sS -o /tmp/h_clear.json -w "HOME:%{http_code}\n" "https://g.aifeili.cn/api/v1/home"
curl -sS -o /tmp/q_clear.json -w "QUOTE:%{http_code}\n" "https://g.aifeili.cn/api/v1/quotes?page=1"
/www/server/php/83/bin/php -d display_errors=0 -r '
$h=json_decode(@file_get_contents("/tmp/h_clear.json"), true);
$d=$h["data"]??$h;
$sup=$d["supplies"]??$d["latest_supplies"]??[];
$pur=$d["purchases"]??$d["latest_purchases"]??[];
$sup=is_array($sup)&&isset($sup["data"])?$sup["data"]:$sup;
$pur=is_array($pur)&&isset($pur["data"])?$pur["data"]:$pur;
$co=$d["companies"]??$d["featured_companies"]??[];
$co=is_array($co)&&isset($co["data"])?$co["data"]:$co;
echo "home_sup=".count(is_array($sup)?$sup:[])." pur=".count(is_array($pur)?$pur:[])." co=".count(is_array($co)?$co:[])."\n";
$q=json_decode(@file_get_contents("/tmp/q_clear.json"), true);
$qd=$q["data"]??$q;
$items=is_array($qd)?(isset($qd["data"])?$qd["data"]:(isset($qd["list"])?$qd["list"]:$qd)): [];
if(!is_array($items)) $items=[];
$names=[];
foreach($items as $x){ if(is_array($x)) $names[]=($x["name"]??$x["title"]??"?"); }
echo "quote_n=".count($items)." names=".implode("|",$names)."\n";
'

echo "BACKUP_FILE=$BAK"
echo "CLEAR_DEMO_OK"
