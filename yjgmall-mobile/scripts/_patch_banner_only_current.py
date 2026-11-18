#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Patch banner to keep reactive v-for, but only emit current slide vnode."""
import os
import shutil
import re

NUXT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
BAK = os.path.join(NUXT, "rMEP6MmM.js.bak_single_slide")
if not os.path.isfile(BAK):
    raise SystemExit("bak missing")

s = open(BAK, encoding="utf-8").read()
needle = "q(a(le),(n,m)=>(r(),o(S,{key:n.id||m}"
j = s.find(needle)
if j < 0:
    raise SystemExit("needle missing in bak")
end = s.find("),128))", j) + len("),128))")
old = s[j:end]
print("old_len", len(old))

# Wrap: only render when m===bannerIndex
# q(a(le),(n,m)=>m!==a(A)?C("",!0):(r(),o(S,...)))
new = old.replace(
    "q(a(le),(n,m)=>(r(),o(S,{key:n.id||m}",
    "q(a(le),(n,m)=>m!==a(A)?C(\"\",!0):(r(),o(S,{key:n.id||m}",
    1,
)
new = new.replace("{on:m===a(A)}", "{on:!0}")
if new == old:
    raise SystemExit("replace failed")
# balance parens: we added ternary so the original `(r(),o(...))` needs closing for ternary
# Original: q(a(le),(n,m)=>(r(),o(S,...))),128)
# New:      q(a(le),(n,m)=>m!==a(A)?C("",!0):(r(),o(S,...))),128)
# The closing of (r(),o(...)) already has enough parens before ),128)
# Check: old ends with ))],64))),128))
# new should end the same since we only changed the start.

s2 = s[:j] + new + s[end:]
print("new_head", new[:200])
print("new_tail", new[-120:])

stamp = ";window.__BANNER_SINGLE_SLIDE=2;"
out = stamp + s2
for name in ["rMEP6MmM.js", "rMEP6MmM.ss.js", "rMEP6MmM.ss2.js", "rMEP6MmM.ss3.js"]:
    p = os.path.join(NUXT, name)
    open(p, "w", encoding="utf-8").write(out)
    print("wrote", name)

# point 0a3 and 0a3 b1 to ss3
ss3 = "rMEP6MmM.ss3.js"
for f in ["0a3-bBq-.js", "0a3-bBq-.b1.js"]:
    p = os.path.join(NUXT, f)
    if not os.path.isfile(p):
        continue
    t = open(p, encoding="utf-8", errors="ignore").read()
    t2 = re.sub(r"rMEP6MmM(?:\.ss3|\.ss2|\.ss)?\.js", ss3, t)
    open(p, "w", encoding="utf-8").write(t2)
    print("entry", f, ss3 in t2)

# bust html entry again
html = "/www/wwwroot/g.aifeili.cn/public/index.html"
if os.path.isfile(html):
    h = open(html, encoding="utf-8", errors="ignore").read()
    # copy entry to b2
    src = os.path.join(NUXT, "0a3-bBq-.js")
    dstn = "0a3-bBq-.b2.js"
    shutil.copy2(src, os.path.join(NUXT, dstn))
    h = re.sub(r"0a3-bBq-(?:b1|b2)?\.js", dstn, h)
    open(html, "w", encoding="utf-8").write(h)
    print("html ->", dstn)

print("DONE")
