#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import shutil, time
p = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/eu_lJI6T.js")
s = p.read_text(encoding="utf-8")
ts = str(int(time.time()))

# Capture full xLabels block from let L=[] to before showDots
start = s.find("let L=[];if(h.overview&&f>=3)")
end = s.find("showDots:h.overview")
print("range", start, end)
print(s[start:end][:800])
print("---TAIL---")
print(s[end-120:end+80])
