#!/usr/bin/env python3
from pathlib import Path
t=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/0a3-bBq-.js").read_text(encoding="utf-8",errors="ignore")
# find createApp export alias - look near export
import re
# fh as $ etc - find which is createApp
idx=t.find("createApp")
print("contexts:")
while idx>=0 and idx<80000:
  print(t[max(0,idx-60):idx+80].replace("\n"," ")[:140])
  idx=t.find("createApp", idx+10)
  if idx>90000: break
# parse export map for createApp - search "createApp as"
m=re.search(r"createApp as ([A-Za-z0-9$]+)", t)
print("as alias", m.group(1) if m else None)
# reverse: export{ X as createApp} won't happen
# find function createApp
m=re.search(r"function ([A-Za-z0-9$]+)\([^)]*\)\{[^}]{0,40}createApp", t)
# Nuxt often: const createApp = ...
# Look at export list - Bt as A - need to find createApp binding
# Search ",createApp=" or "createApp="
for m in re.finditer(r"([A-Za-z0-9$]+)=createApp|createApp=([A-Za-z0-9$]+)", t):
  print("bind", m.group())
# In vite vue: export { createApp as xx }
# Try: which exported name equals createApp by reading vue runtime chunk name
# Simpler: dump export pairs containing App
ex=t[t.rfind("export{"):]
# find createApp in the minified by unique string "mount(container"
idx=t.find(".mount=")
print("mount", t[idx-100:idx+50] if idx>0 else None)
idx=t.find("createApp(")
print("call", t[max(0,idx-30):idx+50] if idx>0 else None)
# Get the actual createApp function name from definition
idx=t.find("function createApp")
print("fn", t[idx:idx+100] if idx>=0 else "no")
idx=t.find("createApp=function")
print("fn2", t[idx:idx+80] if idx>=0 else "no")
# vue 3.4 might use const createApp = (root) =>
idx=75594
print("at75594", t[idx-20:idx+200])
