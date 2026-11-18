#!/usr/bin/env python3
from pathlib import Path
p=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/eu_lJI6T.js")
s=p.read_text(encoding="utf-8")
old='he={key:2},fe=["cx","cy"],ve={key:1}'
new='he={key:2},fe=["cx","cy","r","stroke-width"],ve={key:1}'
if old not in s:
  print("MISS")
else:
  p.write_text(s.replace(old,new,1),encoding="utf-8")
  print("OK fe")
