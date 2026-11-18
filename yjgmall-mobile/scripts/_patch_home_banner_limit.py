#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Raise home_banner slide limit so later ads (sort>8) actually appear."""
import os

files = [
    "/www/wwwroot/g.aifeili.cn/app/controller/api/Home.php",
    "/www/wwwroot/g.aifeili.cn/app/controller/api/v1/Home.php",
]
for p in files:
    if not os.path.isfile(p):
        print("MISSING", p)
        continue
    s = open(p, encoding="utf-8").read()
    bak = p + ".bak_banner_limit"
    if not os.path.isfile(bak):
        open(bak, "w", encoding="utf-8").write(s)
    old = "Ad::slides('home_banner', 8)"
    new = "Ad::slides('home_banner', 16)"
    n = s.count(old)
    s2 = s.replace(old, new)
    # also any literal limit(8) tied to home_banner resolve
    open(p, "w", encoding="utf-8").write(s2)
    print(p, "replaced", n, "now", s2.count(new))
