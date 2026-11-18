from pathlib import Path
import re
p = Path("/www/wwwroot/g.aifeili.cn/public/index.html")
t = p.read_text(encoding="utf-8")
t2 = re.sub(r"quote-chart-expand\.js\?v=\d+", "quote-chart-expand.js?v=4", t)
p.write_text(t2, encoding="utf-8")
js = Path("/www/wwwroot/g.aifeili.cn/public/quote-chart-expand.js")
print("size", js.stat().st_size)
print("has widen circles", "readSeriesPoints" in js.read_text(encoding="utf-8"))
print("index", "v=4" in t2)
