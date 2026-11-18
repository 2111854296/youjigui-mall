#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re

root = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
f = "D6WjvBeG.pg.js"
s = open(os.path.join(root, f), encoding="utf-8", errors="ignore").read()

# doSearch
i = s.find("function Mt(){v.kw=")
print("Mt@", i, s[i : i + 120])

# find tal search: similar pattern with different vars
for m in re.finditer(r"function \w+\(\)\{[^}]{0,80}\.kw=[^}]{0,80}\}", s):
    print("FN", m.group(0))

# find existing scrollIntoView usages to copy style
for m in re.finditer(r".{0,50}scrollIntoView\([^)]*\)", s):
    print("SCR", m.group(0)[:200])

# nextTick alias near pickCat
for m in re.finditer(r"nextTick|Ot\(|Pt\(|\$nextTick", s):
    pass
# look around first scrollIntoView for nextTick wrapper
idx = s.find("scrollIntoView")
print("ctx1", s[max(0, idx - 80) : idx + 80])
idx2 = s.find("scrollIntoView", idx + 1)
print("ctx2", s[max(0, idx2 - 80) : idx2 + 80])
idx3 = s.find("scrollIntoView", idx2 + 1)
print("ctx3", s[max(0, idx3 - 100) : idx3 + 100])
