#!/usr/bin/env python3
from pathlib import Path
import re

root = Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
repl = '(function(a){var l=String(a.link||"").trim();if(/^https?:\\/\\//i.test(l))return l;return a.id?("/announce?id="+a.id):"/announce"})(a)'
patterns = [
    'a.link||"/announce"',
    "a.link||'/announce'",
    '(a.link||"/announce")',
    "(a.link||'/announce')",
]
count = 0
for f in root.glob("*.js"):
    try:
        t = f.read_text(encoding="utf-8", errors="ignore")
    except Exception:
        continue
    if "/announce" not in t:
        continue
    nt = t
    for p in patterns:
        if p in nt:
            nt = nt.replace(p, repl)
    # also vue template compiled forms sometimes: .link||"/announce"
    nt2 = re.sub(
        r'([A-Za-z_$][\w$]*)\.link\|\|"/announce"',
        lambda m: f'(function(a){{var l=String(a.link||"").trim();if(/^https?:\\/\\//i.test(l))return l;return a.id?("/announce?id="+a.id):"/announce"}})({m.group(1)})',
        nt,
    )
    if nt2 != t:
        bak = f.with_suffix(f.suffix + ".bak_announce_href")
        if not bak.exists():
            bak.write_text(t, encoding="utf-8")
        f.write_text(nt2, encoding="utf-8")
        count += 1
        print("patched", f.name)
print("done", count)
