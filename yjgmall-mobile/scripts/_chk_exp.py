#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for p in sorted(nuxt.glob("*.js")):
  t=p.read_text(encoding="utf-8",errors="ignore")
  if "chart-expand" in t or "详情展开" in t or "expanded" in t and "QuoteKline" in t:
    if "chart-expand" in t or "详情展开" in t:
      print(p.name, "详情" , "详情展开" in t, "expand-btn", "chart-expand-btn" in t, "expanded" in t)
# fit9 props and fit logic
s=(nuxt/"C-jR4i7n.fit9.js").read_text(encoding="utf-8")
print("props", s[s.find("props:{"):s.find("props:{")+180])
print("fit", s[s.find("need=(("):s.find("need=((")+120])
print("pin fit", s[s.find("fit=n<="):s.find("fit=n<=")+80])
