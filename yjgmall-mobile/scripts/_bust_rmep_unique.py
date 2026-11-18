#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import shutil

NUXT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
src = os.path.join(NUXT, "rMEP6MmM.js")
s = open(src, encoding="utf-8").read()
assert "[a(le)[a(A)]].filter(Boolean)" in s
# unique bust name
name = "rMEP6MmM.ss2.js"
dst = os.path.join(NUXT, name)
open(dst, "w", encoding="utf-8").write(s)
print("wrote", name, len(s))

# replace in ALL js
n = 0
for f in os.listdir(NUXT):
    if not f.endswith(".js"):
        continue
    p = os.path.join(NUXT, f)
    t = open(p, encoding="utf-8", errors="ignore").read()
    if "rMEP6MmM" not in t:
        continue
    # show before
    refs = sorted(set(re.findall(r"rMEP6MmM[^\"'\\s]*", t)))
    print("before", f, refs)
    nt = t
    for old in ["rMEP6MmM.ss.js", "rMEP6MmM.js"]:
        nt = nt.replace(old, name)
    nt = nt.replace("rMEP6MmM.ss2.ss2.js", name)
    if nt != t:
        open(p, "w", encoding="utf-8").write(nt)
        n += 1
        refs2 = sorted(set(re.findall(r"rMEP6MmM[^\"'\\s]*", nt)))
        print("after", f, refs2)
print("updated", n)

# also verify the patched loop text once
i = s.find("[a(le)[a(A)]].filter(Boolean)")
print("loop ctx", s[i : i + 160])
