#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8")
print("__YJG" in s, s[s.find("__YJG"):s.find("__YJG")+180] if "__YJG" in s else "missing")
# which file is actually imported by quotes page
for name in ["CPYqRJ-v.js","CPYqRJ-v.d7.js"]:
  t=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")/name
  if t.exists():
    x=t.read_text(encoding="utf-8",errors="ignore")
    i=x.find("C-jR4i7n")
    print(name, x[i-20:i+40] if i>=0 else "no")
