#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8",errors="ignore")
print("viewBox", s[s.find("viewBox"):s.find("viewBox")+120])
print("z-H-O", s.count("z-H-O"), s.count(",z)"))
# style height
idx=s.find("line-wrap")
print(s[idx:idx+200] if idx>=0 else "no")
# css at end scope
idx=s.find("y-col")
print("ycss", idx)
# style tags in component - scoped via Tt
print("height:200", s.count("200px"), "240px", s.count("240px"))
# find CSS file for data-v-15c55ada
import os
for p in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt").glob("*.css"):
  t=p.read_text(encoding="utf-8",errors="ignore")
  if "15c55ada" in t or "line-wrap" in t and "y-col" in t:
    print("CSS", p.name, "200px" in t, t[t.find("y-col"):t.find("y-col")+80] if "y-col" in t else "")
