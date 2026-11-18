#!/usr/bin/env python3
import subprocess
from pathlib import Path

env = Path("/www/wwwroot/g.aifeili.cn/.env").read_text(encoding="utf-8", errors="ignore")
d = {}
for line in env.splitlines():
    line = line.strip()
    if not line or line.startswith("#") or "=" not in line:
        continue
    k, v = line.split("=", 1)
    d[k.strip()] = v.strip().strip('"').strip("'")

host = d.get("DB_HOST", "127.0.0.1")
name = d.get("DB_DATABASE") or d.get("DB_NAME") or ""
user = d.get("DB_USERNAME") or "root"
pwd = d.get("DB_PASSWORD") or ""
print("db keys", {k: d[k] for k in d if k.startswith("DB_") or k.startswith("database")})
print("name", repr(name), "user", user)

# PHP may use DATABASE_ mixed
# Read database.php
dbphp = Path("/www/wwwroot/g.aifeili.cn/config/database.php")
print(dbphp.read_text(encoding="utf-8", errors="ignore")[:1500])

sql = "SHOW COLUMNS FROM announcements; SELECT id,title,sort,is_active FROM announcements ORDER BY id DESC LIMIT 8;"
# use php mysqli via think env
php = r"""
<?php
$env=file('/www/wwwroot/g.aifeili.cn/.env');
$c=[];
foreach($env as $l){$l=trim($l);if($l===''||$l[0]==='#')continue;if(strpos($l,'=')===false)continue;[$k,$v]=explode('=',$l,2);$c[trim($k)]=trim($v," \t\"'");}
$host=$c['DB_HOST']??'127.0.0.1';$name=$c['DB_NAME']??($c['DB_DATABASE']??'');
$user=$c['DB_USER']??($c['DB_USERNAME']??'root');$pwd=$c['DB_PASS']??($c['DB_PASSWORD']??'');
$prefix=$c['DB_PREFIX']??'';
echo "H=$host N=$name U=$user P=".strlen($pwd)." pref=$prefix\n";
$mysqli=@new mysqli($host,$user,$pwd,$name);
if($mysqli->connect_error){fwrite(STDERR,$mysqli->connect_error);exit(1);}
foreach(["{$prefix}announcements","announcements"] as $t){
  $r=$mysqli->query("SHOW COLUMNS FROM `$t`");
  if($r){echo "TABLE $t\n";while($row=$r->fetch_assoc()){echo implode("\t",$row)."\n";}
  $r2=$mysqli->query("SELECT id,title,sort,is_active FROM `$t` ORDER BY id DESC LIMIT 8");
  while($row=$r2->fetch_assoc()){echo json_encode($row,JSON_UNESCAPED_UNICODE)."\n";}
  break;}
}
"""
Path("/tmp/_ann_php.php").write_text(php, encoding="utf-8")
print(subprocess.check_output(["php", "/tmp/_ann_php.php"], stderr=subprocess.STDOUT, text=True, encoding="utf-8", errors="replace"))
