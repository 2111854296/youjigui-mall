#!/usr/bin/env python3
from pathlib import Path
import re
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/CPYqRJ-v.js").read_text(encoding="utf-8",errors="ignore")
# find watch on P / allowance / D.value
for m in re.finditer(r'.{0,40}D\.value.{0,80}', s):
  g=m.group(0)
  if '1m' in g or '7d' in g or 'P' in g or 'At' in g:
    print("D", g)
print('---')
# Mt watch
i=s.find('P=c(()=>x.value?Mt')
print(s[i:i+200])
# search for 1m assignment
for m in re.finditer(r'.{0,60}1m.{0,60}', s):
  print("1m", m.group(0))
# pick range click
i=s.find('时间范围')
print('TABS', s[i:i+700])
