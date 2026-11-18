#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
NUXT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
stamp = ";window.__BANNER_SINGLE_SLIDE=1;"
for name in ["rMEP6MmM.js", "rMEP6MmM.ss.js", "rMEP6MmM.ss2.js"]:
    p = os.path.join(NUXT, name)
    if not os.path.isfile(p):
        print("missing", name)
        continue
    s = open(p, encoding="utf-8").read()
    if not s.startswith(";window.__BANNER_SINGLE_SLIDE"):
        # ensure patched
        if "[a(le)[a(A)]].filter(Boolean)" not in s:
            print("NOT PATCHED", name)
            continue
        s = stamp + s
        open(p, "w", encoding="utf-8").write(s)
        print("stamped", name, len(s))
    else:
        print("already stamped", name)

# also bust 0a3 itself so entry reloads
p = os.path.join(NUXT, "0a3-bBq-.js")
s = open(p, encoding="utf-8", errors="ignore").read()
# create 0a3-bBq-.b1.js and rewrite index references
dst = "0a3-bBq-.b1.js"
open(os.path.join(NUXT, dst), "w", encoding="utf-8").write(s)
# find index.html script src
for hp in ["/www/wwwroot/g.aifeili.cn/public/index.html"]:
    if not os.path.isfile(hp):
        continue
    h = open(hp, encoding="utf-8", errors="ignore").read()
    if "0a3-bBq-" in h:
        h2 = h.replace("0a3-bBq-.js", dst)
        open(hp, "w", encoding="utf-8").write(h2)
        print("html entry bust")
    else:
        print("html no 0a3, snippet", h[h.find("_nuxt"): h.find("_nuxt") + 200] if "_nuxt" in h else "no nuxt")
