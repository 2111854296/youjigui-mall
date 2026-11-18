#!/usr/bin/env python3
from pathlib import Path
old='height:z,class:"line-chart"'
new='height:n(u).h||z,class:"line-chart"'
for name in ["C-jR4i7n.js","C-jR4i7n.fit9.js","C-jR4i7n.fit8.js"]:
  p=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")/name
  s=p.read_text(encoding="utf-8")
  if old not in s:
    print("MISS", name, "have?", "height:n(u).h||z" in s)
  else:
    p.write_text(s.replace(old,new,1),encoding="utf-8")
    print("OK", name)
# local source: check svg height binding
print("served check", "height:n(u).h||z,class" in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text())
