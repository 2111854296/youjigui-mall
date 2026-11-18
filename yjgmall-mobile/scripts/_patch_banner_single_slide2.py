#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re
import shutil

NUXT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
CHUNK = os.path.join(NUXT, "rMEP6MmM.js")
BAK = CHUNK + ".bak_single_slide"

# restore from first bak if we mangled dots
if os.path.isfile(BAK):
    s = open(BAK, encoding="utf-8").read()
    print("restored from bak", len(s))
else:
    s = open(CHUNK, encoding="utf-8").read()
    print("no bak, using current")

# Find the SLIDE loop specifically: key:n.id||m and img-slide
needle = 'q(a(le),(n,m)=>(r(),o(S,{key:n.id||m}'
j = s.find(needle)
if j < 0:
    # maybe already patched
    alt = 'q([a(le)[a(A)]].filter(Boolean),(n,m)=>(r(),o(S,{key:n.id||m}'
    if alt in s:
        print("already single slide")
    else:
        # show candidates
        for m in re.finditer(r'q\(a\(le\),\(n,m\)=>.{0,80}', s):
            print("CAND", m.group(0)[:120])
        raise SystemExit("slide loop not found")
else:
    end = s.find("),128))", j)
    if end < 0:
        raise SystemExit("end not found")
    end = end + len("),128))")
    old = s[j:end]
    print("OLD_LEN", len(old))
    print("OLD_HEAD", old[:220])
    print("OLD_TAIL", old[-100:])
    if "img-slide" not in old and "ad-tag" not in old:
        raise SystemExit("matched block is not slides")

    new = old.replace(
        "q(a(le),(n,m)=>",
        "q([a(le)[a(A)]].filter(Boolean),(n,m)=>",
        1,
    )
    # always mark as on
    new = new.replace("{on:m===a(A)}", "{on:!0}")
    if new.count("{on:!0}") < 1:
        print("WARN on-class replace count", new.count("{on:!0}"))
    s = s[:j] + new + s[end:]
    print("patched slides ok")

# ensure dots loop still uses full list
if "q([a(le)[a(A)]].filter(Boolean),(n,m)=>(r(),o(\"button\"" in s:
    s = s.replace(
        'q([a(le)[a(A)]].filter(Boolean),(n,m)=>(r(),o("button"',
        'q(a(le),(n,m)=>(r(),o("button"',
        1,
    )
    print("restored dots loop")

open(CHUNK, "w", encoding="utf-8").write(s)
dst = os.path.join(NUXT, "rMEP6MmM.ss.js")
shutil.copy2(CHUNK, dst)

# bust refs to ss.js
nref = 0
for f in os.listdir(NUXT):
    if not f.endswith(".js"):
        continue
    p = os.path.join(NUXT, f)
    t = open(p, encoding="utf-8", errors="ignore").read()
    if "rMEP6MmM.js" in t:
        open(p, "w", encoding="utf-8").write(t.replace("rMEP6MmM.js", "rMEP6MmM.ss.js"))
        print("bust", f)
        nref += 1
    elif "rMEP6MmM.ss.js" in t:
        print("already bust", f)
print("refs", nref)
print("verify filter", s.count("[a(le)[a(A)]].filter(Boolean)"))
print("verify img", "[a(le)[a(A)]].filter(Boolean),(n,m)=>(r(),o(S,{key:n.id||m}" in s)
