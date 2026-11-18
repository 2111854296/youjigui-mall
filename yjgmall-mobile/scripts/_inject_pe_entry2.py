#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
pe_name = "banner-pe.fix.css"
inject = (
    ';(()=>{var l=document.createElement("link");l.rel="stylesheet";'
    'l.href="/_nuxt/' + pe_name + '?v=3";document.head.appendChild(l);})();'
)
open(os.path.join(ROOT, pe_name), "w", encoding="utf-8").write(
    ".banner-carousel .slide{pointer-events:none!important;z-index:0}"
    ".banner-carousel .slide.on{pointer-events:auto!important;z-index:1}\n"
)

# list js entry-like
js = [f for f in os.listdir(ROOT) if f.endswith(".js")]
print("js_count", len(js))
entries = [f for f in js if f.startswith("entry")]
print("entries", entries)
# also 0a3 / main / app
mains = [f for f in js if f.startswith(("entry", "0a3", "index.", "app."))]
print("mains", mains[:20])

targets = entries[:]
# find file that loads css links
for f in js:
    p = os.path.join(ROOT, f)
    # only small/medium entry-ish
    try:
        sz = os.path.getsize(p)
    except Exception:
        continue
    if sz > 2_000_000:
        continue
    s = open(p, encoding="utf-8", errors="ignore").read(5000)
    if "entry." in f or (s.startswith("(function") and "document.createElement" in open(p, encoding="utf-8", errors="ignore").read(20000)):
        if f not in targets and ("css" in open(p, encoding="utf-8", errors="ignore").read(30000) or f.startswith("entry")):
            targets.append(f)

# Prefer files referencing entry css
for f in js:
    p = os.path.join(ROOT, f)
    if os.path.getsize(p) > 500_000:
        continue
    s = open(p, encoding="utf-8", errors="ignore").read()
    if "DRch0_HE" in s or "entry." in s and s.count("stylesheet") > 0:
        if f not in targets:
            targets.append(f)

print("targets", targets[:15])
for f in targets[:8]:
    p = os.path.join(ROOT, f)
    s = open(p, encoding="utf-8", errors="ignore").read()
    if "banner-pe.fix.css" in s:
        print("already", f)
        continue
    bak = p + ".bak_pe_inject"
    if not os.path.isfile(bak):
        open(bak, "w", encoding="utf-8").write(s)
    open(p, "w", encoding="utf-8").write(inject + s)
    print("injected", f)
