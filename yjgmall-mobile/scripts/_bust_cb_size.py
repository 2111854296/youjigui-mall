#!/usr/bin/env python3
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
public = Path("/www/wwwroot/g.aifeili.cn/public")
old = root / "ChannelBanner.BlvpXtWP.css"

tail = """
.channel-banner{max-width:1240px!important;width:100%!important}
.channel-banner.large{height:350px!important}
.channel-banner.large .slide,.channel-banner.large .slide img{height:350px!important;width:100%!important;object-fit:cover!important}
@media (max-width:700px){.channel-banner.large,.channel-banner.large .slide,.channel-banner.large .slide img{height:160px!important}}
"""

base = old.read_text(encoding="utf-8")
base = re.sub(
    r"^\.channel-banner\[data-v-1f086d4e\]\{max-width:1240px;width:100%\}.*?object-fit:cover\}",
    "",
    base,
)
base = base.replace("height:340px", "height:350px")

new_name = "ChannelBanner.BlvpXtWP.s350.css"
newp = root / new_name
newp.write_text(base + "\n" + tail + "\n", encoding="utf-8")
print("wrote", newp, "len", newp.stat().st_size)

count = 0
for f in list(root.glob("*.js")) + list(root.glob("*.css")) + list(public.rglob("*.html")):
    try:
        t = f.read_text(encoding="utf-8")
    except Exception:
        continue
    if "ChannelBanner.BlvpXtWP.css" in t and "ChannelBanner.BlvpXtWP.s350.css" not in t:
        f.write_text(
            t.replace("ChannelBanner.BlvpXtWP.css", "ChannelBanner.BlvpXtWP.s350.css"),
            encoding="utf-8",
        )
        count += 1
        print("ref", f)
print("refs updated", count)

ov = root / "channel-banner-size.fix.css"
ov.write_text(tail.strip() + "\n", encoding="utf-8")
tag = '<link rel="stylesheet" href="/_nuxt/channel-banner-size.fix.css?v=2">'
for html in public.rglob("*.html"):
    t = html.read_text(encoding="utf-8", errors="ignore")
    if "channel-banner-size.fix.css" in t:
        t2 = re.sub(
            r"channel-banner-size\.fix\.css\?v=\d+",
            "channel-banner-size.fix.css?v=2",
            t,
        )
        if t2 != t:
            html.write_text(t2, encoding="utf-8")
            print("bump", html)
        continue
    if "</head>" in t:
        html.write_text(t.replace("</head>", tag + "\n</head>", 1), encoding="utf-8")
        print("link", html)

print("tail ok", newp.read_text(encoding="utf-8")[-220:])
print("ok")
