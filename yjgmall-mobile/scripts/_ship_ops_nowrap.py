#!/usr/bin/env python3
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")
nuxt = root / "public" / "_nuxt"

css = r"""
/* 后台列表：操作列单行；表体横滑，表头同步（不要各自滚动） */
.tbl .ops,
.el-table .ops{
  display:inline-flex!important;
  flex-wrap:nowrap!important;
  align-items:center!important;
  gap:2px!important;
  white-space:nowrap!important;
}
.el-table__header-wrapper,
.tbl .el-table__header-wrapper{
  overflow-x:hidden!important;
}
.el-table__body-wrapper,
.tbl .el-table__body-wrapper,
.el-table .el-table__body-wrapper{
  overflow-x:auto!important;
}
.el-table__fixed-right,
.el-table__fixed-right-patch{
  min-width:320px!important;
}
.el-table .el-table__fixed-right .el-table__cell .cell{
  white-space:nowrap!important;
  overflow:visible!important;
}
.el-table__fixed-right .el-button.is-link{
  margin:0 2px!important;
  padding:0 2px!important;
}
""".strip() + "\n"

(nuxt / "admin-ops-nowrap.fix.css").write_text(css, encoding="utf-8")
print("wrote css")

# keep ops min-width patch if old chunk still present
replacements = [
    ('e(r)==="quote-products"?340:220', 'e(r)==="quote-products"?360:320'),
    ("e(r)===\"quote-products\"?340:220", "e(r)===\"quote-products\"?360:320"),
]
for f in nuxt.glob("*.js"):
    try:
        s = f.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if "quote-products" not in s or "操作" not in s:
        continue
    orig = s
    for old, new in replacements:
        if old in s:
            s = s.replace(old, new)
            print("patched", f.name, old, "->", new)
    if s != orig:
        f.write_text(s, encoding="utf-8")

tag = '<link rel="stylesheet" href="/_nuxt/admin-ops-nowrap.fix.css?v=3">'
for html in list((root / "public").rglob("admin/**/index.html")) + [
    root / "public/index.html",
    root / "public/200.html",
]:
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "admin-ops-nowrap.fix.css" in h:
        h2 = re.sub(
            r'/_nuxt/admin-ops-nowrap\.fix\.css\?v=\d+',
            "/_nuxt/admin-ops-nowrap.fix.css?v=3",
            h,
        )
        if h2 != h:
            html.write_text(h2, encoding="utf-8")
            print("bump", html)
        continue
    if "</head>" not in h:
        continue
    html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
    print("link", html)
print("ok")
