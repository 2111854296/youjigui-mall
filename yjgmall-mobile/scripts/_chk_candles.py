#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8")
# how props exposed - and if we can hook candles to window
i=s.find("props:{candles")
print(s[i:i+200])
# setup start - where f=C
i=s.find("setup(C")
print(s[i:i+250])
# watch candles
print("candles refs", s.count("f.candles"))
