#!/usr/bin/env python3
from pathlib import Path
import re, json, subprocess, os

# 1) confirm chunk linked from admin HTML / entry
root = Path("/www/wwwroot/g.aifeili.cn/public")
hits = []
for p in list(root.rglob("*.html"))[:50] + list((root/"_nuxt").glob("*.js"))[:5]:
    try:
        t = p.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if "DLdwU6hU" in t:
        hits.append(str(p))
print("chunk refs:", hits[:20], "count", len(hits))

# 2) AdminResource pending filter in PHP
php = Path("/www/wwwroot/g.aifeili.cn/app/controller/api/v1/AdminResource.php")
pt = php.read_text(encoding="utf-8", errors="ignore")
for pat in ["status", "pending", "where", "articles"]:
    pass
# find status filter logic
idx = 0
n = 0
while n < 8:
    i = pt.find("status", idx)
    if i < 0: break
    if "request" in pt[max(0,i-80):i+80].lower() or "param" in pt[max(0,i-80):i+120].lower() or "get(" in pt[max(0,i-40):i+40]:
        print("---php---", pt[max(0,i-100):i+200].replace("\n"," "))
        n += 1
    idx = i + 6

# 3) DB counts if possible
for cmd in [
    "php /www/wwwroot/g.aifeili.cn/think 2>/dev/null | head -3",
]:
    pass

# try find .env db
envp = Path("/www/wwwroot/g.aifeili.cn/.env")
if envp.exists():
    env = envp.read_text(encoding="utf-8", errors="ignore")
    for line in env.splitlines():
        if "DATABASE" in line.upper() or "DB_" in line.upper() or "HOSTNAME" in line or "USERNAME" in line or "PASSWORD" in line or "HOST" == line.split("=")[0].strip().upper():
            if "PASS" in line.upper() or "PASSWORD" in line.upper():
                print("env", line.split("=")[0], "=***")
            else:
                print("env", line.strip()[:80])

# 4) remaining ingest
s = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js").read_text(encoding="utf-8", errors="ignore")
print("ingest left", s.count("127.0.0.1:7691"))
i = s.find("127.0.0.1:7691")
if i>=0:
    print("ingest ctx", s[max(0,i-60):i+80])

# 5) menu pending link
for p in Path("/www/wwwroot/g.aifeili.cn").rglob("*menu*"):
    if p.is_file() and p.suffix in (".php",".js",".json",".vue",".ts"):
        t = p.read_text(encoding="utf-8", errors="ignore")
        if "pending" in t and "article" in t.lower():
            print("menu file", p)
