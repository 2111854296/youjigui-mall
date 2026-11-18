from pathlib import Path
import re
p = Path("/www/wwwroot/g.aifeili.cn/public/index.html")
t = p.read_text(encoding="utf-8")
t2 = re.sub(r"quote-chart-expand\.js\?v=\d+", "quote-chart-expand.js?v=5", t)
if "quote-chart-expand.js" in t2 and "v=5" not in t2:
    t2 = t2.replace("quote-chart-expand.js", "quote-chart-expand.js?v=5")
p.write_text(t2, encoding="utf-8")
js = Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js")
txt = js.read_text(encoding="utf-8")
print("size", js.stat().st_size)
print("rebuild", "buildExpandedChart" in txt)
print("flag", "__quoteChartExpandV5" in txt)
print("index", "v=5" in p.read_text(encoding="utf-8"))
