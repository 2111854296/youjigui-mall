#!/usr/bin/env python3
from pathlib import Path

root = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
css = root / "ChannelBanner.BlvpXtWP.css"
s = css.read_text(encoding="utf-8")
bak = css.with_suffix(".css.bak_size")
if not bak.exists():
    bak.write_text(s, encoding="utf-8")

changed = False
if "height:340px" in s:
    s = s.replace("height:340px", "height:350px")
    changed = True
    print("replaced 340->350")

inject = (
    ".channel-banner[data-v-1f086d4e]{max-width:1240px;width:100%}"
    ".channel-banner.large[data-v-1f086d4e]{height:350px}"
    ".channel-banner.large .slide[data-v-1f086d4e],"
    ".channel-banner.large .slide img[data-v-1f086d4e]{height:350px;width:100%;object-fit:cover}"
)
if "max-width:1240px" not in s:
    s = inject + s
    changed = True
    print("injected size rules")
else:
    print("already has 1240")

if changed:
    css.write_text(s, encoding="utf-8")
    print("wrote", css)
else:
    print("no change to", css.name)

# Override with cache-bust for safety
ov = root / "channel-banner-size.fix.css"
ov.write_text(
    "\n".join(
        [
            ".channel-banner{max-width:1240px!important;width:100%!important}",
            ".channel-banner.large{height:350px!important}",
            ".channel-banner.large .slide,",
            ".channel-banner.large .slide img{height:350px!important;width:100%!important;object-fit:cover}",
            "@media (max-width:700px){",
            "  .channel-banner.large,",
            "  .channel-banner.large .slide,",
            "  .channel-banner.large .slide img{height:160px!important}",
            "}",
            "",
        ]
    ),
    encoding="utf-8",
)
print("override", ov)

# Ensure override is linked from index.html (SPA shell) — also helps first paint
html = Path("/www/wwwroot/g.aifeili.cn/public/index.html")
h = html.read_text(encoding="utf-8")
tag = '<link rel="stylesheet" href="/_nuxt/channel-banner-size.fix.css?v=1">'
if "channel-banner-size.fix.css" not in h:
    if "</head>" in h:
        h = h.replace("</head>", tag + "\n</head>", 1)
    else:
        h += tag
    html.write_text(h, encoding="utf-8")
    print("linked override in index.html")
else:
    print("index.html already linked")

for f in root.glob("*.css"):
    t = f.read_text(encoding="utf-8", errors="ignore")
    if "channel-banner.large" in t and "height:340px" in t:
        f.write_text(t.replace("height:340px", "height:350px"), encoding="utf-8")
        print("also patched", f.name)

print("ok")
