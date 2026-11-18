#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8",errors="ignore")
print("len", len(s))
print("SLOT", "SLOT" in s, "h=200" in s, "200" in s[:500])
# find height 200
i=s.find("200")
print(s[:800])
print("---")
# props defaults
for pat in ["ariaLabel","revealed","gate","scrollable","monthFit","56"]:
  print(pat, s.count(pat))
i=s.find("monthFit")
print("monthFit ctx", s[max(0,i-80):i+200])
