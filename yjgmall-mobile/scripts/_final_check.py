#!/usr/bin/env python3
from pathlib import Path
import subprocess
p=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js")
print("disk", p.stat().st_size)
s=p.read_text(encoding="utf-8",errors="ignore")
print("markers", {
 "pendingFinal": s.count("pendingFinal"),
 "ae_pe": s.count("ae(),pe()"),
 "fullPath_watch": ("{watch:[()=>d.fullPath" in s),
 "select_open": ("e(se)?.status&&!e(pt)" in s),
 "select_locked": ("e(se)?.status&&!e(te)" in s),
 "items": ("return want?rows.filter" in s),
 "ingest": s.count("127.0.0.1:7691"),
 "Ol": ("function Ol(){/*pendingFinal*/" in s),
 "ae": ("function ae(){/*pendingFinalSync*/" in s),
 "fetcher_seq": s.count("__artListSeq"),
})
# simulate backend filter count
env=Path("/www/wwwroot/g.aifeili.cn/.env").read_text(encoding="utf-8",errors="ignore")
cfg={}
for line in env.splitlines():
    line=line.strip()
    if not line or line.startswith("#") or "=" not in line: continue
    k,v=line.split("=",1); cfg[k.strip()]=v.strip().strip('"').strip("'")
sql="SELECT COUNT(*) FROM articles WHERE status='pending'; SELECT COUNT(*) FROM articles WHERE status<>'pending' LIMIT 1;"
# two queries
for q in ["SELECT COUNT(*) FROM articles WHERE status='pending'","SELECT COUNT(*) FROM articles WHERE status='published'","SELECT id,status,title FROM articles WHERE status='pending' ORDER BY id DESC LIMIT 3","SELECT id,status,title FROM articles WHERE status='published' ORDER BY id DESC LIMIT 3"]:
    cmd=["mysql","-h",cfg["DB_HOST"],"-u",cfg["DB_USER"],"-p"+cfg["DB_PASS"],cfg["DB_NAME"],"-N","-e",q]
    r=subprocess.run(cmd,stdout=subprocess.PIPE,stderr=subprocess.PIPE,universal_newlines=True)
    print("SQL", q[:50], "=>", (r.stdout or "").strip()[:200])
