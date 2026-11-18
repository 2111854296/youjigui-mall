#!/usr/bin/env python3
import subprocess
from pathlib import Path

php = r'''<?php
$env=file("/www/wwwroot/g.aifeili.cn/.env");$c=[];
foreach($env as $l){$l=trim($l);if($l===""||$l[0]==="#")continue;if(strpos($l,"=")===false)continue;[$k,$v]=explode("=",$l,2);$c[trim($k)]=trim($v," \t\"'");}
$m=new mysqli($c["DB_HOST"],$c["DB_USER"],$c["DB_PASS"],$c["DB_NAME"],(int)($c["DB_PORT"]??3306));
$r=$m->query("SELECT id,title,link,is_active,sort,LEFT(content,120) c FROM announcements ORDER BY sort DESC,id DESC LIMIT 15");
while($row=$r->fetch_assoc()){echo json_encode($row,JSON_UNESCAPED_UNICODE),"\n";}
'''
Path("/tmp/_list_ann.php").write_text(php, encoding="utf-8")
print(subprocess.check_output(["php", "/tmp/_list_ann.php"], stderr=subprocess.STDOUT).decode("utf-8", "replace"))
