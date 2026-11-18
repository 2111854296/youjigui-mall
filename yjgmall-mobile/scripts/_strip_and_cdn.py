#!/usr/bin/env python3
from pathlib import Path
chunk=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js")
s=chunk.read_text(encoding="utf-8",errors="ignore")
i=s.find("127.0.0.1:7691")
if i<0:
    print("clean")
else:
    a=s.rfind("fetch(", 0, i+10)
    end=".catch(()=>{})"
    b=s.find(end, a)
    if b<0:
        raise SystemExit("end missing")
    b2=b+len(end)
    print("removing", repr(s[a-30:a]), ">>>", repr(s[b2:b2+40]))
    s=s[:a]+s[b2:]
    chunk.write_text(s,encoding="utf-8")
    print("left", s.count("127.0.0.1:7691"))

# nginx cache headers for js?
import subprocess
r=subprocess.run(["curl","-sI","https://g.aifeili.cn/_nuxt/DLdwU6hU.js"],stdout=subprocess.PIPE,stderr=subprocess.PIPE,universal_newlines=True)
print("HEADERS\n", r.stdout)
# confirm pendingFinal in downloaded body first bytes search
r2=subprocess.run(["curl","-s","https://g.aifeili.cn/_nuxt/DLdwU6hU.js"],stdout=subprocess.PIPE,stderr=subprocess.PIPE,universal_newlines=True)
body=r2.stdout
print("CDN pendingFinal", body.count("pendingFinal"), "ae_pe", body.count("ae(),pe()"), "select_locked", "e(se)?.status&&!e(te)" in body, "len", len(body))
