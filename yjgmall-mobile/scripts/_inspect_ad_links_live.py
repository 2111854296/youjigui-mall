#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import json
import urllib.request

# 1) dump home ads home_banner links
u = "https://g.aifeili.cn/api/v1/home"
with urllib.request.urlopen(u, timeout=20) as r:
    data = json.loads(r.read().decode("utf-8", "ignore"))
payload = data.get("data") or data
print("==== home.ads.home_banner ====")
for b in (payload.get("ads") or {}).get("home_banner") or []:
    print(b.get("id"), "=>", b.get("link"))
print("==== home.banners ====")
for b in payload.get("banners") or []:
    print(b.get("id"), "=>", b.get("link"))

# 2) find Home.php on server
root = "/www/wwwroot/g.aifeili.cn"
hits = []
for dirpath, dirnames, filenames in os.walk(root):
    dirnames[:] = [d for d in dirnames if d not in ("node_modules", "vendor", ".git", "_nuxt")]
    for f in filenames:
        if f == "Home.php":
            hits.append(os.path.join(dirpath, f))
print("Home.php files", hits)
for p in hits:
    s = open(p, encoding="utf-8", errors="ignore").read()
    if "home_banner" in s or "function index" in s or "ads" in s:
        print("----", p, "len", len(s))
        # extract ads assignment
        for m in re.finditer(r".{0,80}home_banner.{0,200}", s):
            print(m.group(0)[:260])
        i = s.find("'ads'")
        if i < 0:
            i = s.find('"ads"')
        if i >= 0:
            print("ads ctx", s[i : i + 400])

# 3) mysql via php
php = r"""
<?php
$roots=['/www/wwwroot/g.aifeili.cn'];
$env=null;
foreach($roots as $r){
  foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($r)) as $f){
    if($f->getFilename()==='.env' && strpos($f->getPathname(),'vendor')===false && strpos($f->getPathname(),'node_modules')===false){
      $c=file_get_contents($f->getPathname());
      if(strpos($c,'DB_DATABASE')!==false || strpos($c,'DATABASE')!==false){ $env=$f->getPathname(); break 2; }
    }
  }
}
echo "env=$env\n";
if(!$env) exit;
$cfg=[];
foreach(file($env) as $line){
  $line=trim($line); if($line===''||$line[0]==='#')continue; if(strpos($line,'=')===false)continue;
  [$k,$v]=explode('=',$line,2); $cfg[$k]=trim($v," \t\"'");
}
$host=$cfg['DB_HOST']??'127.0.0.1'; $db=$cfg['DB_DATABASE']??($cfg['DB_NAME']??'');
$user=$cfg['DB_USERNAME']??'root'; $pass=$cfg['DB_PASSWORD']??'';
echo "db=$host/$db user=$user\n";
$mysqli=@new mysqli($host,$user,$pass,$db);
if($mysqli->connect_errno){ echo "connect fail ".$mysqli->connect_error."\n"; exit; }
$mysqli->set_charset('utf8mb4');
foreach(['ads','banners'] as $t){
  $res=$mysqli->query("SHOW TABLES LIKE '$t'");
  if(!$res || !$res->num_rows){ echo "no table $t\n"; continue; }
  echo "==== $t ====\n";
  $q=$mysqli->query("SELECT id,title,link,position,is_active FROM `$t` ORDER BY id");
  while($row=$q->fetch_assoc()){
    $link=$row['link'];
    if(strpos((string)$link,'1688')!==false || strpos(strtolower((string)$link),'alibaba')!==false || $link==='/' || $link==='' || $link===null){
      echo json_encode($row, JSON_UNESCAPED_UNICODE),"\n";
    }
  }
  echo "-- all with 1688 anywhere --\n";
  $q=$mysqli->query("SELECT id,title,link,position FROM `$t` WHERE link LIKE '%1688%' OR link LIKE '%alibaba%'");
  while($row=$q->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE),"\n";
}
"""
open("/tmp/_ads_db.php", "w", encoding="utf-8").write(php)
os.system("php /tmp/_ads_db.php")
