#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.js").read_text(encoding="utf-8")
idx=s.find("line-wrap")
print(repr(s[idx-80:idx+120]))
# expand end
t=Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js").read_text(encoding="utf-8")
print("END", repr(t[-80:]))
print("len", len(t))
