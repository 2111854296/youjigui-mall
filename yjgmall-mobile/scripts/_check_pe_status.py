#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
root = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
print("pe css", os.path.isfile(os.path.join(root, "banner-pe.fix.css")))
for f in ["0a3-bBq-.js", "entry.DRch0_HE.css", "index.RB4plv3A.css"]:
    p = os.path.join(root, f)
    if not os.path.isfile(p):
        print(f, "MISSING")
        continue
    s = open(p, encoding="utf-8", errors="ignore").read(500)
    print(f, "banner-pe" in open(p, encoding="utf-8", errors="ignore").read(), "pe none", "pointer-events:none" in open(p, encoding="utf-8", errors="ignore").read())
html = "/www/wwwroot/g.aifeili.cn/public/index.html"
if os.path.isfile(html):
    print("index.html", "banner-pe" in open(html, encoding="utf-8", errors="ignore").read())
