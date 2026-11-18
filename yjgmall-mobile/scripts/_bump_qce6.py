from pathlib import Path
import re
p = Path("/www/wwwroot/g.aifeili.cn/public/index.html")
t = p.read_text(encoding="utf-8")
t2 = re.sub(r"quote-chart-expand\.js\?v=\d+", "quote-chart-expand.js?v=6", t)
p.write_text(t2, encoding="utf-8")
js = Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js")
txt = js.read_text(encoding="utf-8")
print("size", js.stat().st_size)
print("v6", "__quoteChartExpandV6" in txt)
print("hover", "bindHover" in txt)
print("index", "v=6" in p.read_text(encoding="utf-8"))
fit = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/C-jR4i7n.fit9.js").read_text(encoding="utf-8")
print("data-label", "data-label" in fit)
