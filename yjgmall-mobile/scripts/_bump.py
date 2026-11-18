#!/usr/bin/env python3
from pathlib import Path
p=Path("/www/wwwroot/g.aifeili.cn/public/index.html")
t=p.read_text(encoding="utf-8")
t2=t.replace("quote-chart-expand.js?v=2","quote-chart-expand.js?v=3")
p.write_text(t2,encoding="utf-8")
print("bumped", "v=3" in t2)
# touch fit9 mtime so CDN?
import os,time
for f in ["C-jR4i7n.fit9.js","C-jR4i7n.js","QuoteKlineChart.KSdW9eP7.css"]:
  fp="/www/wwwroot/g.aifeili.cn/public/_nuxt/"+f
  os.utime(fp,None)
  print(f, os.path.getsize(fp), time.ctime(os.path.getmtime(fp)))
