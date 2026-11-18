#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import subprocess

# parse thinkphp database.php
cfg_paths = [
    "/www/wwwroot/g.aifeili.cn/config/database.php",
    "/www/wwwroot/g.aifeili.cn/app/config/database.php",
]
for p in cfg_paths:
    print("cfg", p, os.path.isfile(p))
    if os.path.isfile(p):
        print(open(p, encoding="utf-8", errors="ignore").read()[:1200])

# try reading env from .env.example or runtime
for p in [
    "/www/wwwroot/g.aifeili.cn/.env",
    "/www/wwwroot/g.aifeili.cn/.example.env",
]:
    if os.path.isfile(p):
        print("FILE", p)
        print(open(p, encoding="utf-8", errors="ignore").read()[:800])

# use php think or mysqli from database.php parse
php = r"""
<?php
$f='/www/wwwroot/g.aifeili.cn/config/database.php';
if(!is_file($f)) { echo "no db cfg\n"; exit; }
$cfg=include $f;
$c=$cfg['connections']['mysql'] ?? ($cfg['mysql'] ?? $cfg);
if(isset($c['hostname'])) { $host=$c['hostname']; $db=$c['database']; $user=$c['username']; $pass=$c['password']; }
else { var_export(array_keys($cfg)); exit; }
echo "db=$host/$db user=$user\n";
$m=new mysqli($host,$user,$pass,$db);
if($m->connect_errno){echo $m->connect_error; exit;}
$m->set_charset('utf8mb4');
$tables=[];
$r=$m->query('SHOW TABLES');
while($row=$r->fetch_row()) $tables[]=$row[0];
foreach($tables as $t){
  $cols=[];
  $r=$m->query("DESC `$t`");
  while($row=$r->fetch_assoc()) $cols[]=$row['Field'];
  $textCols=array_values(array_filter($cols, function($c){
    return preg_match('/link|url|website|href|content|extra|remark/i',$c);
  }));
  if(!$textCols) continue;
  $conds=implode(' OR ', array_map(function($c){return "`$c` LIKE '%1688%' OR `$c` LIKE '%alibaba%'";}, $textCols));
  $q=$m->query("SELECT * FROM `$t` WHERE $conds LIMIT 20");
  if(!$q) continue;
  $n=$q->num_rows;
  if($n){
    echo "==== $t ($n) cols=".implode(',',$textCols)." ====\n";
    while($row=$q->fetch_assoc()){
      $slim=[];
      foreach($row as $k=>$v){
        if($v!==null && $v!=='' && (stripos((string)$v,'1688')!==false || stripos((string)$v,'alibaba')!==false || in_array($k,['id','title','name','position','is_active'],true))){
          $slim[$k]=is_string($v)&&strlen($v)>180?substr($v,0,180).'...':$v;
        }
      }
      echo json_encode($slim, JSON_UNESCAPED_UNICODE),"\n";
    }
  }
}
// also show ads id 18 and recently updated ads
echo "==== ads recent ====\n";
$q=$m->query("SELECT id,title,link,position,updated_at FROM ads ORDER BY updated_at DESC LIMIT 15");
while($row=$q->fetch_assoc()) echo json_encode($row, JSON_UNESCAPED_UNICODE),"\n";
"""
open("/tmp/_find_1688.php", "w").write(php)
os.system("php /tmp/_find_1688.php 2>/dev/null")
