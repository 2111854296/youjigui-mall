#!/usr/bin/env python3
from pathlib import Path
p=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/eu_lJI6T.js")
s=p.read_text(encoding="utf-8")
# find fe= for dots
i=s.find('fe=["cx","cy"]')
print("fe", i, s[i:i+40] if i>=0 else None)
# also search after circle
i=s.find("e(s).dotR")
print("dotR ctx", s[i:i+180] if i>=0 else None)
# patch props array - might be named differently
import re
for m in re.finditer(r'\["cx","cy"\]', s):
  print("arr", m.start(), s[m.start()-30:m.start()+40])
