#!/usr/bin/env python3
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for name in ["rMEP6MmM.js", "0a3-bBq-.js"]:
    p = root / name
    if not p.exists():
        print("missing", name)
        continue
    t = p.read_text(encoding="utf-8", errors="ignore")
    # find announce-related slices
    for m in re.finditer(r".{40}announcements.{0,120}slice\([^)]+\)|slice\([^)]+\).{0,80}announcements.{0,40}", t):
        print("---", name)
        print(m.group(0)[:220])
    for m in re.finditer(r"slice\(0,\s*[0-9]+\)", t):
        # only print those near notice or announce within file - too many
        pass
    # search notice-card nearby
    idx = t.find("网站公告")
    if idx < 0:
        idx = t.find("notice")
    if idx >= 0:
        print("context", name, t[max(0, idx - 80) : idx + 200])

# brute force replace in homepage chunks that contain 网站公告
for f in root.glob("rMEP6MmM*.js"):
    t = f.read_text(encoding="utf-8", errors="ignore")
    if "网站公告" not in t and "announcements" not in t:
        continue
    # Look for .slice(0,4) that appears after announcements in the same function-ish area
    # Nuxt often compiles as: (home.announcements||[]).slice(0,4)
    patterns = [
        (r'(\.announcements\|\|\[\]\)\.slice\(0,)4(\))', r'\g<1>10\2'),
        (r'(announcements\|\|\[\]\)\.slice\(0,)4(\))', r'\g<1>10\2'),
        (r'(announcements\|\|\[\]\)\.slice\(0, )4(\))', r'\g<1>10\2'),
        (r'(announcements\|\|\[\])\.slice\(0,4\)', r'\1.slice(0,10)'),
        (r'(announcements\|\|\[\])\.slice\(0, 4\)', r'\1.slice(0,10)'),
    ]
    nt = t
    for a, b in patterns:
        nt2, n = re.subn(a, b, nt)
        if n:
            print(f.name, "re", a, "->", n)
            nt = nt2
    # also literal
    if "slice(0,4)" in nt and "announcements" in nt:
        # replace ALL slice(0,4) only if file is homepage and count is small
        c = nt.count("slice(0,4)") + nt.count("slice(0, 4)")
        print(f.name, "slice04 count", c)
        if c <= 3:
            nt = nt.replace("slice(0,4)", "slice(0,10)").replace("slice(0, 4)", "slice(0,10)")
            print(f.name, "replaced all small count")
    if nt != t:
        f.write_text(nt, encoding="utf-8")
        print("wrote", f.name)

# verify API
import subprocess
php = r'''<?php
require "/www/wwwroot/g.aifeili.cn/vendor/autoload.php";
'''
# just curl home api via php file_get or grep limit in Home
p = Path("/www/wwwroot/g.aifeili.cn/app/controller/api/v1/Home.php")
tt = p.read_text(encoding="utf-8")
m = re.search(r"announcements.*?limit\((\d+)\)", tt, re.S)
print("api limit", m.group(1) if m else "not found")
