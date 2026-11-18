#!/usr/bin/env python3
from pathlib import Path
idx=Path("/www/wwwroot/g.aifeili.cn/public/index.html").read_text(encoding="utf-8",errors="ignore")
print("entry", "x7PoPnNF" in idx)
print("qce script", "quote-chart-expand" in idx)
qce=Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js").read_text(encoding="utf-8",errors="ignore")
print("qce content", qce[:80])
# quotes page asset
import os
hits=[]
for f in os.listdir("/www/wwwroot/g.aifeili.cn/public/_nuxt"):
  if not f.endswith(".js"): continue
  p="/www/wwwroot/g.aifeili.cn/public/_nuxt/"+f
  t=open(p,encoding="utf-8",errors="ignore").read(50000)
  if "chart-expand-btn" in t:
    hits.append(f)
print("expand pages", hits)
print("QuoteKline", os.path.exists("/www/wwwroot/g.aifeili.cn/public/_nuxt/eu_lJI6T.js"))
