#!/usr/bin/env python3
from pathlib import Path
import re
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for p in nuxt.glob("*.js"):
    t=p.read_text(encoding="utf-8",errors="ignore")
    if "function menuMatch" in t or ("menuMatch" in t and "startsWith(to" in t):
        print("MENU", p.name, p.stat().st_size)
        i=t.find("startsWith(to")
        print(t[max(0,i-200):i+200])
    if "users_pending" in t:
        print("DASH", p.name)
        i=t.find("users_pending")
        print(t[max(0,i-300):i+500])
# Ul exact
s=(nuxt/"DLdwU6hU.js").read_text(encoding="utf-8",errors="ignore")
# find toolbarFilters-like: hide position
for m in re.finditer(r'.{0,60}position.{0,60}', s):
    g=m.group(0)
    if "Ie" in g or "mt" in g or "C.value" in g or "filter" in g.lower() or "key===" in g:
        if len(g)<130:
            print("POS", g)
# find V.value group tag
i=s.find("VIP 企业")
print("VIPTAG", s[max(0,i-200):i+350])
