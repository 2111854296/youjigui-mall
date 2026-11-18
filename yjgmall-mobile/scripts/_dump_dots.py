#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8",errors="ignore")
# find line path stroke
i=s.find('stroke:"#2468F2"')
print("stroke count", s.count('stroke:"#2468F2"'))
while i>=0:
  print("---", s[max(0,i-120):i+220])
  i=s.find('stroke:"#2468F2"', i+10)
  if s.find('stroke:"#2468F2"', i)>0 and i>50000: break
# return object of chart compute
i=s.find("return{points:")
print("RET", s[i:i+200])
# pad
i=s.find("o.length>=100")
print("PAD", s[i-80:i+120])
