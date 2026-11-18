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

# main app entry used on this deploy
targets = []
for f in os.listdir(ROOT):
    if not f.endswith(".js"):
        continue
    if f.startswith("0a3") or "entry" in f.lower():
        targets.append(f)
    p = os.path.join(ROOT, f)
    if os.path.getsize(p) < 800_000:
        s = open(p, encoding="utf-8", errors="ignore").read(8000)
        if "DRch0_HE" in s or "RB4plv3A" in s:
            targets.append(f)

targets = sorted(set(targets))
print("targets", targets)
for f in targets:
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
