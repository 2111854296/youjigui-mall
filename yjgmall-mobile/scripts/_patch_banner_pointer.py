#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Fix stacked banner slides capturing clicks (last slide wins)."""
import os
import re

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
OLD1 = ".banner-carousel .slide{position:absolute;inset:0;opacity:0;transition:opacity .7s cubic-bezier(.16,1,.3,1);display:flex;align-items:center;padding:0 var(--s-8);color:#fff}"
NEW1 = ".banner-carousel .slide{position:absolute;inset:0;opacity:0;pointer-events:none;z-index:0;transition:opacity .7s cubic-bezier(.16,1,.3,1);display:flex;align-items:center;padding:0 var(--s-8);color:#fff}"
OLD2 = ".banner-carousel .slide.on{opacity:1}"
NEW2 = ".banner-carousel .slide.on{opacity:1;pointer-events:auto;z-index:1}"

# Also tolerant regex variants (minified spacing)
PAT1 = re.compile(
    r"\.banner-carousel\s*\.slide\{position:absolute;inset:0;opacity:0;(?:pointer-events:none;z-index:0;)?transition:opacity[^}]+\}"
)
PAT2 = re.compile(
    r"\.banner-carousel\s*\.slide\.on\{opacity:1;(?:pointer-events:auto;z-index:1;)?\}"
)

nfiles = 0
for f in os.listdir(ROOT):
    if not f.endswith(".css"):
        continue
    p = os.path.join(ROOT, f)
    s = open(p, encoding="utf-8", errors="ignore").read()
    if ".banner-carousel .slide{" not in s and ".banner-carousel .slide{" not in s.replace(" ", ""):
        if "banner-carousel" not in s or ".slide{" not in s:
            continue
    orig = s
    if OLD1 in s:
        s = s.replace(OLD1, NEW1)
    else:
        s2, c = PAT1.subn(NEW1, s, count=1)
        if c:
            s = s2
    if OLD2 in s:
        s = s.replace(OLD2, NEW2)
    else:
        s2, c = PAT2.subn(NEW2, s, count=1)
        if c:
            s = s2
    # channel banner similar
    if ".channel-banner .slide{" in s and "pointer-events:none" not in s[s.find(".channel-banner .slide{") : s.find(".channel-banner .slide{") + 200]:
        s = s.replace(
            ".channel-banner .slide{",
            ".channel-banner .slide{pointer-events:none;",
            1,
        )
        # crude — better explicit
    if s != orig:
        bak = p + ".bak_pointer"
        if not os.path.isfile(bak):
            open(bak, "w", encoding="utf-8").write(orig)
        open(p, "w", encoding="utf-8").write(s)
        print("patched", f, "has_fix", "pointer-events:none" in s and "pointer-events:auto" in s)
        nfiles += 1

# Also patch source CSS on server if present
src = "/www/wwwroot/g.aifeili.cn/assets/css/portal-design.css"
# find portal-design anywhere
for root, dirs, files in os.walk("/www/wwwroot/g.aifeili.cn"):
    dirs[:] = [d for d in dirs if d not in ("node_modules", "vendor", ".git", "_nuxt")]
    if "portal-design.css" in files:
        p = os.path.join(root, "portal-design.css")
        s = open(p, encoding="utf-8", errors="ignore").read()
        if "pointer-events:none" in s and ".banner-carousel .slide.on{opacity:1;pointer-events:auto" in s.replace(" ", ""):
            print("src already", p)
            continue
        if OLD1 in s or ".banner-carousel .slide{position:absolute" in s:
            orig = s
            if OLD1 in s:
                s = s.replace(OLD1, NEW1)
            if OLD2 in s:
                s = s.replace(OLD2, NEW2)
            if s != orig:
                open(p, "w", encoding="utf-8").write(s)
                print("src patched", p)

print("nfiles", nfiles)
