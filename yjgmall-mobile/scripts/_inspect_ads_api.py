#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json
import urllib.request

u = "https://g.aifeili.cn/api/v1/ads"
with urllib.request.urlopen(u, timeout=15) as r:
    data = json.loads(r.read().decode("utf-8", "ignore"))
items = data.get("data") or []
print("total", len(items))
print("==== 1688 / alibaba ====")
for it in items:
    link = str(it.get("link") or "")
    if "1688" in link or "alibaba" in link.lower() or "aliyun" in link.lower():
        print(it.get("id"), it.get("position"), it.get("title"), link[:160])
print("==== empty or / ====")
for it in items:
    link = str(it.get("link") or "")
    if link in ("", "/", "None", "null", "#"):
        print(it.get("id"), it.get("position"), repr(it.get("title")), repr(it.get("link")))
print("==== all home_banner ====")
for it in items:
    if it.get("position") == "home_banner":
        print(it.get("id"), it.get("sort"), repr(it.get("title")), "=>", it.get("link"))
