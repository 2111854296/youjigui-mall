#!/usr/bin/env python3
from pathlib import Path
# size of quotes page chunk - can we inject?
p=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/CPYqRJ-v.js")
t=p.read_text(encoding="utf-8",errors="ignore")
print("len", len(t))
print("has expand", "chartExpand" in t, "详情展开" in t)
print("has QuoteKline", "C-jR4i7n" in t)
# find chart tools / range area for injection point
for k in ["半年","详情","chart-tools","range-tabs","近半年"]:
  print(k, t.find(k))
