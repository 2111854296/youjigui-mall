#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
old='{token:ut}=Bt(),dt=Ft(),m=R(()=>f.revealed==null?!!ut.value:!!f.revealed)'
new='ut={value:!0},dt={fullPath:"/"};try{var __a=Bt();ut=__a.token||ut;dt=Ft()||dt}catch(e){}m=R(()=>f.revealed==null?!!ut.value:!!f.revealed)'
for name in ["C-jR4i7n.js","C-jR4i7n.fit9.js","C-jR4i7n.fit8.js"]:
  p=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")/name
  s=p.read_text(encoding="utf-8")
  if old not in s:
    print("MISS auth", name)
  else:
    p.write_text(s.replace(old,new,1),encoding="utf-8")
    print("OK auth", name)
# verify candles expose + auth
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8")
print("candles win", "__YJG_QUOTE_CANDLES__" in s)
print("auth try", "try{var __a=Bt()" in s)
