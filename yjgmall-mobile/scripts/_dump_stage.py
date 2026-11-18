#!/usr/bin/env python3
from pathlib import Path
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8")
# dump template around line-stage / svg / watermark / x-axis
i=s.find('class:"line-stage"')
print("STAGE", s[i:i+900])
print("---")
i=s.find("chart-watermark")
print("WM", s[max(0,i-80):i+120])
print("---")
# CSS line-stage
css=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/QuoteKlineChart.KSdW9eP7.css").read_text(encoding="utf-8")
print(css)
