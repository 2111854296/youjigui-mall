#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import shutil

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
FIX = (
    ".banner-carousel .slide{pointer-events:none!important;z-index:0}"
    ".banner-carousel .slide.on{pointer-events:auto!important;z-index:1}"
)

patched = []
for f in os.listdir(ROOT):
    if not f.endswith(".css"):
        continue
    p = os.path.join(ROOT, f)
    s = open(p, encoding="utf-8", errors="ignore").read()
    if "banner-carousel" not in s:
        continue
    # show current slide rules
    print("FILE", f)
    for m in re.finditer(r".banner-carousel \.slide(?:\.on)?\{[^}]*\}", s):
        print(" ", m.group(0)[:220])
    if "pointer-events:none!important" in s:
        print("  already forced")
        continue
    bak = p + ".bak_pe2"
    if not os.path.isfile(bak):
        open(bak, "w", encoding="utf-8").write(s)
    # append force rules at end
    open(p, "w", encoding="utf-8").write(s + "\n" + FIX + "\n")
    patched.append(f)
    print("  appended force")

# also create dedicated pe css and inject into entry html/js
pe_name = "banner-pe.fix.css"
open(os.path.join(ROOT, pe_name), "w", encoding="utf-8").write(FIX + "\n")

# inject link into index.html if exists
for html_path in [
    "/www/wwwroot/g.aifeili.cn/public/index.html",
    "/www/wwwroot/g.aifeili.cn/index.html",
]:
    if not os.path.isfile(html_path):
        continue
    h = open(html_path, encoding="utf-8", errors="ignore").read()
    if pe_name in h:
        print("html already", html_path)
        continue
    if "</head>" in h:
        h = h.replace(
            "</head>",
            f'<link rel="stylesheet" href="/_nuxt/{pe_name}">\n</head>',
            1,
        )
        open(html_path, "w", encoding="utf-8").write(h)
        print("html injected", html_path)
    else:
        print("html no head", html_path)

# inject into entry js as CSS import string if possible
n = 0
for f in os.listdir(ROOT):
    if not f.startswith("entry.") or not f.endswith(".js"):
        continue
    p = os.path.join(ROOT, f)
    s = open(p, encoding="utf-8", errors="ignore").read()
    if pe_name in s:
        print("entry already", f)
        continue
    # find first css preload/link pattern
    if "entry." in s and ".css" in s:
        # append import() or createElement link at start of entry is hard;
        # replace a known css ref to also load pe — simpler: add after first createElement('link')
        needle = 'createElement("link")'
        i = s.find(needle)
        if i < 0:
            needle = "createElement('link')"
            i = s.find(needle)
        if i >= 0:
            inject = (
                ';(()=>{var l=document.createElement("link");l.rel="stylesheet";'
                'l.href="/_nuxt/' + pe_name + '";document.head.appendChild(l);})()'
            )
            # put near beginning after "use strict" if any
            if s.lstrip().startswith('"use strict"') or s.lstrip().startswith("'use strict'"):
                # after first semicolon
                j = s.find(";")
                s = s[: j + 1] + inject + s[j + 1 :]
            else:
                s = inject + ";" + s
            open(p, "w", encoding="utf-8").write(s)
            print("entry inject", f)
            n += 1
print("patched_css", patched, "entry_n", n)
