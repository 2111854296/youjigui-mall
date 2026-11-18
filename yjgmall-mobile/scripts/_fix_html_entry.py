#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn/public"
NUXT = os.path.join(ROOT, "_nuxt")
html = os.path.join(ROOT, "index.html")
h = open(html, encoding="utf-8", errors="ignore").read()

# Fix entry refs: 0a3-bBq-.b1.js / .b2.js -> 0a3-bBq-.js
h2 = re.sub(r"0a3-bBq-\.b\d+\.js", "0a3-bBq-.js", h)
h2 = re.sub(r"0a3-bBq-b\d+\.js", "0a3-bBq-.js", h2)

# pe assets
h2 = re.sub(r'<link[^>]+banner-pe\.fix\.css[^>]*>\s*', '', h2)
h2 = re.sub(r'<script[^>]+banner-pe\.fix\.js[^>]*>\s*</script>\s*', '', h2)
inject = (
    '<link rel="stylesheet" href="/_nuxt/banner-pe.fix.css?v=12">\n'
    '<script src="/_nuxt/banner-pe.fix.js?v=12" defer></script>\n'
)
if "banner-pe.fix.css?v=12" not in h2:
    if "</head>" in h2:
        h2 = h2.replace("</head>", inject + "</head>", 1)
    else:
        h2 = inject + h2

open(html, "w", encoding="utf-8").write(h2)
print("now entry", "0a3-bBq-.js" in h2, "b1" in h2, "pe" in h2)
# show script lines
for line in h2.splitlines():
    if "0a3" in line or "banner-pe" in line:
        print(line)

# Ensure 0a3-bBq-.js has pe boot and points to rMEP6MmM.js
p = os.path.join(NUXT, "0a3-bBq-.js")
t = open(p, encoding="utf-8", errors="ignore").read()
t = re.sub(r"rMEP6MmM(?:\.ss3|\.ss2|\.ss)?\.js", "rMEP6MmM.js", t)
boot = (
    ';(()=>{var c=document.createElement("link");c.rel="stylesheet";c.href="/_nuxt/banner-pe.fix.css?v=12";'
    'document.head.appendChild(c);var s=document.createElement("script");s.src="/_nuxt/banner-pe.fix.js?v=12";'
    's.defer=true;document.head.appendChild(s);})();'
)
# remove old boots
t = re.sub(
    r';\(\(\)=>\{var c=document\.createElement\("link"\);c\.rel="stylesheet";c\.href="/_nuxt/banner-pe\.fix\.css\?v=\d+";document\.head\.appendChild\(c\);var s=document\.createElement\("script"\);s\.src="/_nuxt/banner-pe\.fix\.js\?v=\d+";.*?\}\)\(\);',
    '',
    t,
)
if "banner-pe.fix.js?v=12" not in t:
    t = boot + t
open(p, "w", encoding="utf-8").write(t)
print("0a3 ok", "rMEP6MmM.js" in t, "boot", "banner-pe.fix.js?v=12" in t)

# verify restored chunk is original
chunk = open(os.path.join(NUXT, "rMEP6MmM.js"), encoding="utf-8").read()
print("chunk original vfor", "q(a(le),(n,m)=>(r(),o(S,{key:n.id||m}" in chunk)
print("chunk no filter", "[a(le)[a(A)]].filter" not in chunk)
print("chunk no ternary skip", "m!==a(A)?C(" not in chunk)
