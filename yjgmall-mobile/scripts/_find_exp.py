#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import re
# find script include for quote-chart-expand
root=Path("/www/wwwroot/g.aifeili.cn")
hits=[]
for p in list(root.joinpath("public").glob("*.html"))+list(root.joinpath("public").glob("**/*.php"))[:50]:
  try:
    t=p.read_text(encoding="utf-8",errors="ignore")
  except Exception:
    continue
  if "quote-chart-expand" in t:
    hits.append(str(p))
    for m in re.finditer(r"quote-chart-expand[^\"']*", t):
      print(p.name, m.group())
# also nuxt app template
for p in Path("/www/wwwroot/g.aifeili.cn").rglob("*").__iter__():
  if not p.is_file(): continue
  if p.suffix not in (".html",".vue",".php",".js",".ts"): continue
  if "quote-chart-expand" in p.name: continue
  try:
    if p.stat().st_size>2_000_000: continue
    t=p.read_text(encoding="utf-8",errors="ignore")
  except Exception:
    continue
  if "quote-chart-expand" in t:
    print("FOUND", p, t[t.find("quote-chart-expand")-40:t.find("quote-chart-expand")+60])
