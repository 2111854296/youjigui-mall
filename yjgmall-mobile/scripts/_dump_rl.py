#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8",errors="ignore")
# candle v-for
idx=s.find("P.value?")
# find render candles in template
idx=s.find('key:1},[')
print(s[idx-200:idx+350])
print("====")
# search createElementBlock with points
idx=s.find("n(u).points")
print("points in tpl", idx, s[idx-80:idx+200])
# Fragment props keys qt At
idx=s.find('qt=')
print("qt", s[idx:idx+30])
idx=s.find('["d"]')
print(s[idx-30:idx+80])
# Look for Lt usage - f as Lt might be renderList
print("Lt count", s.count("Lt("))
idx=s.find("Lt(")
print("Lt", s[idx:idx+100] if idx>=0 else None)
# Nt?
print("Nt(", s.count("Nt("))
idx=s.find("Nt(")
print(s[idx:idx+80] if idx>=0 else None)
# $t?
print("$t", s.count("$t("), s.find("$t("))
# Looking at candle: ),128)) - renderList
idx=s.find(",128)")
print("128 context", s[max(0,idx-180):idx+20])
