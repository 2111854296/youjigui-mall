#!/usr/bin/env python3
from pathlib import Path
import re
t=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/0a3-bBq-.js").read_text(encoding="utf-8",errors="ignore")
ex=t[t.rfind("export{"):]
# Fd as ?
m=re.search(r"Fd as ([A-Za-z0-9$]+)", ex)
print("Fd as", m.group(1) if m else "not in export")
# also h - often createVNode
# QuoteKline imports: e as Mt,u as Bt,D as Ft,f as Lt,c as a,a as i,F as v,s as T,j as n,k as L,t as $,q as lt,d as X,n as $t,r as rt,B as Nt,o as l,z as R,_ as Tt
# from"./0a3-bBq-.js"
# So we need createApp - find Fd in export
for name in ["Fd","Aw","wp","h"]:
  m=re.search(rf"(?:^|[,{{])({name}) as ([A-Za-z0-9$]+)", ex)
  print(name, m.groups() if m else None)
# reverse search: which export is Fd
m=re.search(r"([A-Za-z0-9$]+) as Fd[,}]", ex)
print("rev Fd", m.group(1) if m else None)
# print snippet around Fd in export
i=ex.find("Fd")
print("ex Fd ctx", ex[max(0,i-40):i+40] if i>=0 else "absent")
# Maybe Fd not exported - use Nuxt vue app from #app
# Alternative: use render + createVNode from same imports as chart
# Chart uses: c as a (createElementVNode?), o as l (openBlock), z as R (computed/ref?)
# Mounting: import { createApp } from 'vue' via dynamic
# Check if window.Vue exists
print("---")
# Find createVNode export - often used as h
# Look for "export{" and "createVNode"
print("createVNode in file", t.find("createVNode"))
idx=t.find("createVNode")
print(t[idx-50:idx+80] if idx>=0 else None)
# In vue 3 runtime-dom, createApp is exported. Search export list for wp
i=ex.find("wp")
print("wp", ex[max(0,i-20):i+20] if i>=0 else None)
# Fd definition and if exported under another name - search ",Fd," in export by mapping
# Actually Fd=((...e)=>{const t=Kc().createApp(...e)
# Is Fd in export? 
print("Fd in export block", "Fd" in ex)
# List all 2-letter exports that might be createApp - check Nuxt docs: import { createApp } from '#app' 
# Practical approach: use element.__vueParentComponent and clone vnode with expanded
# OR patch chart to listen window event

print("PLAN B: expose from chart")
