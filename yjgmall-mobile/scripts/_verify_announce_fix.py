#!/usr/bin/env python3
from pathlib import Path
import subprocess

ctrl = Path("/www/wwwroot/g.aifeili.cn/app/controller/api/v1/AdminResource.php")
t = ctrl.read_text(encoding="utf-8")
print("has sort null-safe:", "sort null-safe" in t)

# Simulate normalize via quick php include of the logic
php = r'''<?php
function normalize($data){
  foreach($data as $k=>$v){ if($v==='') $data[$k]=null; }
  if(array_key_exists('sort',$data)&&($data['sort']===null||$data['sort']==='')) $data['sort']=0;
  return $data;
}
var_export(normalize(['title'=>'t','content'=>'c','sort'=>null,'is_active'=>1]));
echo "\n";
'''
Path("/tmp/_norm.php").write_text(php, encoding="utf-8")
print(subprocess.check_output(["php", "/tmp/_norm.php"], stderr=subprocess.STDOUT).decode())

# Try insert with empty sort using mysqli to confirm DB default
php2 = r'''<?php
$env=file("/www/wwwroot/g.aifeili.cn/.env");$c=[];
foreach($env as $l){$l=trim($l);if($l===""||$l[0]==="#")continue;if(strpos($l,"=")===false)continue;[$k,$v]=explode("=",$l,2);$c[trim($k)]=trim($v," \t\"'");}
$m=new mysqli($c["DB_HOST"],$c["DB_USER"],$c["DB_PASS"],$c["DB_NAME"],(int)($c["DB_PORT"]??3306));
$r=$m->query("SHOW COLUMNS FROM announcements LIKE 'sort'");
print_r($r->fetch_assoc());
'''
Path("/tmp/_col.php").write_text(php2, encoding="utf-8")
print(subprocess.check_output(["php", "/tmp/_col.php"], stderr=subprocess.STDOUT).decode())
