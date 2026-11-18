#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import subprocess
import os
import json
import urllib.request

host, db, user, pwd = "127.0.0.1", "yjgmall_g", "yjgmall_g", "TkzDCT7GkRf5TPer"

def mysql(sql):
    cmd = ["mysql", "-h", host, "-u", user, f"-p{pwd}", db, "-N", "-B", "-e", sql]
    out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, timeout=20)
    return out.decode("utf-8", "ignore")

print("ads with origin/redirect/1688 in link or content:")
print(mysql("""
SELECT id,position,link,LEFT(content,120)
FROM ads
WHERE IFNULL(link,'') LIKE '%1688%'
   OR IFNULL(link,'') LIKE '%redirect.php%'
   OR IFNULL(content,'') LIKE '%1688%'
   OR IFNULL(content,'') LIKE '%redirect.php%'
ORDER BY id;
"""))

# find redirect.php
for root, dirs, files in os.walk("/www/wwwroot"):
    dirs[:] = [d for d in dirs if d not in ("node_modules", "vendor", ".git")]
    for f in files:
        if f == "redirect.php":
            p = os.path.join(root, f)
            print("FOUND", p)
            print(open(p, encoding="utf-8", errors="ignore").read()[:1500])

# verify home again
u = "https://g.aifeili.cn/api/v1/home"
with urllib.request.urlopen(u, timeout=15) as r:
    data = json.loads(r.read().decode("utf-8", "ignore"))
hb = ((data.get("data") or {}).get("ads") or {}).get("home_banner") or []
print("home_banner count", len(hb))
for b in hb:
    print(b.get("id"), "=>", repr(b.get("link")))
print("has1688", "1688" in json.dumps(hb))
