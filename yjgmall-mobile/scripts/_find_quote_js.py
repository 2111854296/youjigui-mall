#!/usr/bin/env python3
from pathlib import Path
bases=["/www/wwwroot/g.aifeili.cn/public/_nuxt","/www/wwwroot/www.aifeili.cn","/www/wwwroot/v.aifeili.cn/public/_nuxt"]
needles=["近两年","近 1 月","两年","RANGE_OPTS","quoteWindowStart"]
for base in bases:
  p=Path(base)
  if not p.exists():
    print("missing", base); continue
  print("==", base)
  n=0
  for f in p.rglob("*.js"):
    if f.stat().st_size>4_000_000: continue
    try: t=f.read_text(encoding="utf-8",errors="ignore")
    except Exception: continue
    hit=[x for x in needles if x in t]
    if hit:
      print(f.name, hit, f.stat().st_size)
      n+=1
      if n>=8: break
