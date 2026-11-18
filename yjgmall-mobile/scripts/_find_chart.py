#!/usr/bin/env python3
from pathlib import Path
# list chart related + expand script
import os
base="/www/wwwroot/g.aifeili.cn/public"
for root,dirs,files in os.walk(base):
  for f in files:
    if "quote-chart" in f or "fit9" in f or f.startswith("C-jR4i7n"):
      print(os.path.join(root,f), os.path.getsize(os.path.join(root,f)))
# grep overview in _nuxt
nuxt=Path(base+"/_nuxt")
hits=0
for p in nuxt.glob("*.js"):
  t=p.read_text(encoding="utf-8",errors="ignore")
  if "近一年价格走势" in t or "QuoteKline" in t or "padRatio" in t or "o.length>=100" in t:
    print("HIT", p.name, "overview" in t, "showDots" in t, "o.length>=100" in t)
