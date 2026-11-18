#!/usr/bin/env python3
from pathlib import Path
js=Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js")
print("js", js.stat().st_size, "widen" in js.read_text(encoding="utf-8",errors="ignore"))
html=Path("/www/wwwroot/g.aifeili.cn/public/index.html").read_text(encoding="utf-8",errors="ignore")
print("v2", "quote-chart-expand.js?v=2" in html)
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8",errors="ignore")
print("overview", "g>=100" in s, "fit=n<=31||n>=100" in s, "o.length>=100" in s)
# pad remaining
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for f in nuxt.glob("C-jR4i7n*.js"):
  if ".bak_" in f.name: continue
  t=f.read_text(encoding="utf-8",errors="ignore")
  if "rawSpan*.12" in t and "o.length>=100" not in t:
    t2=t.replace("const Z=Math.max(rawSpan*.12,Math.abs(mid0)*.004,1)","const Z=Math.max(rawSpan*(o.length>=100?.06:.12),Math.abs(mid0)*.0025,1)",1)
    if t2!=t:
      f.write_text(t2,encoding="utf-8"); print("padfix", f.name)
  elif "o.length>=100" in t:
    print("padok", f.name)
