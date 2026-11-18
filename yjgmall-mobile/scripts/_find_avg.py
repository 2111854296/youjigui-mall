#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import re
nuxt = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
# find quote detail chunks with the market fallback text
pat_old = None
hits = []
for p in nuxt.glob("*.js"):
    t = p.read_text(encoding="utf-8", errors="ignore")
    if "市场参考" in t and ("active_region" in t or "平均价格" in t or "dt-quote-hero" in t or "比昨天" in t):
        hits.append(p.name)
        # show nearby
        i = t.find("市场参考")
        print("HIT", p.name, t[max(0,i-120):i+80])
print("files", hits)
