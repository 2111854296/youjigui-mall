#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
FILES = ["D6WjvBeG.js", "D6WjvBeG.pg.js"]

OLD = (
    'function Mt(){v.kw=st.value.trim(),K.value=1,Y()}'
    'function $t(){_.kw=nt.value.trim(),V.value=1,q()}'
)
NEW = (
    'function Mt(){v.kw=st.value.trim(),K.value=1,Y(),'
    'ot(()=>St.value?.scrollIntoView({behavior:"smooth",block:"start"}))}'
    'function $t(){_.kw=nt.value.trim(),V.value=1,q(),'
    'ot(()=>vt.value?.scrollIntoView({behavior:"smooth",block:"start"}))}'
)

for f in FILES:
    p = os.path.join(ROOT, f)
    if not os.path.isfile(p):
        print(f, "MISSING")
        continue
    s = open(p, encoding="utf-8").read()
    bak = p + ".bak_search_scroll"
    if not os.path.isfile(bak):
        open(bak, "w", encoding="utf-8").write(s)
    n = s.count(OLD)
    print(f, "matches", n, "already", NEW[:40] in s)
    if n:
        s = s.replace(OLD, NEW)
        open(p, "w", encoding="utf-8").write(s)
        print(f, "patched")
    elif "ot(()=>St.value?.scrollIntoView" in s and "function Mt()" in s:
        # maybe partially patched
        i = s.find("function Mt()")
        print(f, "Mt ctx", s[i : i + 180])
    else:
        i = s.find("function Mt()")
        print(f, "NO_MATCH", s[i : i + 160] if i >= 0 else "no Mt")

# cache bust pg → sc
src = os.path.join(ROOT, "D6WjvBeG.pg.js")
dst = os.path.join(ROOT, "D6WjvBeG.sc.js")
if os.path.isfile(src):
    shutil.copy2(src, dst)
    n = 0
    for f in os.listdir(ROOT):
        if not f.endswith(".js"):
            continue
        p = os.path.join(ROOT, f)
        s = open(p, encoding="utf-8", errors="ignore").read()
        if "D6WjvBeG.pg.js" in s:
            open(p, "w", encoding="utf-8").write(s.replace("D6WjvBeG.pg.js", "D6WjvBeG.sc.js"))
            print("bust", f)
            n += 1
    print("bust_n", n, "dst_ok", os.path.isfile(dst))
