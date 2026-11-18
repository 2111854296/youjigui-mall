#!/usr/bin/env python3
from pathlib import Path
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
# find createApp / h export in 0a3
for name in ["0a3-bBq-.js","oUIM5V-l.js"]:
  p=nuxt/name
  if not p.exists():
    print("missing", name); continue
  t=p.read_text(encoding="utf-8",errors="ignore")
  print(name, "len", len(t))
  for k in ["createApp","createVNode","h(","export{"]:
    print(" ", k, t.find(k))
  # last export line
  i=t.rfind("export{")
  print(" exports", t[i:i+400] if i>=0 else None)
# quotes page chunk - CPYqRJ or CfDte
for p in nuxt.glob("*.js"):
  t=p.read_text(encoding="utf-8",errors="ignore")
  if "/quotes/" in t and "QuoteKline" in t or ("近一年" in t and "range-tabs" in t):
    print("PAGE?", p.name)
  if "C-jR4i7n" in t and ("半年" in t or "rangeKey" in t or "7日" in t):
    print("QUOTEPAGE", p.name, p.stat().st_size)
