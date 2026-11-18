#!/usr/bin/env python3
# -*- coding: utf-8 -*-
from pathlib import Path
import shutil, time, re
nuxt = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
ts = str(int(time.time()))

# New chart component
p = nuxt / "eu_lJI6T.js"
s = p.read_text(encoding="utf-8")
shutil.copy2(p, str(p) + ".bak_dotr_" + ts)

# Template circles: r:"4.5" -> use dynamic from chart; minified may be r:"4.5"
# Also return showDots:h.overview&&!S.value -> add ,dotR:...
old_ret = "showDots:h.overview&&!S.value"
# After points length f is in scope as f=a.length in that computed - variable is `f` for n
# From earlier dump: const f=a.length ... showDots:h.overview&&!S.value
new_ret = 'showDots:h.overview&&!S.value,dotR:h.overview&&!S.value?(f>=220?1.4:f>=140?1.7:f>=80?2:2.2):2.2'
if old_ret not in s:
    # try alternate
    print("MISS ret", "showDots" in s)
    i = s.find("showDots:")
    print(s[i:i+80] if i>=0 else "none")
else:
    s = s.replace(old_ret, new_ret, 1)
    print("OK ret")

# circle r:"4.5" -> r:e(s).dotR
old_c = 'r:"4.5",fill:"#2468F2",stroke:"#fff","stroke-width":"2"'
new_c = 'r:e(s).dotR,fill:"#2468F2",stroke:"#fff","stroke-width":e(s).dotR<=1.7?1:1.4'
if old_c not in s:
    print("MISS circle", s[s.find('r:"4.5"'):s.find('r:"4.5"')+90] if 'r:"4.5"' in s else "no 4.5")
else:
    s = s.replace(old_c, new_c, 1)
    print("OK circle")

# empty return showDots:!1 need dotR
old_empty = "showDots:!1}"
if "showDots:!1,dotR" not in s and "showDots:!1}" in s:
    # careful - might match wrong
    # from dump: scrollable:!1,showDots:!1};
    if "scrollable:!1,showDots:!1}" in s:
        s = s.replace("scrollable:!1,showDots:!1}", "scrollable:!1,showDots:!1,dotR:2.2}", 1)
        print("OK empty")
    else:
        print("empty form", s[s.find("showDots:!1")-30:s.find("showDots:!1")+40])

p.write_text(s, encoding="utf-8")

# Also old hotpatched C-jR4i7n if still referenced
for name in ["C-jR4i7n.js", "C-jR4i7n.fit9.js", "C-jR4i7n.fit8.js"]:
    fp = nuxt / name
    if not fp.exists():
        continue
    t = fp.read_text(encoding="utf-8", errors="ignore")
    if 'r:"4.5"' not in t:
        continue
    shutil.copy2(fp, str(fp) + ".bak_dotr_" + ts)
    t = t.replace('r:"4.5",fill:"#2468F2",stroke:"#fff","stroke-width":"2"', 'r:1.8,fill:"#2468F2",stroke:"#fff","stroke-width":"1.2"', 1)
    fp.write_text(t, encoding="utf-8")
    print("OK old", name)

print("verify", "dotR" in Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/eu_lJI6T.js").read_text())
