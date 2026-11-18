#!/usr/bin/env python3
"""Fix announcements save: empty sort -> 0; ensure DB default; drop invalid link if no column."""
from pathlib import Path
import subprocess
import re

root = Path("/www/wwwroot/g.aifeili.cn")
ctrl = root / "app/controller/api/v1/AdminResource.php"
fields = root / "config/admin_resource_fields.php"
resources = root / "config/admin_resources.php"

# 1) Patch normalizePayload to coerce sort null -> 0
src = ctrl.read_text(encoding="utf-8")
bak = ctrl.with_suffix(".php.bak_announce_sort")
if not bak.exists():
    bak.write_text(src, encoding="utf-8")

needle = "        return $data;\n    }\n}"
# Find normalizePayload's return - last return $data in file should be normalizePayload
marker = "if ($mode === 'create' && $resource === 'users' && empty($data['password']))"
if "sort null-safe" not in src:
    insert = """
        // sort null-safe: 排序留空时按 0，避免 NOT NULL 列写入 null 导致 500
        if (array_key_exists('sort', $data) && ($data['sort'] === null || $data['sort'] === '')) {
            $data['sort'] = 0;
        }
"""
    if marker in src:
        # insert before final return of normalizePayload
        idx = src.rfind("        return $data;")
        if idx > 0:
            src = src[:idx] + insert + src[idx:]
            ctrl.write_text(src, encoding="utf-8")
            print("patched normalizePayload sort null-safe")
        else:
            print("ERROR: return $data not found")
    else:
        print("ERROR: marker not found")
else:
    print("already patched controller")

# 2) Check DB columns and alter sort default; remove link from fields if no column
php = r'''<?php
$env=file("/www/wwwroot/g.aifeili.cn/.env");
$c=[];
foreach($env as $l){$l=trim($l);if($l===""||$l[0]==="#")continue;if(strpos($l,"=")===false)continue;[$k,$v]=explode("=",$l,2);$c[trim($k)]=trim($v," \t\"'");}
$mysqli=new mysqli($c["DB_HOST"],$c["DB_USER"],$c["DB_PASS"],$c["DB_NAME"],(int)($c["DB_PORT"]??3306));
if($mysqli->connect_error){fwrite(STDERR,$mysqli->connect_error."\n");exit(1);}
$cols=[];
$r=$mysqli->query("SHOW COLUMNS FROM announcements");
while($row=$r->fetch_assoc()){$cols[$row["Field"]]=$row; echo $row["Field"]."\t".$row["Type"]."\t".$row["Null"]."\t".$row["Default"]."\n";}
if(isset($cols["sort"])){
  $ok=$mysqli->query("ALTER TABLE announcements MODIFY COLUMN `sort` INT NOT NULL DEFAULT 0");
  echo $ok?"ALTER sort OK\n":("ALTER fail ".$mysqli->error."\n");
}
echo "HAS_LINK=".(isset($cols["link"])?"1":"0")."\n";
echo "HAS_WIDTH=".(isset($cols["width"])?"1":"0")."\n";
'''
Path("/tmp/_ann_fix.php").write_text(php, encoding="utf-8")
out = subprocess.check_output(["php", "/tmp/_ann_fix.php"], stderr=subprocess.STDOUT)
print(out.decode("utf-8", "replace"))
has_link = b"HAS_LINK=1" in out

# 3) If no link column, remove link from admin_resource_fields announcements
if not has_link and fields.exists():
    t = fields.read_text(encoding="utf-8")
    # remove link field block under announcements
    new = re.sub(
        r"('announcements'\s*=>\s*array\s*\([\s\S]*?)(\s*array\s*\(\s*'name'\s*=>\s*'link'[\s\S]*?\),)",
        r"\1",
        t,
        count=1,
    )
    if new != t:
        fields.write_text(new, encoding="utf-8")
        print("removed link field from admin_resource_fields")
    else:
        print("link field pattern not matched (maybe already removed)")

# clear opcache if any
subprocess.call(["php", "-r", "if(function_exists('opcache_reset')){opcache_reset();echo 'opcache reset\n';}else{echo 'no opcache\n';}"])
print("done")
