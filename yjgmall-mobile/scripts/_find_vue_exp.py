#!/usr/bin/env python3
from pathlib import Path
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for p in sorted(nuxt.glob("*.js")):
  t=p.read_text(encoding="utf-8",errors="ignore")
  if "chart-expand" in t or "详情展开" in t or "chartExpandOpen" in t:
    print("HIT", p.name, "expand-btn", "chart-expand-btn" in t, "Teleport" in t or "teleport" in t.lower())
# QuoteKlineChart export
s=(nuxt/"C-jR4i7n.fit9.js").read_text(encoding="utf-8")
print("export", s[-80:])
print("expanded prop", "expanded" in s[s.find("props:"):s.find("props:")+300])
# createApp availability
for p in list(nuxt.glob("entry*.js"))[:5]+list(nuxt.glob("B*.js"))[:3]:
  pass
print("vue createApp search")
for p in nuxt.glob("*.js"):
  if p.stat().st_size>500000: continue
  t=p.read_text(encoding="utf-8",errors="ignore")
  if "createApp" in t and "nuxt" in p.name.lower():
    print(p.name)
