#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""启用广告全部按排序展示，不再静默截断到 8/16。"""
import os
import re

ROOT = "/www/wwwroot/g.aifeili.cn"

# 1) Ad::slides — raise default limit; home calls pass higher anyway
ad_php = os.path.join(ROOT, "app/model/Ad.php")
s = open(ad_php, encoding="utf-8").read()
bak = ad_php + ".bak_show_all"
if not os.path.isfile(bak):
    open(bak, "w", encoding="utf-8").write(s)
# change default limit 8 -> 100
s2 = s.replace(
    "public static function slides(string $position, int $limit = 8): array",
    "public static function slides(string $position, int $limit = 100): array",
)
# remove ->limit($limit) clamp only when we want all — keep limit but high
if "->limit($limit)" not in s2:
    print("Ad.php NO limit() call")
open(ad_php, "w", encoding="utf-8").write(s2)
print("Ad.php default", "limit = 100" in s2)

# 2) Home.php calls
for rel in ["app/controller/api/Home.php", "app/controller/api/v1/Home.php"]:
    p = os.path.join(ROOT, rel)
    if not os.path.isfile(p):
        print("MISS", p)
        continue
    t = open(p, encoding="utf-8").read()
    bak = p + ".bak_show_all"
    if not os.path.isfile(bak):
        open(bak, "w", encoding="utf-8").write(t)
    n = 0
    for old, new in [
        ("Ad::slides('home_banner', 16)", "Ad::slides('home_banner', 100)"),
        ("Ad::slides('home_banner', 8)", "Ad::slides('home_banner', 100)"),
        ("Ad::slides('news_top', 8)", "Ad::slides('news_top', 100)"),
        ("Ad::slides('home_portal', 6)", "Ad::slides('home_portal', 100)"),
    ]:
        c = t.count(old)
        if c:
            t = t.replace(old, new)
            n += c
            print(rel, old, "->", new, "x", c)
    open(p, "w", encoding="utf-8").write(t)
    print(rel, "done", n)

# 3) clear cache
os.system("rm -rf /www/wwwroot/g.aifeili.cn/runtime/cache/* 2>/dev/null; echo cache_cleared")
