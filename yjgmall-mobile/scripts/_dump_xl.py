#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/eu_lJI6T.js").read_text(encoding="utf-8")
i=s.find("h.overview&&f>=3")
print("A", i, s[i:i+350] if i>=0 else None)
i=s.find("overview&&f>=3")
print("B", i, s[i:i+350] if i>=0 else None)
i=s.find("Math.floor((f-1)/2)")
print("C", i, s[max(0,i-80):i+200] if i>=0 else None)
