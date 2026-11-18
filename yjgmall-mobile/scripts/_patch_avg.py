#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
nuxt = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
repls = [
    ('n(m)||n(d).market||"市场参考"', 'n(m)||"均价"'),
    ('s(p)||s(d).market||"市场参考"', 's(p)||"均价"'),
    # other minify variants
    ('||n(d).market||"市场参考"', '||"均价"'),
    ('||s(d).market||"市场参考"', '||"均价"'),
]
n = 0
for p in nuxt.glob("*.js"):
    t = p.read_text(encoding="utf-8", errors="ignore")
    if "市场参考" not in t:
        continue
    if "平均价格" not in t and "price_min" not in t:
        continue
    orig = t
    for a, b in repls:
        if a in t:
            t = t.replace(a, b)
    if t != orig:
        p.write_text(t, encoding="utf-8")
        n += 1
        print("OK", p.name, "left", t.count("市场参考"))
print("patched", n)
# also check local output
