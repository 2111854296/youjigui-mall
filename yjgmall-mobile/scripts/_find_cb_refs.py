#!/usr/bin/env python3
from pathlib import Path

public = Path("/www/wwwroot/g.aifeili.cn/public")
needle = "ChannelBanner.BlvpXtWP.css"
# keep s350 out
for f in public.rglob("*"):
    if not f.is_file():
        continue
    if f.suffix.lower() not in {".js", ".html", ".css", ".json", ".mjs"}:
        continue
    if "s350" in f.name:
        continue
    try:
        t = f.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if needle in t and "ChannelBanner.BlvpXtWP.s350.css" not in t.replace(needle, "", 1):
        # still has bare old name
        if "ChannelBanner.BlvpXtWP.css" in t:
            # count occurrences of old without s350
            if t.count("ChannelBanner.BlvpXtWP.css") > t.count("ChannelBanner.BlvpXtWP.s350.css"):
                print(f)
                # auto-fix
                f.write_text(
                    t.replace("ChannelBanner.BlvpXtWP.css", "ChannelBanner.BlvpXtWP.s350.css"),
                    encoding="utf-8",
                )
                print("  patched")

# Also overwrite old css file content with new so cached links still get 350
old = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/ChannelBanner.BlvpXtWP.css")
new = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/ChannelBanner.BlvpXtWP.s350.css")
if new.exists():
    old.write_text(new.read_text(encoding="utf-8"), encoding="utf-8")
    print("synced old css path to s350 content")
print("done")
