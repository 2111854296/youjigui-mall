#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
for f in sorted(os.listdir(ROOT)):
    if not f.endswith(".js"):
        continue
    p = os.path.join(ROOT, f)
    try:
        if os.path.getsize(p) > 2_500_000:
            continue
    except Exception:
        continue
    s = open(p, encoding="utf-8", errors="ignore").read()
    if "banner-carousel" in s and ("img-slide" in s or "ad-tag" in s):
        print("HIT", f, len(s))
        i = s.find("banner-carousel")
        print(s[max(0, i - 200) : i + 500])
        print("---")
        # find v-for like patterns near slides
        for m in re.finditer(r".{0,80}img-slide.{0,120}", s):
            print("SLIDE", m.group(0)[:200])
            break
