#!/usr/bin/env python3
from pathlib import Path
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for f in nuxt.glob("*.js"):
  if f.stat().st_size>2_000_000: continue
  try: t=f.read_text(encoding="utf-8",errors="ignore")
  except Exception: continue
  if "左右滑动查看更早的日期" in t or "quoteLineFill" in t or "ChartMode" in t:
    print("chart", f.name, f.stat().st_size)
  if "详情展开" in t:
    print("expand already", f.name)
  if 'key:"2y",label:"两年"' in t or 'label:"两年",days:730' in t:
    print("page", f.name, f.stat().st_size)
