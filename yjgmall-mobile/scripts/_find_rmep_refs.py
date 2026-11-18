#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
NUXT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
for f in os.listdir(NUXT):
    if not f.endswith(".js"):
        continue
    p = os.path.join(NUXT, f)
    try:
        s = open(p, encoding="utf-8", errors="ignore").read()
    except Exception:
        continue
    if "rMEP6MmM" in s:
        print(f, "js" if "rMEP6MmM.js" in s else "-", "ss" if "rMEP6MmM.ss.js" in s else "-")
