#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import shutil

NUXT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
src = os.path.join(NUXT, "rMEP6MmM.ss.js")
dst = os.path.join(NUXT, "rMEP6MmM.js")
if not os.path.isfile(src):
    raise SystemExit("ss missing")
shutil.copy2(src, dst)
s = open(dst, encoding="utf-8").read()
print("copied ss->js", "[a(le)[a(A)]].filter(Boolean)" in s)

# rewrite ALL refs
n = 0
for f in os.listdir(NUXT):
    if not f.endswith((".js", ".mjs", ".json", ".html")):
        continue
    p = os.path.join(NUXT, f)
    t = open(p, encoding="utf-8", errors="ignore").read()
    if "rMEP6MmM.js" in t or "rMEP6MmM.ss.js" in t:
        # keep both pointing to same content; prefer ss for cache bust in importers
        nt = t.replace("rMEP6MmM.js", "rMEP6MmM.ss.js")
        # but avoid double ss.ss
        nt = nt.replace("rMEP6MmM.ss.ss.js", "rMEP6MmM.ss.js")
        if nt != t:
            open(p, "w", encoding="utf-8").write(nt)
            print("ref", f)
            n += 1
print("ref_n", n)

# also check public root html
for hp in [
    "/www/wwwroot/g.aifeili.cn/public/index.html",
    "/www/wwwroot/g.aifeili.cn/index.html",
]:
    if os.path.isfile(hp):
        t = open(hp, encoding="utf-8", errors="ignore").read()
        if "rMEP6MmM" in t:
            print("html has chunk ref", hp)
