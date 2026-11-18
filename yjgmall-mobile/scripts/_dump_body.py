#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8",errors="ignore")
# dump from flatThresh to return
idx=s.find("flatThresh")
if idx<0: idx=s.find("rawSpan")
print(s[idx:idx+2200])
print("\n====TEMPLATE====\n")
idx=s.find('stroke:"#2468F2","stroke-width":"2.4"')
print(s[idx-350:idx+200])
print("\n====CONST====\n")
idx=s.find(",H=16,")
print(s[idx:idx+120])
# Fragment symbols At qt
idx=s.find("At=")
print("At", s[idx:idx+40])
# Fragment list near end
idx=s.find("const qt=")
print(s[idx:idx+200] if idx>=0 else "no qt")
idx=s.rfind("At=")
print("At last", s[idx:idx+80])
