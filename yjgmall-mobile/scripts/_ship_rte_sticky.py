#!/usr/bin/env python3
"""Live: richtext toolbar fixed, only body scrolls."""
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
nuxt = root / "public" / "_nuxt"

css = r"""
/* 富文本：工具栏固定，只滚正文 */
.rte{
  display:flex!important;
  flex-direction:column!important;
  max-height:min(72vh,680px)!important;
  position:relative;
}
.rte .rte-bar{
  flex:0 0 auto!important;
  position:sticky!important;
  top:0!important;
  z-index:5!important;
  background:linear-gradient(180deg,#f7f9fc,#f0f3f7)!important;
}
.rte .rte-body{
  flex:1 1 auto!important;
  overflow:auto!important;
  overscroll-behavior:contain;
  -webkit-overflow-scrolling:touch;
  min-height:var(--rte-min,180px);
}
""".strip() + "\n"

(nuxt / "rte-sticky.fix.css").write_text(css, encoding="utf-8")
print("wrote rte-sticky.fix.css")

tag = '<link rel="stylesheet" href="/_nuxt/rte-sticky.fix.css?v=1">'
for html in list((root / "public").rglob("admin/**/index.html")) + [
    root / "public/index.html",
    root / "public/200.html",
]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "rte-sticky.fix.css" in h:
        h2 = re.sub(r"rte-sticky\.fix\.css\?v=\d+", "rte-sticky.fix.css?v=1", h)
        if h2 != h:
            html.write_text(h2, encoding="utf-8")
            print("bump", html)
        continue
    if "</head>" not in h:
        continue
    html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
    print("link", html)

print("ok")
