#!/usr/bin/env python3
from pathlib import Path
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for p in nuxt.glob("QuoteKline*.css"):
  print("==", p.name)
  print(p.read_text(encoding="utf-8",errors="ignore")[:2500])
for p in nuxt.glob("*e8aab47a*"):
  print("scope", p.name)
# find css with data-v-e8aab47a
for p in nuxt.glob("*.css"):
  t=p.read_text(encoding="utf-8",errors="ignore")
  if "e8aab47a" in t or "y-badge" in t:
    print("CSS", p.name)
    if "y-col" in t:
      import re
      for m in re.finditer(r"[^{}]*y-col[^{]*\{[^}]+\}", t):
        print(m.group()[:200])
      for m in re.finditer(r"[^{}]*line-chart[^{]*\{[^}]+\}", t):
        print(m.group()[:250])
      for m in re.finditer(r"[^{}]*y-badge[^{]*\{[^}]+\}", t):
        print(m.group()[:200])
