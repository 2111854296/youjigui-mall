#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/CPYqRJ-v.js").read_text(encoding="utf-8",errors="ignore")
for key in ["近 1 月","rangeTitle","rangeKey","selectedDays","价格走势","pickRange","RANGE_OPTS","quote-"]:
  print(key, s.count(key))
i=s.find("近 1 月")
print("around title const", s[max(0,i-200):i+400])
print("---")
i=s.find("价格走势")
print("around 价格走势", s[max(0,i-150):i+200])
