#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8",errors="ignore")
# find renderList usage near chart
import re
for m in re.finditer(r"b\(n\(u\)\.points", s):
  print("found", m.start())
for m in re.finditer(r"b\([^)]{0,40}points", s[:200000]):
  print("pts", m.group()[:80], m.start())
# candle map?
i=s.find("n(u).candles")
print("candles", i, s[i:i+180] if i>=0 else None)
i=s.find(".candles")
print("candles2", s[i-50:i+200])
# overview prop
print("overview count", s.count("overview"))
i=s.find("overview:")
print(s[i:i+80] if i>=0 else "no")
# props
i=s.find("overview")
print("first overview", s[max(0,i-30):i+100])
