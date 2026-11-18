#!/usr/bin/env python3
from pathlib import Path
sites=["/www/wwwroot/www.aifeili.cn","/www/wwwroot/v.aifeili.cn","/www/wwwroot/w.aifeili.cn","/www/wwwroot/o.aifeili.cn","/www/wwwroot/p.aifeili.cn"]
for s in sites:
  p=Path(s)
  if not p.exists():
    continue
  hits=[]
  for f in p.rglob("*"):
    if not f.is_file(): continue
    if f.suffix not in (".js",".html",".vue",".mjs"): continue
    if f.stat().st_size>5_000_000: continue
    try:
      t=f.read_text(encoding="utf-8",errors="ignore")
    except Exception:
      continue
    if "近 1 月" in t or "近两年" in t or "rangeTitle" in t or "废硅胶" in t:
      hits.append(str(f.relative_to(p))[:120])
      if len(hits)>=5: break
  if hits:
    print(s, hits)
