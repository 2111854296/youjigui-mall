#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import shutil
import re

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
src = os.path.join(ROOT, "entry.DRch0_HE.css")
# verify fix present
s = open(src, encoding="utf-8", errors="ignore").read()
print("has_none", "pointer-events:none" in s and ".banner-carousel .slide{" in s)
print("snippet", re.search(r".banner-carousel \.slide\{[^}]+\}", s))
print("on", re.search(r".banner-carousel \.slide\.on\{[^}]+\}", s))

# bust: copy to new name and rewrite refs
dst_name = "entry.DRch0_HE.pe.css"
dst = os.path.join(ROOT, dst_name)
shutil.copy2(src, dst)
n = 0
for f in os.listdir(ROOT):
    if not f.endswith((".js", ".html", ".css")):
        continue
    p = os.path.join(ROOT, f)
    t = open(p, encoding="utf-8", errors="ignore").read()
    if "entry.DRch0_HE.css" in t and f != dst_name:
        open(p, "w", encoding="utf-8").write(t.replace("entry.DRch0_HE.css", dst_name))
        print("bust", f)
        n += 1
print("bust_n", n)
