#!/usr/bin/env python3
from pathlib import Path
import re
t=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/0a3-bBq-.js").read_text(encoding="utf-8",errors="ignore")
ex=t[t.rfind("export{"):]
# render function Aw as aU?
print("Aw", re.search(r"Aw as ([A-Za-z0-9$]+)", ex))
# find createVNode-like - "isVNode" nearby
for pat in ["createVNode","createBlock","guardReactiveProps","openBlock"]:
  print(pat, t.find(pat))
# vue 3.5 might use different - Chart imports c as a - check what c is
# import{e as Mt,u as Bt,D as Ft,f as Lt,c as a,a as i,...
# reverse: export has `c as` - the local name before export
# Actually imports FROM 0a3: `c as a` means export name `c` 
# Find in export: something as c,
m=re.findall(r"([A-Za-z0-9$]+) as c[,}]", ex)
print("exported as c:", m[:5])
m=re.findall(r"([A-Za-z0-9$]+) as a[,}]", ex)
print("exported as a:", m[:5])
# render: Aw
# For mounting with context, use render(vnode, container)
# Search function that sets appContext
idx=t.find("appContext")
print("appContext sample", t[idx:idx+100] if idx>0 else None)
# Practical test approach - use nuxt payload
print("__NUXT__ keys docs")
# Find h export - often same as createVNode  
# In many builds: Cl as h or similar
for m in re.finditer(r"([A-Za-z0-9$]+) as h[,}]", ex):
  print("as h", m.group(1))
