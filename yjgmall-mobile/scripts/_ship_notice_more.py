#!/usr/bin/env python3
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn")

# 1) API limit 6 -> 10
for rel in [
    "app/controller/api/v1/Home.php",
    "app/controller/api/Home.php",
]:
    p = root / rel
    if not p.exists():
        continue
    t = p.read_text(encoding="utf-8")
    nt = t
    # announcements limit
    nt = re.sub(
        r"(announcements[\s\S]{0,220}?->limit\()6(\))",
        r"\g<1>10\2",
        nt,
        count=1,
    )
    if "Announcement::where('is_active', 1)->order('sort', 'desc')->limit(6)" in nt:
        nt = nt.replace(
            "Announcement::where('is_active', 1)->order('sort', 'desc')->limit(6)",
            "Announcement::where('is_active', 1)->order('sort', 'desc')->limit(10)",
        )
    if nt != t:
        p.write_text(nt, encoding="utf-8")
        print("api", rel)
    else:
        print("api skip/no match", rel)

# 2) CSS override for notice-card fill
css = """
.notice-card{display:flex!important;flex-direction:column!important;min-height:0}
.notice-card ul{margin:0;padding:0;list-style:none;flex:1;min-height:0;display:flex;flex-direction:column}
.notice-card li{flex:1 1 auto;display:flex;align-items:center;min-height:28px}
.notice-card li a{width:100%}
""".strip() + "\n"
nuxt = root / "public/_nuxt"
(nuxt / "notice-more.fix.css").write_text(css, encoding="utf-8")

# Also patch entry/portal css if notice-card present
for f in nuxt.glob("*.css"):
    try:
        t = f.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if ".notice-card{" not in t and ".notice-card{" not in t:
        continue
    if "notice-card ul{margin:0" in t or "notice-more" in f.name:
        continue
    # append rules at end for files that define notice-card
    if ".notice-card" in t and "flex-direction:column!important" not in t:
        f.write_text(t + "\n" + css, encoding="utf-8")
        print("css append", f.name)

# 3) JS: slice(0,4) -> slice(0,10) around announcements
count = 0
for f in nuxt.glob("*.js"):
    try:
        t = f.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if "announcements" not in t and "notice-card" not in t:
        continue
    nt = t
    # common patterns
    for a, b in [
        (".slice(0,4)", ".slice(0,10)"),
        (".slice(0, 4)", ".slice(0, 10)"),
        ("slice(0,4)", "slice(0,10)"),
        ("slice(0, 4)", "slice(0, 10)"),
    ]:
        # only replace near announcements to avoid breaking other slices
        if "announcements" in nt and a in nt:
            # replace all slice(0,4) in files that mention announcements+notice — risky
            pass
    # safer: replace only when announcements appears within 200 chars of slice
    def repl_slice(m):
        return m.group(0).replace("slice(0,4)", "slice(0,10)").replace("slice(0, 4)", "slice(0, 10)")

    nt2 = re.sub(
        r"announcements.{0,180}?slice\(0,\s*4\)|slice\(0,\s*4\).{0,180}?announcements",
        repl_slice,
        nt,
        flags=re.S,
    )
    if nt2 != t:
        bak = f.with_suffix(f.suffix + ".bak_notice10")
        if not bak.exists():
            bak.write_text(t, encoding="utf-8")
        f.write_text(nt2, encoding="utf-8")
        count += 1
        print("js", f.name)
print("js patched", count)

# 4) link css in html
tag = '<link rel="stylesheet" href="/_nuxt/notice-more.fix.css?v=1">'
for name in ["index.html", "200.html"]:
    html = root / "public" / name
    if not html.exists():
        continue
    h = html.read_text(encoding="utf-8", errors="ignore")
    if "notice-more.fix.css" in h:
        continue
    if "</head>" in h:
        html.write_text(h.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
        print("link", name)
print("ok")
