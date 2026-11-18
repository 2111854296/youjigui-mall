#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import subprocess
import json
import urllib.request
import os
import re

host, db, user, pwd = "127.0.0.1", "yjgmall_g", "yjgmall_g", "TkzDCT7GkRf5TPer"

def mysql(sql):
    cmd = ["mysql", "-h", host, "-u", user, f"-p{pwd}", db, "-N", "-B", "-e", sql]
    out = subprocess.check_output(cmd, stderr=subprocess.STDOUT, timeout=20)
    return out.decode("utf-8", "ignore")

print("DB id18:")
print(mysql("SELECT id,link,HEX(link),content FROM ads WHERE id=18\\G"))
print("force empty again:")
print(mysql("UPDATE ads SET link=NULL WHERE id=18; SELECT id,IFNULL(link,'NULL') FROM ads WHERE id=18;"))

# read resolveBanners fully
for p in [
    "/www/wwwroot/g.aifeili.cn/app/controller/api/v1/Home.php",
    "/www/wwwroot/g.aifeili.cn/app/controller/api/Home.php",
]:
    s = open(p, encoding="utf-8", errors="ignore").read()
    i = s.find("function resolveBanners")
    print("====", p, "resolveBanners@", i)
    print(s[i : i + 900])

# check redis/cache
print("cache files?")
for root, dirs, files in os.walk("/www/wwwroot/g.aifeili.cn/runtime"):
    for f in files:
        if "home" in f.lower() or "ad" in f.lower():
            print(os.path.join(root, f))
    if root.count(os.sep) - "/www/wwwroot/g.aifeili.cn/runtime".count(os.sep) > 3:
        dirs[:] = []

# clear think cache
os.system("rm -rf /www/wwwroot/g.aifeili.cn/runtime/cache/* 2>/dev/null; echo cleared")

# hit local php directly
u = "http://127.0.0.1/api/v1/home"
# try via public
for u in [
    "https://g.aifeili.cn/api/v1/home",
    "http://127.0.0.1/api/v1/ads?position=home_banner",
]:
    try:
        req = urllib.request.Request(u, headers={"Cache-Control": "no-cache", "Pragma": "no-cache"})
        with urllib.request.urlopen(req, timeout=15) as r:
            raw = r.read().decode("utf-8", "ignore")
        data = json.loads(raw)
        print("URL", u, "cache-headers?", )
        if "ads" in (data.get("data") or {}):
            for b in (data["data"].get("ads") or {}).get("home_banner") or []:
                if b.get("id") == 18:
                    print("home banner 18", b.get("link"))
        items = data.get("data") or []
        if isinstance(items, list):
            for b in items:
                if b.get("id") == 18:
                    print("ads api 18", b.get("link"))
    except Exception as e:
        print("ERR", u, e)
