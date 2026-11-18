#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import shutil, time
p = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/eu_lJI6T.js")
s = p.read_text(encoding="utf-8")
ts = str(int(time.time()))

# Find usages of y-col - search for se, and right ke
# From earlier dump: se={class:"y-col left"...} ke={class:"y-col right"...}
# In return: c("div",se,[...]) and c("div",ke,[...])

# Better replace static props with dynamic:
# Change se to not be used statically - replace render sites

# Pattern from full dump earlier:
# c("div",se,[(r(!0),l(b,null,A(e(s).yLeft,...
# c("div",ke,[(r(!0),l(b,null,A(e(s).yRight,...

old_l = 'c("div",se,['
new_l = 'c("div",{class:"y-col left","aria-hidden":"true",style:X({height:e(s).h+"px"})},['

old_r = 'c("div",ke,['
new_r = 'c("div",{class:"y-col right","aria-hidden":"true",style:X({height:e(s).h+"px"})},['

# svg: height:e(s).h,class:"line-chart" -> add style
old_svg = 'height:e(s).h,class:"line-chart",preserveAspectRatio:"none"'
new_svg = 'height:e(s).h,class:"line-chart",style:X({height:e(s).h+"px"}),preserveAspectRatio:"none"'

shutil.copy2(p, str(p)+".bak_h_"+ts)
ok = True
for name, a, b in [("left", old_l, new_l), ("right", old_r, new_r), ("svg", old_svg, new_svg)]:
    if a not in s:
        print("MISS", name)
        # try find alternatives
        if name == "left":
            print("se uses", s.count("se,"), s[s.find("c(\"div\",se"):s.find("c(\"div\",se")+40] if "c(\"div\",se" in s else "no")
        if name == "right":
            print("ke", [s[i:i+30] for i in [s.find("c(\"div\",ke")]])
        ok = False
    else:
        s = s.replace(a, b, 1)
        print("OK", name)

# Also need X (normalizeStyle / style merge) - chart imports q as X which is style/normalizeStyle from earlier: q as X
# From import: q as X - yes style in vue 3 is often mergeProps. Check: `style:X({` already used for yLeft spans: style:X({top:...
# Good.

p.write_text(s, encoding="utf-8")
print("wrote", ok)

# verify css
css = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/QuoteKlineChart.0h5oYOfJ.css").read_text()
print("css overview !important", "280px!important" in css, "360px!important" in css, "height:auto" in css or ":not(.overview)" in css)

# verify DMee threshold
d = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DMee_jht.js").read_text(encoding="utf-8")
print(">=90 expand", ">=90" in d, d[d.find("_t=u(()=>F.value"):d.find("_t=u(()=>F.value")+40])
