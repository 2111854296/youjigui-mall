#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8")
# template structure for scroll
for key in ["chart-scroll","line-stage","chart-viewport","fit"]:
  i=s.find(key)
  print(key, repr(s[max(0,i-40):i+80]) if i>=0 else None)
