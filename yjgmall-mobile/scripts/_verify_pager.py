#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import os
import re

ROOT = "/www/wwwroot/g.aifeili.cn/public/_nuxt"
path = os.path.join(ROOT, "CfDteVJo.js")
s = open(path, encoding="utf-8").read()
i = s.find("function __cpi")
print("at", i)
print("BEFORE", repr(s[max(0, i - 80) : i]))
k = s.find("return out}", i)
print("AFTER_HELPER", repr(s[k : k + 40]))
print("has_use", "__cpi(n(W),n(qt).last_page)" in s)
print("old_gone", "y(n(qt).last_page," not in s)

# Try parse with node via full path
helper = s[i : k + 11]
open("/tmp/check_cpi.js", "w").write(
    helper + ";\nconsole.log(JSON.stringify(__cpi(1,102)));\nconsole.log(JSON.stringify(__cpi(50,102)));\n"
)
for node in ["/usr/bin/node", "/usr/local/bin/node", "/www/server/nodejs/v18/bin/node", "/www/server/nodejs/v20/bin/node"]:
    if os.path.isfile(node):
        import subprocess
        r = subprocess.Popen([node, "/tmp/check_cpi.js"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        out, err = r.communicate()
        print("NODE", node, out.decode("utf-8", "ignore").strip())
        print("ERR", err.decode("utf-8", "ignore").strip()[:300])
        break
else:
    print("no node binary")

# CSS
add = ".news-portal .pager .pg-ellipsis{min-width:24px;text-align:center;color:var(--ink-3,#98a5bc);user-select:none;letter-spacing:.08em}"
for f in os.listdir(ROOT):
    if not f.endswith(".css"):
        continue
    p = os.path.join(ROOT, f)
    css = open(p, encoding="utf-8", errors="ignore").read()
    if ".news-portal .pager" in css and "pg-ellipsis" not in css:
        if ".news-portal .pager{" in css:
            css = css.replace(".news-portal .pager{", add + ".news-portal .pager{", 1)
        else:
            css = css + "\n" + add + "\n"
        open(p, "w", encoding="utf-8").write(css)
        print("css_patched", f)
    elif "pg-ellipsis" in css and "news-portal" in css:
        print("css_ok", f)

# jobs naive loops
for f in os.listdir(ROOT):
    if not f.endswith(".js"):
        continue
    p = os.path.join(ROOT, f)
    try:
        js = open(p, encoding="utf-8", errors="ignore").read()
    except Exception:
        continue
    if "goJobPage" not in js and "招聘专区" not in js:
        continue
    print("JOBFILE", f)
    # find y(XXX, where XXX looks like page count used with goJobPage nearby
    for m in re.finditer(r"y\(([A-Za-z0-9_$.]+),([a-z])=>", js):
        start = max(0, m.start() - 60)
        end = min(len(js), m.end() + 200)
        chunk = js[start:end]
        if "goJobPage" in chunk or "goTalPage" in chunk or "pager" in chunk:
            print("LOOP", chunk[:260])
