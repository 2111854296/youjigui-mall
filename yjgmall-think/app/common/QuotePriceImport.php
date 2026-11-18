<?php

namespace app\common;

use app\model\QuoteProduct;
use app\model\QuoteProductPrice;

/** 行情报价历史：一份按天的表同时供折线和 K 线使用。 */
class QuotePriceImport
{
    /**
     * @return array{imported:int,updated:int,skipped:int,errors:list<string>}
     */
    public static function fromFile(string $path, string $ext, int $productId = 0, int $defaultYear = 0): array
    {
        $ext = strtolower($ext);
        $productId = max(0, $productId);
        $defaultYear = ($defaultYear >= 2000 && $defaultYear <= 2100) ? $defaultYear : 0;
        if ($ext === 'xlsx') {
            $grid = self::rowsFromXlsx($path);
        } elseif ($ext === 'csv' || $ext === 'txt') {
            $grid = self::rowsFromCsv($path);
        } else {
            throw new \InvalidArgumentException('请上传 .xlsx 或 .csv。旧版 .xls 请另存为 xlsx 后再传');
        }
        if (count($grid) < 2) {
            throw new \InvalidArgumentException('表格至少要有表头和一行数据');
        }

        $titleYear = self::yearFromText((string) ($grid[0][0] ?? ''));
        $yearHint = $defaultYear > 0 ? $defaultYear : $titleYear;

        $wide = self::wideLayout($grid);
        if ($wide !== null) {
            return self::importWide($grid, $wide, $productId, $yearHint);
        }

        $header = array_shift($grid);
        $cols = self::mapHeader($header);
        if ($productId > 0) {
            if (!QuoteProduct::find($productId)) {
                throw new \InvalidArgumentException('这个行情品种不存在');
            }
            foreach (['date', 'avg'] as $need) {
                if (!isset($cols[$need])) {
                    throw new \InvalidArgumentException('表头要有：日期、均价。最低价和最高价可以不填');
                }
            }
        } else {
            foreach (['name', 'date', 'avg'] as $need) {
                if (!isset($cols[$need])) {
                    throw new \InvalidArgumentException('表头需要包含：品种、日期、均价。最低价、最高价可以空');
                }
            }
        }

        $products = [];
        if ($productId <= 0) {
            foreach (QuoteProduct::field('id,name')->select() as $p) {
                $name = trim((string) $p->name);
                if ($name !== '') {
                    $products[$name] = (int) $p->id;
                }
            }
        }

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $touched = [];

        foreach ($grid as $i => $line) {
            $rowNo = $i + 2;
            $dateRaw = trim((string) ($line[$cols['date']] ?? ''));
            $avgRaw = trim((string) ($line[$cols['avg']] ?? ''));
            if ($productId > 0) {
                if ($dateRaw === '' && $avgRaw === '') {
                    continue;
                }
                $pid = $productId;
            } else {
                $name = trim((string) ($line[$cols['name']] ?? ''));
                if ($name === '' && $dateRaw === '' && $avgRaw === '') {
                    continue;
                }
                if ($name === '' || !isset($products[$name])) {
                    $skipped++;
                    if (count($errors) < 30) {
                        $errors[] = "第{$rowNo}行：品种「{$name}」在行情里不存在，未导入";
                    }
                    continue;
                }
                $pid = $products[$name];
            }
            $date = self::normalizeDate($dateRaw, $yearHint);
            if ($date !== '' && $defaultYear >= 2000 && $defaultYear <= 2100) {
                $date = self::forceYear($date, $defaultYear);
            }
            if ($date === '') {
                $skipped++;
                if (count($errors) < 30) {
                    $errors[] = "第{$rowNo}行：日期「{$dateRaw}」无法识别，请写成 2026-09-01 或 1/1/26";
                }
                continue;
            }
            $avg = self::num($avgRaw);
            if ($avg === null) {
                $skipped++;
                if (count($errors) < 30) {
                    $errors[] = "第{$rowNo}行：均价为空或不是数字";
                }
                continue;
            }
            $min = isset($cols['min']) ? self::num((string) ($line[$cols['min']] ?? '')) : null;
            $max = isset($cols['max']) ? self::num((string) ($line[$cols['max']] ?? '')) : null;
            $exists = QuoteProductPrice::where('product_id', $pid)->where('quoted_on', $date)->find();
            $payload = [
                'product_id' => $pid,
                'quoted_on' => $date,
                'price_avg' => $avg,
                'price_min' => $min,
                'price_max' => $max,
            ];
            if ($exists) {
                $exists->save($payload);
                $updated++;
            } else {
                QuoteProductPrice::create($payload);
                $imported++;
            }
            $touched[$pid] = true;
        }

        foreach (array_keys($touched) as $pid) {
            $latest = QuoteProductPrice::where('product_id', $pid)->order('quoted_on', 'desc')->find();
            $product = QuoteProduct::find($pid);
            if (!$latest || !$product) {
                continue;
            }
            $product->save([
                'price_avg' => $latest->price_avg,
                'price_min' => $latest->price_min,
                'price_max' => $latest->price_max,
                'quoted_on' => substr((string) $latest->quoted_on, 0, 10),
            ]);
        }

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * 客户原表：标题行 +「日期、若干地区或企业、最低报价、最高报价、均价」。
     * 地区列以这一次的表头为准，这个品种上一次有、这次没有的地区会去掉。
     *
     * @param list<list<string>> $grid
     * @param array{headerIndex:int,dateCol:int,regions:array<string,int>,stat:array<string,int>,title:string} $wide
     * @return array{imported:int,updated:int,skipped:int,errors:list<string>,regions:list<string>}
     */
    private static function importWide(array $grid, array $wide, int $productId, int $defaultYear = 0): array
    {
        self::ensureRegionTable();
        $productId = self::resolveWideProduct($productId, $wide['title']);
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $regionNames = array_keys($wide['regions']);
        $touchedDates = [];

        for ($i = $wide['headerIndex'] + 1, $n = count($grid); $i < $n; $i++) {
            $line = $grid[$i];
            $rowNo = $i + 1;
            $dateRaw = trim((string) ($line[$wide['dateCol']] ?? ''));
            if ($dateRaw === '') {
                continue;
            }
            $date = self::dateInSheet($dateRaw, $wide['title'], $defaultYear);
            if ($date === '') {
                $skipped++;
                if (count($errors) < 30) {
                    $errors[] = "第{$rowNo}行：日期「{$dateRaw}」无法识别";
                }
                continue;
            }
            $regionPrices = [];
            foreach ($wide['regions'] as $region => $col) {
                $price = self::num((string) ($line[$col] ?? ''));
                if ($price === null) {
                    continue;
                }
                $regionPrices[] = $price;
                $exists = \app\model\QuoteRegionPrice::where('product_id', $productId)
                    ->where('region', $region)
                    ->where('quoted_on', $date)
                    ->find();
                if ($exists) {
                    $exists->save(['price' => $price]);
                    $updated++;
                } else {
                    \app\model\QuoteRegionPrice::create([
                        'product_id' => $productId,
                        'region' => $region,
                        'quoted_on' => $date,
                        'price' => $price,
                    ]);
                    $imported++;
                }
            }
            $avg = isset($wide['stat']['avg']) ? self::num((string) ($line[$wide['stat']['avg']] ?? '')) : null;
            if ($avg === null && $regionPrices) {
                $avg = round(array_sum($regionPrices) / count($regionPrices), 2);
            }
            if ($avg !== null) {
                $min = isset($wide['stat']['min']) ? self::num((string) ($line[$wide['stat']['min']] ?? '')) : null;
                $max = isset($wide['stat']['max']) ? self::num((string) ($line[$wide['stat']['max']] ?? '')) : null;
                $daily = QuoteProductPrice::where('product_id', $productId)->where('quoted_on', $date)->find();
                $payload = [
                    'product_id' => $productId,
                    'quoted_on' => $date,
                    'price_avg' => $avg,
                    'price_min' => $min,
                    'price_max' => $max,
                ];
                if ($daily) {
                    $daily->save($payload);
                    $updated++;
                } else {
                    QuoteProductPrice::create($payload);
                    $imported++;
                }
                $touchedDates[$date] = true;
            } elseif ($regionPrices) {
                $touchedDates[$date] = true;
            }
        }

        $latest = QuoteProductPrice::where('product_id', $productId)->order('quoted_on', 'desc')->find();
        $product = QuoteProduct::find($productId);
        if ($latest && $product) {
            $product->save([
                'price_avg' => $latest->price_avg,
                'price_min' => $latest->price_min,
                'price_max' => $latest->price_max,
                'quoted_on' => substr((string) $latest->quoted_on, 0, 10),
            ]);
        }

        // 只清「本次导入年份」里不在表头的地区；无有效年份则绝不清理，避免误删其它年份
        if ($regionNames && $defaultYear >= 2000 && $defaultYear <= 2100) {
            \app\model\QuoteRegionPrice::where('product_id', $productId)
                ->whereNotIn('region', $regionNames)
                ->whereBetween('quoted_on', [
                    sprintf('%04d-01-01', $defaultYear),
                    sprintf('%04d-12-31', $defaultYear),
                ])
                ->delete();
        }

        $dateList = array_keys($touchedDates);
        sort($dateList);
        $yearMin = $dateList ? (int) substr($dateList[0], 0, 4) : ($defaultYear ?: null);
        $yearMax = $dateList ? (int) substr($dateList[count($dateList) - 1], 0, 4) : ($defaultYear ?: null);
        $forcedYear = ($defaultYear >= 2000 && $defaultYear <= 2100) ? $defaultYear : null;

        return [
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
            'regions' => array_values($regionNames),
            'days' => count($dateList),
            'date_from' => $dateList[0] ?? null,
            'date_to' => $dateList ? $dateList[count($dateList) - 1] : null,
            'year' => $forcedYear ?: $yearMax,
            'year_min' => $yearMin,
            'year_max' => $yearMax,
            'year_forced' => $forcedYear,
        ];
    }

    /** @param list<list<string>> $grid */
    private static function wideLayout(array $grid): ?array
    {
        foreach ($grid as $i => $line) {
            $dateCol = null;
            $regions = [];
            $stat = [];
            foreach ($line as $col => $cell) {
                $label = preg_replace('/\s+/u', '', trim((string) $cell)) ?? '';
                if ($label === '') {
                    continue;
                }
                if (in_array($label, ['日期', '报价日期', 'date'], true)) {
                    $dateCol = (int) $col;
                    continue;
                }
                if (in_array($label, ['最低报价', '最低价', '最低'], true)) {
                    $stat['min'] = (int) $col;
                    continue;
                }
                if (in_array($label, ['最高报价', '最高价', '最高'], true)) {
                    $stat['max'] = (int) $col;
                    continue;
                }
                if ($label === '均价') {
                    $stat['avg'] = (int) $col;
                    continue;
                }
                if (in_array($label, ['平均值', '平均'], true) && !isset($stat['avg'])) {
                    $stat['avg'] = (int) $col;
                    continue;
                }
                if ($dateCol !== null) {
                    $regions[$label] = (int) $col;
                }
            }
            if ($dateCol === null || !$regions) {
                continue;
            }
            $title = $i > 0 ? trim((string) ($grid[0][0] ?? '')) : '';

            return [
                'headerIndex' => $i,
                'dateCol' => $dateCol,
                'regions' => $regions,
                'stat' => $stat,
                'title' => $title,
            ];
        }

        return null;
    }

    private static function resolveWideProduct(int $productId, string $title): int
    {
        if ($productId > 0) {
            if (!QuoteProduct::find($productId)) {
                throw new \InvalidArgumentException('这个行情品种不存在');
            }

            return $productId;
        }
        $hits = [];
        foreach (QuoteProduct::field('id,name')->select() as $p) {
            $name = trim((string) $p->name);
            if ($name === '') {
                continue;
            }
            $alias = $name;
            if (preg_match('/[（(]([^）)]+)[）)]/u', $name, $m)) {
                $alias = $m[1];
            }
            if (($alias !== '' && mb_stripos($title, $alias) !== false) || mb_stripos($title, $name) !== false) {
                $hits[] = (int) $p->id;
            }
        }
        $hits = array_values(array_unique($hits));
        if (count($hits) === 1) {
            return $hits[0];
        }
        throw new \InvalidArgumentException('这张表对上了多个品种，或没有对上。请在对应品种那一行点「导入历史」');
    }

    /**
     * 后台选了导入年份时：无论单元格是序列号还是带年份的完整日期，都按所选年入库（保留月日）。
     * 这样「筛选/导入年份 = 2025」不会因为表里写着 2026 或 Excel 序列号而写进别的年份。
     */
    private static function forceYear(string $ymd, int $year): string
    {
        if ($ymd === '' || $year < 2000 || $year > 2100) {
            return $ymd;
        }
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m)) {
            return $ymd;
        }
        $month = (int) $m[2];
        $day = (int) $m[3];
        if (!checkdate($month, $day, $year)) {
            // 如 2024-02-29 → 2025：落到该月最后一天，避免整行丢弃
            $day = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
            if (!checkdate($month, $day, $year)) {
                return '';
            }
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private static function dateInSheet(string $raw, string $title, int $defaultYear = 0): string
    {
        $yearHint = ($defaultYear >= 2000 && $defaultYear <= 2100)
            ? $defaultYear
            : self::yearFromText($title);
        $norm = self::normalizeDate($raw, $yearHint);
        if ($norm !== '') {
            return ($defaultYear >= 2000 && $defaultYear <= 2100)
                ? self::forceYear($norm, $defaultYear)
                : $norm;
        }
        $raw = trim($raw);
        if (!preg_match('/^\d{1,2}$/', $raw)) {
            return '';
        }
        $day = (int) $raw;
        $month = 0;
        // 只写「日」的月表：表头里的「x月」+ 所选/标题年份
        $year = $yearHint;
        if (preg_match('/(20\d{2})\s*年\s*(\d{1,2})\s*月/u', $title, $m)) {
            // 后台已选导入年时，只用标题里的月份，年份以所选为准
            if ($year <= 0 || $defaultYear < 2000) {
                $year = (int) $m[1];
            }
            $month = (int) $m[2];
        } elseif (preg_match('/(\d{1,2})\s*月/u', $title, $m)) {
            $month = (int) $m[1];
            if ($year <= 0) {
                $year = (int) date('Y');
                if ($month > (int) date('n') + 1) {
                    $year--;
                }
            }
        }
        if ($month < 1 || $month > 12 || $year < 2000 || !checkdate($month, $day, $year)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    private static function yearFromText(string $text): int
    {
        if (preg_match('/(20\d{2})\s*年/u', $text, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(20\d{2})/', $text, $m)) {
            return (int) $m[1];
        }

        return 0;
    }

    private static function ensureRegionTable(): void
    {
        \think\facade\Db::execute("CREATE TABLE IF NOT EXISTS quote_region_prices (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `product_id` INT UNSIGNED NOT NULL,
            `region` VARCHAR(64) NOT NULL,
            `quoted_on` DATE NOT NULL,
            `price` DECIMAL(12,2) NOT NULL,
            `created_at` DATETIME NULL,
            `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_product_region_date` (`product_id`, `region`, `quoted_on`),
            KEY `idx_product` (`product_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    /** @param list<string> $header @return array<string,int> */
    private static function mapHeader(array $header): array
    {
        $alias = [
            '品种' => 'name', '品名' => 'name', '名称' => 'name', '产品名称' => 'name', 'name' => 'name',
            '日期' => 'date', '报价日期' => 'date', 'date' => 'date',
            '均价' => 'avg', '价格' => 'avg', 'price_avg' => 'avg',
            '最低价' => 'min', '最低' => 'min', 'price_min' => 'min',
            '最高价' => 'max', '最高' => 'max', 'price_max' => 'max',
        ];
        $cols = [];
        foreach ($header as $i => $label) {
            $key = preg_replace('/\s+/u', '', trim((string) $label)) ?? '';
            $key = strtolower($key);
            foreach ($alias as $name => $field) {
                if ($key === strtolower($name) && !isset($cols[$field])) {
                    $cols[$field] = (int) $i;
                }
            }
        }

        return $cols;
    }

    /**
     * 识别：Excel 序列号、2026-01-01、2026/1/1、1/1/26、2026年1月1日 等。
     * 两位年份优先用 hintYear 的世纪；无 hint 时 00–69→2000，70–99→1900。
     */
    private static function normalizeDate(string $raw, int $hintYear = 0): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }
        if (preg_match('/^\d+(\.\d+)?$/', $raw)) {
            $n = (float) $raw;
            if ($n > 20000 && $n < 80000) {
                $ts = (int) round(($n - 25569) * 86400);

                return gmdate('Y-m-d', $ts);
            }
        }
        if (preg_match('/(20\d{2})\s*年\s*(\d{1,2})\s*月\s*(\d{1,2})\s*日?/u', $raw, $m)) {
            return self::ymd((int) $m[1], (int) $m[2], (int) $m[3]);
        }
        $raw = str_replace(['.', '年', '月', '日'], ['/', '/', '/', ''], $raw);
        $raw = preg_replace('/\s+/', '', $raw) ?? $raw;
        // 2026-01-01 / 2026/1/1
        if (preg_match('/^(20\d{2})[-\/](\d{1,2})[-\/](\d{1,2})/', $raw, $m)) {
            return self::ymd((int) $m[1], (int) $m[2], (int) $m[3]);
        }
        // 1/1/26、01-01-2026、1/31/2026（Excel 常见月/日/年）
        if (preg_match('/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{2,4})$/', $raw, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $y = (int) $m[3];
            if ($y < 100) {
                if ($hintYear >= 2000) {
                    $y = (int) (floor($hintYear / 100) * 100 + $y);
                    // 若与 hint 差太大（如 hint=2026 且写了 99），回退常规规则
                    if (abs($y - $hintYear) > 20) {
                        $y = $y >= 70 ? 1900 + ((int) $m[3]) : 2000 + ((int) $m[3]);
                    }
                } else {
                    $y = $y >= 70 ? 1900 + $y : 2000 + $y;
                }
            }
            // a/b：若其中一个 >12，大的是日；否则按月/日（Excel 美式）
            if ($a > 12 && $b <= 12) {
                return self::ymd($y, $b, $a);
            }
            if ($b > 12 && $a <= 12) {
                return self::ymd($y, $a, $b);
            }

            return self::ymd($y, $a, $b);
        }
        // 1/6、01-06（仅月日：必须已选导入年 / hintYear）
        if ($hintYear >= 2000 && preg_match('/^(\d{1,2})[-\/](\d{1,2})$/', $raw, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            if ($a >= 1 && $a <= 12 && checkdate($a, $b, $hintYear)) {
                return self::ymd($hintYear, $a, $b);
            }
            if ($b >= 1 && $b <= 12 && $a > 12 && checkdate($b, $a, $hintYear)) {
                return self::ymd($hintYear, $b, $a);
            }
        }

        return '';
    }

    private static function ymd(int $y, int $m, int $d): string
    {
        if ($y < 1990 || $y > 2100 || !checkdate($m, $d, $y)) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $y, $m, $d);
    }

    private static function num(string $raw): ?float
    {
        $raw = trim(str_replace([',', '，', '元', ' '], '', $raw));
        if ($raw === '') {
            return null;
        }
        if (!is_numeric($raw)) {
            return null;
        }

        return round((float) $raw, 2);
    }

    /** @return list<list<string>> */
    private static function rowsFromCsv(string $path): array
    {
        $raw = (string) file_get_contents($path);
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }
        if ($raw !== '' && !mb_check_encoding($raw, 'UTF-8')) {
            $converted = @mb_convert_encoding($raw, 'UTF-8', 'GBK');
            if (is_string($converted) && $converted !== '') {
                $raw = $converted;
            }
        }
        $first = strtok($raw, "\r\n") ?: '';
        $delim = substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',';
        $fp = fopen('php://memory', 'r+');
        fwrite($fp, $raw);
        rewind($fp);
        $rows = [];
        while (($line = fgetcsv($fp, 0, $delim)) !== false) {
            $rows[] = array_map(static fn ($c) => trim((string) $c), $line);
        }
        fclose($fp);

        return $rows;
    }

    /** @return list<list<string>> */
    private static function rowsFromXlsx(string $path): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \InvalidArgumentException('无法读取这个 Excel 文件');
        }
        $shared = [];
        $ss = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($ss) && $ss !== '') {
            $xml = simplexml_load_string(self::stripNs($ss));
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $shared[] = (string) $si->t;
                        continue;
                    }
                    $text = '';
                    foreach ($si->r as $r) {
                        $text .= (string) $r->t;
                    }
                    $shared[] = $text;
                }
            }
        }
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if (!is_string($sheet) || $sheet === '') {
            throw new \InvalidArgumentException('Excel 里没有第一张工作表');
        }
        $xml = simplexml_load_string(self::stripNs($sheet));
        if (!$xml || !isset($xml->sheetData)) {
            throw new \InvalidArgumentException('Excel 内容无法识别');
        }
        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = preg_replace('/\d+/', '', $ref) ?: 'A';
                $idx = self::colIndex($col);
                $type = (string) $c['t'];
                $value = isset($c->v) ? (string) $c->v : '';
                if ($type === 's') {
                    $value = $shared[(int) $value] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string) ($c->is->t ?? '');
                }
                $cells[$idx] = trim($value);
            }
            if (!$cells) {
                continue;
            }
            ksort($cells);
            $max = max(array_keys($cells));
            $line = [];
            for ($i = 0; $i <= $max; $i++) {
                $line[] = $cells[$i] ?? '';
            }
            $rows[] = $line;
        }

        return $rows;
    }

    private static function stripNs(string $xml): string
    {
        return preg_replace('/xmlns(:\w+)?="[^"]*"/', '', $xml) ?? $xml;
    }

    private static function colIndex(string $col): int
    {
        $n = 0;
        $col = strtoupper($col);
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($col[$i]) - 64);
        }

        return max(0, $n - 1);
    }
}
