#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import shutil, time
ts=str(int(time.time()))
old='''a("circle",{key:"d"+r,cx:t.x,cy:t.y,r:"4.5",fill:"#2468F2",stroke:"#fff","stroke-width":"2"},null,8,["cx","cy"])'''
new='''a("circle",{key:"d"+r,cx:t.x,cy:t.y,r:"4.5",fill:"#2468F2",stroke:"#fff","stroke-width":"2","data-label":t.label,"data-value":t.value},null,8,["cx","cy","data-label","data-value"])'''
for name in ["C-jR4i7n.js","C-jR4i7n.fit9.js","C-jR4i7n.fit8.js"]:
  p=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")/name
  s=p.read_text(encoding="utf-8")
  if old not in s:
    print("MISS", name, "has data-label?" , "data-label" in s)
  else:
    shutil.copy2(p, str(p)+".bak_dl_"+ts)
    p.write_text(s.replace(old,new,1), encoding="utf-8")
    print("OK", name)
