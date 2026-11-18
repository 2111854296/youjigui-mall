#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8",errors="ignore")
# imports at start
print("IMPORT", s[:400])
print("---")
# props
idx=s.find("props:{")
print("PROPS", s[idx:idx+200])
print("---")
# xLabels building
idx=s.find("xLabels")
print("xLabels first", s[idx:idx+80])
# find how xLabels assigned
import re
for m in re.finditer(r"xLabels", s):
  if m.start()>2000:
    print(s[m.start()-20:m.start()+120])
    break
# look for label loop
idx=s.find("MIN_GAP")
print("MINGAP", idx, s[idx-100:idx+200] if idx>=0 else "")
idx=s.find("targetStep")
print("TS", idx)
idx=s.find("idxSet")
print("idxSet", s[idx-50:idx+250] if idx>=0 else "nf")
# return object end of chart
idx=s.find("gridY:")
# find return after points mapped - search return{points:_
idx=s.find("return{points:_")
print("RET", s[idx:idx+220] if idx>=0 else "try2")
if idx<0:
  idx=s.find("return{points:")
  while idx>=0:
    frag=s[idx:idx+180]
    if "line:" in frag or "area:" in frag:
      print("RET@", idx, frag)
      if "yLeft" in frag or len(frag)>50:
        print("FULL", s[idx:idx+280])
        break
    idx=s.find("return{points:", idx+10)
# renderList alias - F as 
print("FRAG F", [s[i:i+30] for i in [s.find("F as "), s.find(" as b,"), s.find("b as ")]])
print("import line", [x for x in s[:500].split(";") if "from" in x][:3])
