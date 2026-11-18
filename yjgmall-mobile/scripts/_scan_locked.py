#!/usr/bin/env python3
from pathlib import Path
import re
s=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt/DLdwU6hU.js").read_text(encoding="utf-8",errors="ignore")
# locked computeds
for m in re.finditer(r'(te|Ie|mt|Le)=w\(\(\)=>\{.{0,120}', s):
    print(m.group(0))
# toolbar filter fn
i=s.find('["id","title","name","username"]')
print("TB", s[i-120:i+180])
# menuMatch in layout files
nuxt=Path("/www/wwwroot/g.aifeili.cn/public/_nuxt")
for p in nuxt.glob("*.js"):
    t=p.read_text(encoding="utf-8",errors="ignore")
    if "startsWith(to+" in t or 'startsWith(to+"&"' in t or "startsWith(to+\"&\")" in t:
        print("MATCHFILE", p.name)
        i=t.find("startsWith(to")
        print(t[max(0,i-350):i+250])
