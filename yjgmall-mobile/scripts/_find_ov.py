#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import os
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for p in sorted(nuxt.glob("*.js")):
  if p.name.startswith("C-jR4i7n"): continue
  t=p.read_text(encoding="utf-8",errors="ignore")
  if "详情展开" in t or "canExpand" in t or "overview" in t and "QuoteKline" in t:
    print("PAGE", p.name, "详情" in t, "overview" in t, "expanded" in t, p.stat().st_size)
  if "详情展开" in t:
    # show overview usage
    i=t.find("overview")
    print("  ov", t[i-40:i+80] if i>=0 else "no overview")
    i=t.find("详情展开")
    print("  exp", t[i-60:i+40])
# also expand script
print("---expand---")
print(Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js").read_text(encoding="utf-8",errors="ignore")[:500])
print("...")
# how xLabels built in C-jR4i7n
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8",errors="ignore")
idx=s.find("_t=")
print("_t assign search")
import re
for m in re.finditer(r"_t=", s):
  frag=s[m.start():m.start()+200]
  if "label" in frag or "map" in frag:
    print(frag)
    print("---")
