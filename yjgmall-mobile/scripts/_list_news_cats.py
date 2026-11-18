#!/usr/bin/env python3
from pathlib import Path
import subprocess

php = r'''<?php
$e=file("/www/wwwroot/g.aifeili.cn/.env");$c=[];
foreach($e as $l){$l=trim($l);if($l===""||$l[0]==="#")continue;if(strpos($l,"=")===false)continue;[$k,$v]=explode("=",$l,2);$c[trim($k)]=trim($v," \t\"'");}
$m=new mysqli($c["DB_HOST"],$c["DB_USER"],$c["DB_PASS"],$c["DB_NAME"],(int)($c["DB_PORT"]??3306));
$r=$m->query("SELECT id,name,slug,parent_id FROM categories WHERE module='news' OR slug LIKE 'news%' ORDER BY parent_id,sort,id LIMIT 100");
while($row=$r->fetch_assoc()) echo json_encode($row,JSON_UNESCAPED_UNICODE),"\n";
echo "---- fields articles quote/category ----\n";
'''
Path("/tmp/_nc.php").write_text(php, encoding="utf-8")
print(subprocess.check_output(["php", "/tmp/_nc.php"], stderr=subprocess.STDOUT).decode("utf-8", "replace"))
