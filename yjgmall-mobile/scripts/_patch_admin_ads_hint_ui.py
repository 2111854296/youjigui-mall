#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Inject ads hint into live admin resource Nuxt chunk if possible."""
import os
import re
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
HINT = "标记为「上架」的广告会全部按「排序」进入对应广告位轮播，不会因数量多而被截掉；不想起作用请改「下架」。"

# Find chunk that contains admin resource title row / 示意图 for ads
cands = []
for f in os.listdir(ROOT):
    if not f.endswith(".js"):
        continue
    p = os.path.join(ROOT, f)
    s = open(p, encoding="utf-8", errors="ignore").read()
    if "示意图" in s and ("showAdSchematic" in s or "ads" in s) and ("ptitle" in s or "pageTitle" in s or "新建" in s):
        cands.append((f, s))
print("cands", [c[0] for c in cands])

# Also find by admin-meta-
if not cands:
    for f in os.listdir(ROOT):
        if not f.endswith(".js"):
            continue
        p = os.path.join(ROOT, f)
        s = open(p, encoding="utf-8", errors="ignore").read()
        if "admin-meta-" in s and "示意图" in s:
            cands.append((f, s))
    print("cands2", [c[0] for c in cands])

for f, s in cands[:3]:
    if HINT in s:
        print(f, "already")
        continue
    # Try inject after title-row closing: look for 示意图 button markup patterns
    # Vue compiled often: e("strong",{class:"ptitle"}...
    patterns = [
        (r'(class:"ptitle"[^\]]*\])', None),
    ]
    # Simpler approach: after "示意图" button block, insert a paragraph vnode
    # Find unique snippet near 示意图
    i = s.find("示意图")
    print(f, "示意图@", i, "ctx", s[max(0, i - 120) : i + 160])

# Verify API
import json, urllib.request
u = "https://g.aifeili.cn/api/v1/admin/resources/ads/meta"
# may need auth — try home count instead
u2 = "https://g.aifeili.cn/api/v1/home"
with urllib.request.urlopen(u2, timeout=15) as r:
    data = json.loads(r.read().decode())
hb = ((data.get("data") or {}).get("ads") or {}).get("home_banner") or []
print("home_banner_count", len(hb), "ids", [b.get("id") for b in hb])
