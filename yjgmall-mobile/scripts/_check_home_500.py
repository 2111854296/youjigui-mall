#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import urllib.request
import os

for u in ["https://g.aifeili.cn/", "https://g.aifeili.cn/?v=fix1"]:
    try:
        req = urllib.request.Request(u, headers={"Cache-Control": "no-cache"})
        with urllib.request.urlopen(req, timeout=20) as r:
            body = r.read().decode("utf-8", "ignore")
        print(u, r.status, "len", len(body), "banner" in body, "500" in body[:200])
        print(body[:300].replace("\n", " "))
    except Exception as e:
        print(u, "ERR", e)

html = open("/www/wwwroot/g.aifeili.cn/public/index.html", encoding="utf-8", errors="ignore").read()
print("html entry", "0a3-bBq-.js" in html, "banner-pe" in html)
print(html[:500].replace("\n", " | "))

# nuxt logs?
for p in [
    "/www/wwwroot/g.aifeili.cn/logs/error.log",
    "/www/wwwroot/g.aifeili.cn/runtime/log/error.log",
]:
    if os.path.isfile(p):
        print("log", p)
        print(open(p, encoding="utf-8", errors="ignore").read()[-800:])
