#!/usr/bin/env python3
from pathlib import Path
import subprocess, re

env = Path("/www/wwwroot/g.aifeili.cn/.env").read_text(encoding="utf-8", errors="ignore")
cfg = {}
for line in env.splitlines():
    line=line.strip()
    if not line or line.startswith("#") or "=" not in line: continue
    k,v=line.split("=",1)
    cfg[k.strip()] = v.strip().strip('"').strip("'")
user=cfg.get("DB_USER","")
passwd=cfg.get("DB_PASS","") or cfg.get("DB_PASSWORD","")
db=cfg.get("DB_NAME","")
host=cfg.get("DB_HOST","127.0.0.1")
sql="SELECT status, COUNT(*) c FROM articles GROUP BY status;"
cmd=["mysql","-h",host,"-u",user,"-p%s"%passwd,db,"-N","-e",sql]
r=subprocess.run(cmd,stdout=subprocess.PIPE,stderr=subprocess.PIPE,universal_newlines=True)
print("DB_OUT", r.stdout)
print("DB_ERR", (r.stderr or "")[-300:])

nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for p in nuxt.glob("*.js"):
    if p.name.startswith("DLdwU6hU"): continue
    try:
        t=p.read_text(encoding="utf-8",errors="ignore")
    except Exception:
        continue
    if "DLdwU6hU" in t:
        print("ref", p.name)

menus=Path("/www/wwwroot/g.aifeili.cn/config/admin_menus.php").read_text(encoding="utf-8",errors="ignore")
for m in re.finditer(r".{0,100}pending.{0,100}", menus):
    print("MENU", m.group(0).replace("\n"," ")[:200])

chunk=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js")
s=chunk.read_text(encoding="utf-8",errors="ignore")
n=0
while "127.0.0.1:7691/ingest" in s:
    a=s.find("fetch(")
    found=False
    while a>=0:
        if "127.0.0.1:7691/ingest" in s[a:a+220]:
            end=").catch(()=>{});"
            b=s.find(end,a)
            if b>a and b-a<1500:
                s=s[:a]+s[b+len(end):]
                n+=1
                found=True
                break
        a=s.find("fetch(", a+5)
    if not found:
        break
if n:
    chunk.write_text(s,encoding="utf-8")
print("stripped", n, "left", s.count("127.0.0.1:7691"))

# final checks
print("PASS_BITS", {
  "pendingFinal": s.count("pendingFinal"),
  "ae_pe": s.count("ae(),pe()"),
  "fullPath_watch": ("{watch:[()=>d.fullPath" in s),
  "select_locked": ("e(se)?.status&&!e(te)" in s),
  "items": ("return want?rows.filter" in s),
})

index=Path("/www/wwwroot/g.aifeili.cn/public/index.html")
if index.exists():
    html=index.read_text(encoding="utf-8",errors="ignore")
    marker="<!-- pending-filter-fix-v3 -->"
    if marker not in html:
        if "</head>" in html:
            html=html.replace("</head>", marker+"</head>",1)
        else:
            html+=marker
        index.write_text(html,encoding="utf-8")
        print("index marked")
    else:
        print("index already marked")
