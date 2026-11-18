#!/usr/bin/env python3
from pathlib import Path
import subprocess
# how many points for a product in last 2y
env=Path("/www/wwwroot/g.aifeili.cn/.env").read_text(encoding="utf-8",errors="ignore")
cfg={}
for line in env.splitlines():
    line=line.strip()
    if not line or line.startswith("#") or "=" not in line: continue
    k,v=line.split("=",1); cfg[k.strip()]=v.strip().strip('"').strip("'")
def sql(q):
    cmd=["mysql","-h",cfg["DB_HOST"],"-u",cfg["DB_USER"],"-p"+cfg["DB_PASS"],cfg["DB_NAME"],"-N","-e",q]
    r=subprocess.run(cmd,stdout=subprocess.PIPE,stderr=subprocess.PIPE,universal_newlines=True)
    return (r.stdout or "").strip()
print(sql("SELECT id,name FROM quote_products WHERE name LIKE '%DMC%' OR name LIKE '%废硅胶%'"))
print("---counts---")
print(sql("SELECT product_id, COUNT(*), MIN(quoted_on), MAX(quoted_on), MIN(price_avg), MAX(price_avg) FROM quote_product_prices WHERE product_id IN (1,18) GROUP BY product_id"))
print("---2025---")
print(sql("SELECT product_id, COUNT(*) FROM quote_product_prices WHERE product_id IN (1,18) AND quoted_on>='2025-01-01' AND quoted_on<'2026-01-01' GROUP BY product_id"))
# live chart hint
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8",errors="ignore")
print("hint scroll", "左右滑动" in s, "滑动" in s)
print("monthFit", "g<=31" in s or "monthFit" in s)
i=s.find("g<=31")
print(s[max(0,i-120):i+180])
