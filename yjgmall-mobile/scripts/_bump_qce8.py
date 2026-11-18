from pathlib import Path
import re
p = Path("/www/wwwroot/g.aifeili.cn/public/index.html")
t = p.read_text(encoding="utf-8")
t2 = re.sub(r"quote-chart-expand\.js\?v=\d+", "quote-chart-expand.js?v=8", t)
p.write_text(t2, encoding="utf-8")
js = Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js")
print("size", js.stat().st_size)
print("v8", "__quoteChartExpandV8" in js.read_text(encoding="utf-8"))
print("index", "v=8" in p.read_text(encoding="utf-8"))
print("win candles", "__YJG_QUOTE_CANDLES__" in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8"))
