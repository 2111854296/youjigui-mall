#!/usr/bin/env python3
from pathlib import Path
import subprocess
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
print("PRODUCTS")
print(sql("SELECT id,name,quoted_on,price_avg FROM quote_products ORDER BY id LIMIT 30"))
print("--- YEAR COUNTS ---")
print(sql("SELECT p.id,p.name,LEFT(q.quoted_on,4) y,COUNT(*) c FROM quote_product_prices q JOIN quote_products p ON p.id=q.product_id GROUP BY p.id,y ORDER BY p.id,y"))
print("--- REGION YEAR ---")
print(sql("SELECT p.id,p.name,LEFT(q.quoted_on,4) y,COUNT(*) c FROM quote_region_prices q JOIN quote_products p ON p.id=q.product_id GROUP BY p.id,y ORDER BY p.id,y"))
print("--- recent 2025 sample ---")
print(sql("SELECT product_id,quoted_on,price_avg FROM quote_product_prices WHERE quoted_on LIKE '2025%' ORDER BY quoted_on DESC LIMIT 10"))
print("--- recent 2026 sample ---")
print(sql("SELECT product_id,quoted_on,price_avg FROM quote_product_prices WHERE quoted_on LIKE '2026%' ORDER BY quoted_on DESC LIMIT 5"))
