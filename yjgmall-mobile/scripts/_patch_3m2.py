#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import shutil, time
nuxt = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
ts = str(int(time.time()))

# also fix old quote page chunks still on server
for p in list(nuxt.glob("CPYqRJ-v*.js")):
    t = p.read_text(encoding="utf-8", errors="ignore")
    if ">=180" not in t:
        continue
    if "详情展开" not in t and "chart-expand" not in t:
        continue
    n = t.count(">=180")
    shutil.copy2(p, str(p)+".bak_3m_"+ts)
    p.write_text(t.replace(">=180", ">=90"), encoding="utf-8")
    print("OK", p.name, n)

# QuoteKlineChart: patch y-col to use style height from chart.h
# Look for class:"y-col left" pattern in eu_lJI6T.js
chart = nuxt / "eu_lJI6T.js"
s = chart.read_text(encoding="utf-8")
# find y-col left
i = s.find('class:"y-col left"')
print("ycol", s[i:i+200] if i>=0 else "nf")
i2 = s.find('class:"y-col right"')
print("ycolR", s[i2:i2+120] if i2>=0 else "nf")
# find svg height binding
i3 = s.find("line-chart")
print("svg nearby height", s[max(0,s.find('height:e(s).h')):s.find('height:e(s).h')+80] if 'height:e(s).h' in s else s[s.find("class:\"line-chart\""):s.find("class:\"line-chart\"")+100])
