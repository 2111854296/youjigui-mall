#!/usr/bin/env python3
from pathlib import Path
import re
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
# which chart chunk does page import?
for name in ["CPYqRJ-v.js","CPYqRJ-v.hs.js","CPYqRJ-v.pg.js"]:
  p=nuxt/name
  if not p.exists(): continue
  t=p.read_text(encoding="utf-8",errors="ignore")
  m=re.search(r'from"\./(C-jR4i7n[^"]*)"', t)
  print(name, "->", m.group(1) if m else None)
# dump exact fit logic from fit9 and main
for name in ["C-jR4i7n.js","C-jR4i7n.fit9.js"]:
  s=(nuxt/name).read_text(encoding="utf-8",errors="ignore")
  i=s.find("g<=31")
  print("==", name)
  print(s[i-80:i+350])
  print("pad", "rawSpan*.12" in s, "flatThresh" in s or "rawSpan<" in s)
