#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import re, shutil, time
nuxt = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
ts = str(int(time.time()))

# 1) quotes page: >=180 -> >=90
for p in nuxt.glob("*.js"):
    t = p.read_text(encoding="utf-8", errors="ignore")
    if "详情展开" not in t and "chart-expand" not in t:
        continue
    orig = t
    # various minify forms of selectedDays >= 180
    for a, b in [
        (".value>=180", ".value>=90"),
        (">=180)", ">=90)"),
        ("days.value>=180", "days.value>=90"),
    ]:
        if a in t and "canExpand" in t or "详情展开" in t:
            # only replace if context near expand
            pass
    # safer: find unique near 详情展开
    if ">=180" in t:
        # replace all >=180 that are expand thresholds - show contexts
        idxs = [m.start() for m in re.finditer(r">=180", t)]
        for i in idxs:
            print(p.name, "ctx", t[max(0,i-60):i+40])
        t2 = t.replace(">=180", ">=90")
        # might be too broad - check count
        if t.count(">=180") <= 3:
            shutil.copy2(p, str(p)+".bak_3m_"+ts)
            p.write_text(t2, encoding="utf-8")
            print("OK expand thresh", p.name, t.count(">=180"))
        else:
            print("SKIP many >=180", p.name, t.count(">=180"))

# 2) chart css: kill fixed 200px on line-chart; rely on attr + force overview/expanded
css = nuxt / "QuoteKlineChart.0h5oYOfJ.css"
if css.exists():
    c = css.read_text(encoding="utf-8")
    shutil.copy2(css, str(css)+".bak_align_"+ts)
    # remove height:200px from .line-chart rule; keep overview/expanded overrides strong
    c2 = c.replace(
        ".line-chart[data-v-e8aab47a]{cursor:crosshair;display:block;height:200px;max-width:none;touch-action:pan-x pan-y;z-index:1}",
        ".line-chart[data-v-e8aab47a]{cursor:crosshair;display:block;max-width:none;touch-action:pan-x pan-y;z-index:1;height:auto}",
    )
    if "expanded .line-chart" not in c2:
        c2 += ".line-wrap.overview .line-chart[data-v-e8aab47a],.line-wrap.overview[data-v-e8aab47a] .line-chart[data-v-e8aab47a]{height:280px!important}.line-wrap.expanded .line-chart[data-v-e8aab47a],.line-wrap.expanded[data-v-e8aab47a] .line-chart[data-v-e8aab47a]{height:360px!important}"
    else:
        # strengthen
        c2 = c2.replace(
            ".line-wrap.overview .line-chart[data-v-e8aab47a]{height:280px}",
            ".line-wrap.overview .line-chart[data-v-e8aab47a]{height:280px!important}",
        ).replace(
            ".line-wrap.expanded .line-chart[data-v-e8aab47a]{height:360px}",
            ".line-wrap.expanded .line-chart[data-v-e8aab47a]{height:360px!important}",
        )
    # default non-overview: height from attribute - use 200px as fallback matching y-col
    if "height:auto" in c2 and ".y-col[data-v-e8aab47a]{height:200px" in c2:
        # sync: when not overview/expanded, line-chart should be 200
        if "line-wrap:not" not in c2:
            c2 += ".line-wrap[data-v-e8aab47a]:not(.overview):not(.expanded) .line-chart[data-v-e8aab47a]{height:200px!important}"
    css.write_text(c2, encoding="utf-8")
    print("OK css")

print("DONE")
