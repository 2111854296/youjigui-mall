#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import json
meta=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/builds/latest.json")
print(meta.read_text()[:500] if meta.exists() else "no latest")
# find which file imports chart
import os
for p in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt").glob("*.js"):
  if p.name.startswith("C-jR4i7n"): continue
  t=p.read_text(encoding="utf-8",errors="ignore")
  if "C-jR4i7n" in t and ("fit9" in t or './C-jR4i7n' in t or 'C-jR4i7n.js' in t):
    # show import line
    i=t.find("C-jR4i7n")
    print(p.name, t[max(0,i-30):i+40])
# nginx might rewrite fit9
os.system("grep -rn 'C-jR4i7n\\|fit9' /www/server/panel/vhost/nginx/*.conf 2>/dev/null | head -20")
os.system("grep -rn 'C-jR4i7n\\|fit9' /www/wwwroot/g.aifeili.cn/nginx*.conf 2>/dev/null | head -10")
# check if quotes page script tag in HTML payload - look at SSR
# verify patch: g>=100 pad 0.18
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8")
assert "?.18:" in s
assert 'r:"4.5"' in s
assert "board:!!n(u).showDots" in s
assert "showDots:!P.value&&g>=100" in s
print("ASSERTS OK")
# horizontal crosshair still z?
print("y2 leftovers", s.count("y2:z-O"), "y1:H,", s.count("y1:H,"))
