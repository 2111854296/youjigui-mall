#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import shutil, time
ts=str(int(time.time()))

# Inject after const f=C: expose candles + appContext hook via watch in setup
# Find: const f=C,ct=it,
# Add watch that syncs window.__YJG_QUOTE_CANDLES__

# After setup begins, add Lt(()=>f.candles, o=>{try{window.__YJG_QUOTE_CANDLES__=o||[];window.__YJG_QUOTE_APP_CTX__=...}catch(e){}},{immediate:!0});
# But we need component instance for appContext - use getCurrentInstance
# Chart already imports: e as Mt (defineComponent?), u as Bt (useAuth), D as Ft (useRoute), f as Lt (watch), ...
# Check: Lt is watch from import f as Lt

# Simpler: inside chart computed u=R(()=>{const o=f.candles||[] ... at start:
# try{window.__YJG_QUOTE_CANDLES__=o.slice()}catch(e){}

old = 'u=R(()=>{const o=f.candles||[],e=m.value;if(o.length<1)'
new = 'u=R(()=>{const o=f.candles||[],e=m.value;try{if(!f.expanded){window.__YJG_QUOTE_CANDLES__=o;window.__YJG_QUOTE_META__={mode:f.mode,n:o.length,t:Date.now()}}}catch(err){}if(o.length<1)'

for name in ["C-jR4i7n.js","C-jR4i7n.fit9.js","C-jR4i7n.fit8.js"]:
  p=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")/name
  s=p.read_text(encoding="utf-8")
  if old not in s:
    print("MISS", name, "already" if "__YJG_QUOTE_CANDLES__" in s else "diff")
    continue
  shutil.copy2(p, str(p)+".bak_win_"+ts)
  p.write_text(s.replace(old,new,1), encoding="utf-8")
  print("OK", name)

# Also need getCurrentInstance for provides - patch createApp mount to use nuxt from #__nuxt
print("check", "__YJG_QUOTE_CANDLES__" in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text())
